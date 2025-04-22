<?php
// Database connection
$host = "localhost";
$dbname = "campus_security_system";
$username = "root";
$password = "";
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webcam Feed</title>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <style>
        .unregistered-warning {
            color: white;
            background-color: #ff4444;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            animation: blink 1s linear infinite;
            display: none;
        }
        /* Add these styles for registered vehicles */
        .owner-details.registered-vehicle span {
            color: #2e7d32; /* Dark green for registered vehicles */
            font-weight: bold;
        }
        .owner-details.unregistered-vehicle span {
            color: #ff0000 !important;
            font-weight: bold;
        }

        .unregistered-warning.active {
            display: block;
        }

        @keyframes blink {
            50% { opacity: 0.7; }
        }

        @keyframes highlight {
            0% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.7); }
            50% { box-shadow: 0 0 20px 10px rgba(33, 150, 243, 0); }
            100% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0); }
        }

        video {
            display: block;
            margin: 0 auto;
            width: 800px;
            max-width: 100%;
            height: 450px;
            object-fit: cover;
        }

        .video-container {
            position: relative;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        h1 {
            text-align: center;
        }

        .navigate-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
        }

        .navigate-btn:hover {
            background-color: #0b7dda;
            transform: scale(1.1);
        }

        .logout-icon {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 50px;
            height: 50px;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
        }

        .logout-icon:hover {
            transform: scale(1.2);
        }

        .plate-info {
            margin-left: 20px;
            padding: 20px;
            background: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }

        .plate-info h2 {
            margin-bottom: 10px;
        }

        .plate-image {
            width: 250px;
            height: auto;
            border-radius: 8px;
        }

        .plate-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .owner-details p {
            margin: 8px 0;
            padding: 5px;
            background-color: #f8f8f8;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Logout Icon -->
    <a href="logout.php">
        <img src="logout-icon.png" alt="Logout" class="logout-icon">
    </a>

    <div class="container">
        <!-- Video Feed -->
        <div class="video-container">
            <h1>Live Camera View</h1>
            <div style="position: relative; display: inline-block;">
                <video id="droidCam" autoplay></video>
                <div id="videoOverlay" class="video-overlay"></div>
            </div>
            <button class="navigate-btn" onclick="window.location.href='access_history.php'">Vehicle Access History</button>
            <button class="navigate-btn" onclick="window.location.href='registered_vehicles.php'">Registered Vehicle Record</button>
            <button class="navigate-btn" onclick="window.location.href='operation.php'">Operation Page</button>
        </div>

        <!-- License Plate Display -->
        <div class="plate-info" id="plateDisplay">
            <h2>Detected License Plate</h2>
            <img id="plateImage" class="plate-image" src="get_plate_image.php?car_id=1&frame_nmr=150" alt="No plate detected">
            <p class="plate-number" id="plateText">Waiting for detection...</p>

            <div id="unregisteredWarning" class="unregistered-warning">
                ⚠️ UNREGISTERED VEHICLE ⚠️
            </div>

            <div class="owner-details" id="ownerDetails" style="margin-top: 20px; text-align: left;">
                <p><strong>Owner:</strong> <span id="ownerName">-</span></p>
                <p><strong>Student ID:</strong> <span id="studentId">-</span></p>
                <p><strong>Vehicle:</strong> <span id="carModel">-</span></p>
                <p><strong>Permit Type:</strong> <span id="permitType">-</span></p>
            </div>
        </div>
    </div>

    <script>
        // Initialize WebSocket connection
        const socket = io('http://localhost:5000');

        // Handle new detection events
        socket.on('new_detection', (data) => {
            console.log("Real-time detection:", data);
            updateDetectionDisplay(data);
        });

        function updateDetectionDisplay(data) {
            // Update license plate text
            const plateText = document.getElementById("plateText");
            const plateImage = document.getElementById("plateImage");
            
            if (plateText) {
                plateText.textContent = `License Number: ${data.license_number}`;
            }
            
            if (plateImage && data.license_number) {
                plateImage.src = `get_plate_image.php?license_number=${encodeURIComponent(data.license_number)}`;
            }

            // Update owner information if available
            if (data.owner_info) {
                const ownerInfo = data.owner_info;
                const warningDiv = document.getElementById("unregisteredWarning");
                const ownerDetails = document.querySelector(".owner-details");
                
                // Update all fields
                document.getElementById("ownerName").textContent = ownerInfo.owner_name;
                document.getElementById("studentId").textContent = ownerInfo.student_id;
                document.getElementById("carModel").textContent = ownerInfo.car_brand_model;
                document.getElementById("permitType").textContent = ownerInfo.permit_type;
                
                // Check registration status
                if (ownerInfo.is_registered === false) {
                    ownerDetails.classList.add("unregistered-vehicle");
                    ownerDetails.classList.remove("registered-vehicle");
                    warningDiv.classList.add("active");
                } else {
                    ownerDetails.classList.remove("unregistered-vehicle");
                    ownerDetails.classList.add("registered-vehicle");
                    warningDiv.classList.remove("active");
                }
                
                // Highlight animation
                const plateDisplay = document.getElementById("plateDisplay");
                plateDisplay.style.animation = "none";
                void plateDisplay.offsetWidth;
                plateDisplay.style.animation = "highlight 1s";
            }
        }

        let video = document.getElementById("droidCam");
        let canvas = document.createElement("canvas");
        let context = canvas.getContext("2d");

        async function startDroidCam() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === "videoinput");

                let droidCamDevice = videoDevices.find(device => device.label.includes("DroidCam"));

                const constraints = {
                    video: {
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        aspectRatio: 16 / 9,
                        frameRate: { ideal: 30 },
                        deviceId: droidCamDevice ? droidCamDevice.deviceId : undefined
                    }
                };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                document.getElementById("droidCam").srcObject = stream;
                setInterval(sendFrameToFlask, 500);
            } catch (error) {
                console.error("Error accessing webcam:", error);
                alert("Webcam access is required to view the live feed.");
            }
        }

        function sendFrameToFlask() {
            if (!video.videoWidth || !video.videoHeight) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(blob => {
                let formData = new FormData();
                formData.append("frame", blob, "frame.jpg");

                fetch("http://127.0.0.1:5000/process_frame", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => console.log("Flask Response:", data))
                .catch(error => console.error("Error sending frame:", error));
            }, "image/jpeg");
        }

        window.onload = function() {
            startDroidCam();
        };
    </script>
</body>
</html>