from flask import Flask, request, jsonify
import cv2
import numpy as np
import os
from ultralytics import YOLO
from sort.sort import *
from util import get_car, read_license_plate, write_mysql

app = Flask(__name__)

# Get current directory
current_dir = os.path.dirname(os.path.abspath(__file__))

results = {}
# Global frame counter
frame_nmr = -1
FRAME_LIMIT = 100  # Set the max number of frames to keep
mot_tracker = Sort()

# Load YOLO models with full paths
coco_model = YOLO(os.path.join(current_dir, "yolov8n.pt"))
license_plate_detector = YOLO(os.path.join(current_dir, "license_plate3.pt"))

vehicles = [2, 3, 5, 7]  # Vehicle class IDs

@app.route('/process_frame', methods=['POST'])
def process_frame():
    global frame_nmr
    frame_nmr += 1

    if 'frame' not in request.files:
        return jsonify({"error": "No frame uploaded"}), 400

    file = request.files['frame']
    npimg = np.frombuffer(file.read(), np.uint8)
    frame = cv2.imdecode(npimg, cv2.IMREAD_COLOR)

    if frame is None:
        return jsonify({"error": "Invalid image format"}), 400

    results[frame_nmr] = {}  # Initialize frame storage

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
                results[frame_nmr][car_id] = {
                    'car': {'bbox': [xcar1, ycar1, xcar2, ycar2]},
                    'license_plate': {
                        'bbox': [x1, y1, x2, y2],
                        'text': license_plate_text,
                        'bbox_score': score,
                        'text_score': license_plate_text_score if license_plate_text_score else 0
                    }
            }
    try:
        write_mysql({frame_nmr: results[frame_nmr]})  # Save only the current frame
    except Exception as e:
        print("Error writing to MySQL:", e)

    return jsonify({
        "message": "Frame processed",
        "frame_nmr": frame_nmr,
        "results": results[frame_nmr]
    })

@app.route('/bounding_boxes', methods=['GET'])
def get_bounding_boxes():
    return jsonify({
        "all_results": results,  # All stored results
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
