from flask import Flask, request, jsonify
from flask_socketio import SocketIO
from flask_cors import CORS  # Import CORS
import cv2
import numpy as np
import os
import mysql.connector
from ultralytics import YOLO
from sort.sort import *
from util import get_car, read_license_plate

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
license_plate_detector = YOLO(os.path.join(current_dir, "license_plate.pt"))

vehicles = [2, 3, 5, 7]  # Vehicle class IDs

socketio = SocketIO(app, cors_allowed_origins="*")

@app.route('/process_frame', methods=['POST'])
def process_frame():
    global frame_nmr, first_detection
    frame_nmr += 1
    MIN_CONFIDENCE = 0.85  # Minimum confidence score to consider a detection valid

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
    highest_detection = get_highest_detection()

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
            
            if isinstance(license_plate_text, set):
                license_plate_text = ''.join(license_plate_text) if license_plate_text else None
            elif not isinstance(license_plate_text, str):
                license_plate_text = str(license_plate_text) if license_plate_text else None
            
            if license_plate_text:
                # Clean the license plate text
                license_plate_text = license_plate_text.replace('{', '').replace('}', '').replace("'", "")
                license_plate_text = license_plate_text.strip().upper()
                # Remove any non-alphanumeric characters
                license_plate_text = ''.join(c for c in license_plate_text if c.isalnum())
            
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
                elif highest_detection.get("car_id") != car_id:
                    save_result = True
                    print("-> Saving (new vehicle/plate)")
                elif highest_detection.get("car_id") == car_id:
                    highest_text_score = highest_detection.get("text_score", 0)
                    if license_plate_text_score > highest_text_score:
                        save_result = True
                        print(f"-> Saving (better score than previous {highest_text_score:.2f})")
                
                if save_result:
                    # Ensure license_plate_text is valid
                    if not license_plate_text or not isinstance(license_plate_text, str):
                        print(f"Invalid license plate text: {license_plate_text} - Skipping insertion")
                        continue
                        
                    detection_data = {
                        'car_id': car_id,
                        'license_number': license_plate_text,
                        'license_number_score': license_plate_text_score,
                        'license_plate_crop': license_plate_Crop_blob,
                        'license_plate_crop_thresh': license_plate_crop_thresh_blob
                    }
                    
                    # Emit real-time detection via WebSocket
                    socketio.emit('new_detection', {
                        'license_number': license_plate_text,
                        'timestamp': frame_nmr,
                        'confidence': license_plate_text_score,
                        'owner_info': get_owner_info(license_plate_text)  # Add this line
                    })
                    
                    mysql_data['detections'].append(detection_data)

            elif license_plate_text is not None:
                print(f"[Frame {frame_nmr}] Low confidence: {license_plate_text} (Score: {license_plate_text_score:.2f}) - Not saving")

    if mysql_data['detections']:

        write_mysql(mysql_data)
        del results[frame_nmr]

    return jsonify({
        "message": "Frame processed",
        "detections": len(mysql_data['detections']),
        "frame_nmr": frame_nmr
    })

# Add this new function to server.py:
def get_owner_info(license_plate):
    """Retrieve owner information from the database based on license plate"""
    try:
        conn = mysql.connector.connect(
            user="root", password="",
            database="campus_security_system",
            host="localhost"
        )
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT owner_name, student_id, car_brand_model, permit_type 
            FROM vehicle_registered_details
            WHERE license_plate = %s
            LIMIT 1
        """, (license_plate,))
        
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        
        if result:
            # Return the result with registration status
            return {
                'owner_name': result['owner_name'],
                'student_id': result['student_id'],
                'car_brand_model': result['car_brand_model'],
                'permit_type': result['permit_type'],
                'is_registered': True  # Vehicle is registered
            }
        else:
            # Vehicle not found in database
            return {
                'owner_name': 'UNKNOWN',
                'student_id': 'N/A',
                'car_brand_model': 'UNKNOWN',
                'permit_type': 'N/A',
                'is_registered': False  # Vehicle is not registered
            }
        
    except mysql.connector.Error as err:
        print(f"Database error: {err}")
        return {
            'owner_name': 'DATABASE ERROR',
            'student_id': 'DB ERROR',
            'car_brand_model': 'DB ERROR',
            'permit_type': 'DB ERROR',
            'is_registered': False
        }
    
def get_highest_detection():
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
            """SELECT frame_nmr, license_number
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
                "license_number": best_detection["license_number"]
            })

    return jsonify({"error": "No detections found"}), 404


def write_mysql(data):
    try:
        conn = mysql.connector.connect(
            user="root", password="",
            database="campus_security_system",
            host="localhost"
        )
        cursor = conn.cursor()

        for detection in data['detections']:
            # Additional validation
            if not detection.get('license_number'):
                print("Skipping detection with empty license number")
                continue
                
            try:
                cursor.execute("""
                    INSERT INTO detections
                    (car_id, frame_nmr, license_number, license_number_score, 
                    license_plate_crop, license_plate_crop_thresh)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, (
                    detection['car_id'],
                    data['frame_nmr'],
                    detection['license_number'],
                    float(detection['license_number_score']),  # Ensure float
                    detection['license_plate_crop'],
                    detection['license_plate_crop_thresh']
                ))
            except mysql.connector.Error as err:
                print(f"Error inserting detection: {err}")
                continue

        conn.commit()
        print(f"Successfully inserted {len(data['detections'])} records")
    except Exception as e:
        print(f"Database connection error: {e}")
    finally:
        if 'cursor' in locals(): cursor.close()
        if 'conn' in locals(): conn.close()

if __name__ == '__main__':
    socketio.run(app, host='0.0.0.0', port=5000, debug=True)
