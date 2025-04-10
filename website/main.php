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
        video {
            display: block;
            margin: 0 auto;
            width: 800px; /* Adjust width */
            max-width: 100%;
            height: 450px; /* Set a fixed height */
            object-fit: cover; /* Removes black bars */
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
            transform: scale(1.1); /* Slightly enlarges the button */
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
        </div>

        <!-- License Plate Display -->
        <div class="plate-info" id="plateDisplay">
            <h2>Detected License Plate</h2>
            <img id="plateImage" class="plate-image" src="get_plate_image.php?car_id=1&frame_nmr=150" alt="No plate detected">
            <p class="plate-number" id="plateText">Waiting for detection...</p>
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
                plateText.innerHTML = "";
                plateText.innerText = `License Number: ${data.license_number}`;
            }
            
            if (plateImage && data.license_number) {
                // Use license number to fetch the best matching image
                const imageUrl = `get_plate_image.php?license_number=${encodeURIComponent(data.license_number)}`;
                plateImage.src = imageUrl;
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