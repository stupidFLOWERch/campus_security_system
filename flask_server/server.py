from flask import Flask, request, jsonify
import cv2
import numpy as np
import os
import csv
from ultralytics import YOLO
from sort.sort import *
from util import get_car, read_license_plate, write_csv

app = Flask(__name__)

# Get current directory
current_dir = os.path.dirname(os.path.abspath(__file__))

# Load YOLO models with full paths
coco_model = YOLO(os.path.join(current_dir, "yolov8n.pt"))
license_plate_detector = YOLO(os.path.join(current_dir, "license_plate3.pt"))

# Initialize vehicle tracker
results = {}
mot_tracker = Sort()
vehicles = [2, 3, 5, 7]  # Vehicle class IDs

# CSV file path
csv_file = os.path.join(current_dir, "test.csv")

# Global frame counter
frame_nmr = -1

# Ensure CSV file has headers
def initialize_csv():
    if not os.path.exists(csv_file):
        with open(csv_file, mode='w', newline='') as file:
            writer = csv.writer(file)
            writer.writerow(['frame_nmr', 'car_id', 'car_bbox', 'license_plate_bbox', 'license_plate_bbox_score', 'license_number', 'license_number_score'])

@app.route('/process_frame', methods=['POST'])
def process_frame():
    global frame_nmr
    frame_nmr += 1  # Increment frame count

    # Check if frame is present in request
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
    detections = coco_model(frame)[0]
    detections_ = []
    for detection in detections.boxes.data.tolist():
        x1, y1, x2, y2, score, class_id = detection
        if int(class_id) in vehicles:
            detections_.append([x1, y1, x2, y2, score])

    # Track vehicles
    track_ids = mot_tracker.update(np.asarray(detections_)) if detections_ else []

    # Detect license plates
    license_plates = license_plate_detector(frame)[0]
    
    for license_plate in license_plates.boxes.data.tolist():
        x1, y1, x2, y2, score, class_id = license_plate
        xcar1, ycar1, xcar2, ycar2, car_id = get_car(license_plate, track_ids)

        if car_id != -1:
            # crop license plate
            license_plate_crop = frame[int(y1):int(y2), int(x1): int(x2), :]
            # resize license plate
            license_plate_crop = cv2.resize(license_plate_crop, (372, 146))
            # process license plate
            license_plate_crop_gray = cv2.cvtColor(license_plate_crop, cv2.COLOR_BGR2GRAY)

            # Apply Gaussian Blur (reduces noise)
            license_plate_crop_gray = cv2.GaussianBlur(license_plate_crop_gray, (3, 3), 0)
            # Apply adaptive thresholding
            license_plate_crop_thresh = cv2.adaptiveThreshold(
                license_plate_crop_gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                cv2.THRESH_BINARY_INV, 11, 2)
            
            # read license plate number
            license_plate_text, license_plate_text_score = read_license_plate(license_plate_crop_thresh)
            
            # Store only cars with detected license plates
            results[frame_nmr][car_id] = {
                'car': {'bbox': [xcar1, ycar1, xcar2, ycar2]},
                'license_plate': {
                    'bbox': [x1, y1, x2, y2],
                    'text': license_plate_text if license_plate_text else "N/A",
                    'bbox_score': score,
                    'text_score': license_plate_text_score if license_plate_text_score else 0
                }
            }

            try:
                print("Results before writing CSV:", results)  # Debugging line
                write_csv(results, csv_file)
            except Exception as e:
                print("Error writing CSV:", e)


    return jsonify({"status": "success"})

if __name__ == '__main__':
    
    app.run(host='0.0.0.0', port=5000, debug=True)
