import mysql.connector
from paddleocr import PaddleOCR
import os
import logging
import re
import cv2
import numpy as np

# Database Configuration
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "campus_security_system"
}

# Suppress PaddleOCR logging
os.environ["GLOG_minloglevel"] = "3"  # Suppress C++ logs
os.environ["PADDLE_LOG_LEVEL"] = "ERROR"  # Only show errors
logging.getLogger("ppocr").setLevel(logging.ERROR)  # Suppress PaddleOCR Python logs

# Initialize the OCR reader
reader = PaddleOCR(use_angle_cls=True, lang="en", use_mp=False)

# Mapping dictionaries for character conversion
dict_char_to_int = {'O': '0',
                    'I': '1',
                    'J': '3',
                    'A': '4',
                    'G': '6',
                    'S': '5'}

dict_int_to_char = {'0': 'O',
                    '1': 'I',
                    '3': 'J',
                    '4': 'A',
                    '6': 'G',
                    '5': 'S'}

def is_valid_malaysian_plate(text):
    if re.fullmatch(r'^[A-Z]{3}[0-9]{4}$', text):  # ABC1234
        return 1
    elif re.fullmatch(r'^[A-Z]{2}[0-9]{4}[A-Z]$', text):  # AB1234C
        return 2
    elif re.fullmatch(r'^[A-Z]{2}[0-9]{4}$', text):  # AB1234
        return 3
    elif re.fullmatch(r'^[A-Z]{2}[0-9]{3}$', text):  # AB123
        return 4
    elif re.fullmatch(r'^[A-Z]{3}[0-9]{2}$', text):  # ABC12
        return 5
    else:
        return 0

def write_mysql(results):
    """
    Write detection results to MySQL, saving both original and thresholded license plate images as BLOBs.
    """
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()

        insert_query = """
        INSERT INTO detections (frame_nmr, car_id, car_bbox, license_plate_bbox, 
                                license_plate_image, license_plate_image_thresh,
                                license_plate_bbox_score, license_number, license_number_score)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
        """

        for frame_nmr, frame_data in results.items():
            for car_id, car_data in frame_data.items():
                if 'car' in car_data and 'license_plate' in car_data:
                    car_bbox = '[{} {} {} {}]'.format(*car_data['car']['bbox'])
                    license_plate_bbox = '[{} {} {} {}]'.format(*car_data['license_plate']['bbox'])
                    license_plate_bbox_score = car_data['license_plate']['bbox_score']
                    license_number = car_data['license_plate']['text']
                    license_number_score = car_data['license_plate']['text_score']

                    # Retrieve the license plate images (original and thresholded)
                    license_plate_image = car_data['license_plate']['image1']
                    license_plate_image_thresh = car_data['license_plate']['image2']

                    # ✅ Ensure both images are NumPy arrays before encoding
                    def convert_to_blob(image):
                        if isinstance(image, bytes):
                            return image  # Already in binary format
                        elif isinstance(image, np.ndarray):
                            _, buffer = cv2.imencode('.jpg', image)
                            return buffer.tobytes()
                        else:
                            print(f"⚠️ Warning: Image is not valid!")
                            return None  # Store NULL in MySQL

                    license_plate_image = convert_to_blob(license_plate_image)
                    license_plate_image_thresh = convert_to_blob(license_plate_image_thresh)

                    # Execute query
                    cursor.execute(insert_query, (frame_nmr, car_id, car_bbox, license_plate_bbox,
                                                  license_plate_image, license_plate_image_thresh,
                                                  license_plate_bbox_score, license_number, license_number_score))
        
        conn.commit()

    except mysql.connector.Error as err:
        print("❌ Error writing to MySQL:", err)

    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()


def license_complies_format(text):
    """
    Check if the license plate text complies with the required format.

    Args:
        text (str): License plate text.

    Returns:
        bool: True if the license plate complies with the format, False otherwise.
    """
    if is_valid_malaysian_plate(text) == 0:
        return False
    else:
        return True


def format_license(text):
    """
    Format the license plate text by converting characters using the mapping dictionaries.

    Args:
        text (str): License plate text.

    Returns:
        str: Formatted license plate text.
    """
    license_plate_ = ''
    format_type = is_valid_malaysian_plate(text)
    # Define mapping for each valid format type
    mappings = {
        1: {0: dict_int_to_char, 1: dict_int_to_char, 2: dict_int_to_char,
            3: dict_char_to_int, 4: dict_char_to_int, 5: dict_char_to_int, 6: dict_char_to_int},
        2: {0: dict_int_to_char, 1: dict_int_to_char, 2: dict_char_to_int,
            3: dict_char_to_int, 4: dict_char_to_int, 5: dict_char_to_int, 6: dict_int_to_char},
        3: {0: dict_int_to_char, 1: dict_int_to_char, 2: dict_char_to_int, 3: dict_char_to_int,
            4: dict_char_to_int, 5: dict_char_to_int},
        4: {0: dict_int_to_char, 1: dict_int_to_char, 2: dict_char_to_int,
            3: dict_char_to_int, 4: dict_char_to_int},
        5: {0: dict_int_to_char, 1: dict_int_to_char, 2: dict_int_to_char, 3: dict_char_to_int,
            4: dict_char_to_int}
    }
    mapping = mappings[format_type]
    for j in range(len(text)):
        if j in mapping and text[j] in mapping[j]:
            license_plate_ += mapping[j][text[j]]
        else:
            license_plate_ += text[j]

    return license_plate_

def read_license_plate(license_plate_crop):
    """
    Read the license plate text from the given cropped image.

    Args:
        license_plate_crop (PIL.Image.Image): Cropped image containing the license plate.

    Returns:
        tuple: Tuple containing the formatted license plate text and its confidence score.
    """

    detections = reader.ocr(license_plate_crop, cls=True)

    for detection_group in detections:  # ✅ Outer list
        if detection_group is None:  # Skip None values
            continue
        for detection in detection_group:  # ✅ Inner list containing bbox and (text, score)
            if not isinstance(detection, list) or len(detection) < 2:
                continue  # Ensure detection has valid data
            if len(detection) == 2 and isinstance(detection[1], tuple):  # ✅ Ensure valid format
                bbox = detection[0]  # Bounding box
                text, score = detection[1]  # Extract text and score separately

                text = text.upper().replace(' ', '')  # Format text
                print({text})

                if license_complies_format(text):
                    return format_license(text), score

    return None, None # Return None if no valid plate is found


def get_car(license_plate, vehicle_track_ids):
    """
    Retrieve the vehicle coordinates and ID based on the license plate coordinates.

    Args:
        license_plate (tuple): Tuple containing the coordinates of the license plate (x1, y1, x2, y2, score, class_id).
        vehicle_track_ids (list): List of vehicle track IDs and their corresponding coordinates.

    Returns:
        tuple: Tuple containing the vehicle coordinates (x1, y1, x2, y2) and ID.
    """
    x1, y1, x2, y2, score, class_id = license_plate

    foundIt = False
    for j in range(len(vehicle_track_ids)):
        xcar1, ycar1, xcar2, ycar2, car_id = vehicle_track_ids[j]

        if x1 > xcar1 and y1 > ycar1 and x2 < xcar2 and y2 < ycar2:
            car_indx = j
            foundIt = True
            break

    if foundIt:
        return vehicle_track_ids[car_indx]

    return -1, -1, -1, -1, -1
