from flask import Flask, request, jsonify
# from flask_caching import Cache
from flask_socketio import SocketIO
from flask_cors import CORS  # Import CORS
import cv2
import numpy as np
import os
import mysql.connector
from ultralytics import YOLO
from sort.sort import *
from util import get_car, read_license_plate, write_mysql

app = Flask(__name__)
CORS(app)
socketio = SocketIO(app, cors_allowed_origins="*", async_mode='threading')

# Get current directory
current_dir = os.path.dirname(os.path.abspath(__file__))

results = {}
first_detection = True
# Global frame counter
frame_nmr = -1
mot_tracker = Sort()

# Load YOLO models with full paths
coco_model = YOLO(os.path.join(current_dir, "yolov8n.pt"))
license_plate_detector = YOLO(os.path.join(current_dir, "license_plate3.pt"))

vehicles = [2, 3, 5, 7]  # Vehicle class IDs

socketio = SocketIO(app, cors_allowed_origins="*")

@socketio.on('connect')
def handle_connect():
    print('Client connected')

@app.route('/process_frame', methods=['POST'])
def process_frame():
    global frame_nmr, first_detection
    frame_nmr += 1
    save_result = False
    MIN_CONFIDENCE = 0.8  # Minimum confidence score to consider a detection valid

    if 'frame' not in request.files:
        return jsonify({"error": "No frame uploaded"}), 400

    file = request.files['frame']
    npimg = np.frombuffer(file.read(), np.uint8)
    frame = cv2.imdecode(npimg, cv2.IMREAD_COLOR)

    if frame is None:
        return jsonify({"error": "Invalid image format"}), 400
    
    if frame_nmr not in results:
        results[frame_nmr] = {}

    # Detect vehicles
    detections = coco_model(frame, verbose=False)[0]  # Disable YOLO's built-in logging
    detections_ = []
    for detection in detections.boxes.data.tolist():
        x1, y1, x2, y2, score, class_id = detection
        if int(class_id) in vehicles and score > 0.5:  # Only consider high-confidence vehicle detections
            detections_.append([x1, y1, x2, y2, score])

    # Track vehicles
    track_ids = mot_tracker.update(np.asarray(detections_)) if detections_ else []

    # Detect license plates
    license_plates = license_plate_detector(frame, verbose=False)[0]
    last_detection = get_last_detection()

    # Prepare data for MySQL
    mysql_data = {
        'frame_nmr': frame_nmr,
        'detections': []
    }

    for license_plate in license_plates.boxes.data.tolist():
        x1, y1, x2, y2, score, class_id = license_plate
        xcar1, ycar1, xcar2, ycar2, car_id = get_car(license_plate, track_ids)

        if car_id != -1:
            license_plate_crop = frame[int(y1):int(y2), int(x1): int(x2), :]
            license_plate_crop = cv2.resize(license_plate_crop, (372, 146))
            license_plate_crop_gray = cv2.cvtColor(license_plate_crop, cv2.COLOR_BGR2GRAY)
            license_plate_crop_gray = cv2.GaussianBlur(license_plate_crop_gray, (3, 3), 0)
            license_plate_crop_thresh = cv2.adaptiveThreshold(
                license_plate_crop_gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                cv2.THRESH_BINARY_INV, 11, 2)
            
            license_plate_text, license_plate_text_score = read_license_plate(license_plate_crop_thresh)
            
            if license_plate_text is not None and license_plate_text_score >= MIN_CONFIDENCE:
                print(f"[Frame {frame_nmr}] Detected: {license_plate_text} (Score: {license_plate_text_score:.4f})")
                
                # Save the cropped plate image for debugging
                cv2.imwrite(f'debug/plate_{frame_nmr}_{car_id}.jpg', license_plate_crop)
                
                _, buffer = cv2.imencode('.jpg', license_plate_crop)
                license_plate_Crop_blob = buffer.tobytes()
                _, buffer = cv2.imencode('.jpg', license_plate_crop_thresh)
                license_plate_crop_thresh_blob = buffer.tobytes()

                # Determine if we should save this detection
                save_result = False
                
                if first_detection:
                    save_result = True
                    first_detection = False
                    print("-> Saving (first detection)")
                elif (last_detection.get("car_id") != car_id or last_detection.get("license_number") != license_plate_text):
                    save_result = True
                    print("-> Saving (new vehicle/plate)")
                elif last_detection.get("car_id") == car_id:
                    highest_text_score = last_detection.get("text_score", 0)
                    if license_plate_text_score > highest_text_score:
                        save_result = True
                        print(f"-> Saving (better score than previous {highest_text_score:.2f})")
                
                if save_result:
                    detection_data = {
                        'car_id': car_id,
                        'license_number': license_plate_text,
                        'license_number_score': license_plate_text_score,
                        'license_plate_bbox': [x1, y1, x2, y2],
                        'car_bbox': [xcar1, ycar1, xcar2, ycar2],
                        'license_plate_crop': license_plate_Crop_blob,
                        'license_plate_crop_thresh': license_plate_crop_thresh_blob
                    }
                    
                    # Emit real-time detection via WebSocket
                    socketio.emit('new_detection', {
                        'license_number': license_plate_text,
                        'license_plate_bbox': [x1, y1, x2, y2],
                        'car_bbox': [xcar1, ycar1, xcar2, ycar2],
                        'timestamp': frame_nmr,
                        'confidence': license_plate_text_score
                    })
                    
                    mysql_data['detections'].append(detection_data)

                    results[frame_nmr][car_id] = {
                        'car': {'bbox': [xcar1, ycar1, xcar2, ycar2]},
                        'license_plate': {
                            'bbox': [x1, y1, x2, y2],
                            'text': license_plate_text,
                            'bbox_score': score,
                            'text_score': license_plate_text_score
                        }
                    }
            elif license_plate_text is not None:
                print(f"[Frame {frame_nmr}] Low confidence: {license_plate_text} (Score: {license_plate_text_score:.2f}) - Not saving")

    if mysql_data['detections']:
        print(f"Saving {len(mysql_data['detections'])} detections to database")
        write_mysql(mysql_data)
        del results[frame_nmr]

    return jsonify({
        "message": "Frame processed",
        "detections": len(mysql_data['detections']),
        "frame_nmr": frame_nmr
    })

def get_last_detection():
    """Retrieve the last detected license plate from the database."""
    try:
        # Connect to MySQL database
        conn = mysql.connector.connect(
            user="root", password="", database="campus_security_system", host="localhost"
        )
        cursor = conn.cursor(dictionary=True)

        # Get the latest detected vehicle
        cursor.execute("SELECT car_id FROM detections ORDER BY id DESC LIMIT 1")
        latest_car = cursor.fetchone()

        if latest_car:
            car_id = latest_car["car_id"]

            # Get the detection with the highest confidence score for this car
            cursor.execute(
                """
                SELECT frame_nmr, license_number, license_number_score 
                FROM detections WHERE car_id=%s 
                ORDER BY license_number_score DESC LIMIT 1
                """,
                (car_id,)
            )
            best_detection = cursor.fetchone()

            cursor.close()
            conn.close()

            return {
                "car_id": car_id,
                "frame_nmr": best_detection["frame_nmr"] if best_detection else None,
                "license_number": best_detection["license_number"] if best_detection else "Unknown",
                "text_score": best_detection["license_number_score"] if best_detection else 0
            }

        return {"car_id": None, "frame_nmr": None, "license_number": "Unknown", "text_score": 0}
    
    except mysql.connector.Error as err:
        print(f"Database error: {err}")
        return {"car_id": None, "frame_nmr": None, "license_number": "Unknown", "text_score": 0}


@app.route("/get_latest_plate")
def get_latest_plate():
    # Connect to MySQL database
    conn = mysql.connector.connect(user="root", password="", database="campus_security_system", host="localhost")
    cursor = conn.cursor(dictionary=True)

    # Get the latest detected vehicle
    cursor.execute("SELECT car_id FROM detections ORDER BY id DESC LIMIT 1")
    latest_car = cursor.fetchone()

    if latest_car:
        car_id = latest_car["car_id"]

        # Get the detection with the highest confidence score for this car
        cursor.execute(
            """SELECT frame_nmr, license_number, license_plate_bbox_x1, license_plate_bbox_y1, 
                  license_plate_bbox_x2, license_plate_bbox_y2, car_bbox_x1, car_bbox_y1, 
                  car_bbox_x2, car_bbox_y2 
               FROM detections WHERE car_id=%s 
               ORDER BY license_number_score DESC LIMIT 1""",
            (car_id,)
        )
        best_detection = cursor.fetchone()

        cursor.close()
        conn.close()

        if best_detection:
            return jsonify({
                "car_id": car_id,
                "frame_nmr": best_detection["frame_nmr"],
                "license_number": best_detection["license_number"],
                "license_plate_bbox": [
                    best_detection["license_plate_bbox_x1"],
                    best_detection["license_plate_bbox_y1"],
                    best_detection["license_plate_bbox_x2"],
                    best_detection["license_plate_bbox_y2"]
                ],
                "car_bbox": [
                    best_detection["car_bbox_x1"],
                    best_detection["car_bbox_y1"],
                    best_detection["car_bbox_x2"],
                    best_detection["car_bbox_y2"]
                ]
            })

    return jsonify({"error": "No detections found"}), 404


if __name__ == '__main__':
    socketio.run(app, host='0.0.0.0', port=5000, debug=True)
