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
            <video id="droidCam" autoplay></video>
            <button class="navigate-btn" onclick="window.location.href='access_history.php'">Vehicle Access History</button>
            <button class="navigate-btn" onclick="window.location.href='registered_vehicles.php'">Registered Vehicle Record</button>
        </div>

        <!-- License Plate Display -->
        <div class="plate-info" id="plateDisplay">
            <h2>Detected License Plate</h2>
            <img id="plateImage" class="plate-image" src="get_plate_image.php?car_id=1&frame_nmr=150" alt="No plate detected">
            <p class="plate-number" id="plateText">Waiting for detection...</p>
            <pre id="debugJson" style="background: #ddd; padding: 10px; border-radius: 5px;"></pre>
        </div>
    </div>

    <script>
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

        async function fetchLicensePlateData() {
            try {
                const response = await fetch("http://127.0.0.1:5000/get_latest_plate");
                const data = await response.json();

                // Debugging: Display raw JSON response
                console.log("Fetched data:", data);
                document.getElementById("debugJson").textContent = JSON.stringify(data, null, 2);

                let plateText = document.getElementById("plateText");

                if (!plateText) {
                    console.error("Element #plateText not found!");
                    return;
                }

                if (data.car_id && data.frame_nmr && data.license_number) {
                    let newText = `License Number: ${data.license_number}`;
                    console.log("Updating plateText:", newText);

                    // Force update by clearing and reassigning text
                    plateText.innerHTML = "";  // Clear existing text
                    plateText.innerText = newText;  // Set new text

                    // Optional: Trigger reflow to force a refresh
                    plateText.style.display = "none";
                    plateText.offsetHeight;  // Trigger reflow
                    plateText.style.display = "block";
                }
            } catch (error) {
                console.error("Error fetching license plate data:", error);
            }
        }

        window.onload = function() {
            startDroidCam();
            setInterval(fetchLicensePlateData, 2000);
        };
    </script>
</body>
</html>