from flask import Flask, request, jsonify
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

@app.route('/process_frame', methods=['POST'])
def process_frame():
    global frame_nmr, first_detection
    frame_nmr += 1
    save_result = False


    if 'frame' not in request.files:
        return jsonify({"error": "No frame uploaded"}), 400

    file = request.files['frame']
    npimg = np.frombuffer(file.read(), np.uint8)
    frame = cv2.imdecode(npimg, cv2.IMREAD_COLOR)

    if frame is None:
        return jsonify({"error": "Invalid image format"}), 400

    if frame_nmr not in results:
        results[frame_nmr] = {}  # Ensure the frame entry exists

    # Detect vehicles
    detections = coco_model(frame)[0]
    detections_ = []
    for detection in detections.boxes.data.tolist():
        x1, y1, x2, y2, score, class_id = detection
        if int(class_id) in vehicles:
            detections_.append([x1, y1, x2, y2, score])

    print(f"🚗 Frame {frame_nmr}: Detected {len(detections_)} vehicles")
    # Track vehicles
    track_ids = mot_tracker.update(np.asarray(detections_)) if detections_ else []

    print(f"📌 Frame {frame_nmr}: Tracked {len(track_ids)} vehicles")
    # Detect license plates
    license_plates = license_plate_detector(frame)[0]
    
    # Retrieve last detection from database
    last_detection = get_last_detection()

    for license_plate in license_plates.boxes.data.tolist():
        x1, y1, x2, y2, score, class_id = license_plate
        xcar1, ycar1, xcar2, ycar2, car_id = get_car(license_plate, track_ids)

        if car_id != -1:
            # Crop and process license plate
            license_plate_crop = frame[int(y1):int(y2), int(x1): int(x2), :]
            license_plate_crop = cv2.resize(license_plate_crop, (372, 146))
            license_plate_crop_gray = cv2.cvtColor(license_plate_crop, cv2.COLOR_BGR2GRAY)
            license_plate_crop_gray = cv2.GaussianBlur(license_plate_crop_gray, (3, 3), 0)
            license_plate_crop_thresh = cv2.adaptiveThreshold(
                license_plate_crop_gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                cv2.THRESH_BINARY_INV, 11, 2)
            
            # Read license plate number
            license_plate_text, license_plate_text_score = read_license_plate(license_plate_crop_thresh)
            
            if license_plate_text is not None:
                # Convert the image to binary (for BLOB storage)
                _, buffer = cv2.imencode('.jpg', license_plate_crop)
                license_plate_Crop_blob = buffer.tobytes()  # Convert to binary
                _, buffer = cv2.imencode('.jpg', license_plate_crop_thresh)
                license_plate_crop_thresh_blob = buffer.tobytes()  # Convert to binary

                if first_detection == True:
                    save_result = True
                    first_detection = False
                elif last_detection.get("car_id") == car_id:
                    highest_text_score = last_detection.get("text_score", 0)
                    if license_plate_text_score > highest_text_score:
                        save_result = True
                elif last_detection.get("car_id") != car_id or last_detection.get("license_number") != license_plate_text:
                    save_result = True

                if save_result:
                    results[frame_nmr][car_id] = {
                        'car': {'bbox': [xcar1, ycar1, xcar2, ycar2]},
                        'license_plate': {
                            'bbox': [x1, y1, x2, y2],
                            'image1':license_plate_Crop_blob,
                            'image2': license_plate_crop_thresh_blob,
                            'text': license_plate_text,
                            'bbox_score': score,
                            'text_score': license_plate_text_score if license_plate_text_score else 0
                        }
                }
                
    if results[frame_nmr]:  # Only save if a license plate was detected
        write_mysql({frame_nmr: results[frame_nmr]})
        del results[frame_nmr]  # Remove from memory after saving

    return jsonify({
        "message": "Frame processed",
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
            "SELECT frame_nmr,license_number FROM detections WHERE car_id=%s ORDER BY license_number_score DESC LIMIT 1",
            (car_id,)
        )
        best_detection = cursor.fetchone()

        cursor.close()
        conn.close()

        return jsonify({
            "car_id": car_id,
            "frame_nmr": best_detection["frame_nmr"] if best_detection else None,
            "license_number": best_detection["license_number"] if best_detection else "Unknown"
        })

    return jsonify({"error": "No detections found"}), 404

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
