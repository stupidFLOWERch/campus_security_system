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
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f4f4f4;
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
        .navigate-btn:hover{
            background-color: #0b7dda;
            transform: scale(1.1); /* Slightly enlarges the button */
        }
        /* Logout icon styles */
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
    </style>
</head>
<body>
    <!-- Logout Icon -->
    <a href="logout.php">
        <img src="logout-icon.png" alt="Logout" class="logout-icon">
    </a>
    <h1>Live Camera View</h1>
    <video id="droidCam" autoplay></video>

    <button class="navigate-btn" onclick="window.location.href='access_history.php'">Vehicle Access History</button>
    <button class="navigate-btn" onclick="window.location.href='registered_vehicles.php'">Registered Vehicle Record</button>
    <button class="navigate-btn" onclick="window.location.href='operation.php'">Operation Page</button>
    <script>
        async function startDroidCam() {
            try {
                // Get list of available video input devices
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === "videoinput");

                console.log("input", videoDevices);

                // Find the device named "DroidCam Video"
                let droidCamDevice = videoDevices.find(device => device.label.includes("DroidCam"));

                // If found, use DroidCam, otherwise fallback to default
                const constraints = {
                    video: {
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        aspectRatio: 16 / 9,
                        frameRate: { ideal: 30 }, // Smooth video playback
                        deviceId: droidCamDevice ? droidCamDevice.deviceId : undefined
                    }
                };

                // Get webcam stream
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                document.getElementById("droidCam").srcObject = stream;
            } catch (error) {
                console.error("Error accessing webcam:", error);
                alert("Webcam access is required to view the live feed.");
            }
        }

        // Start the webcam when the page loads
        window.onload = startDroidCam;
    </script>
</body>
</html>
