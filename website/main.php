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
    <title>Campus Security - Live Monitoring</title>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --error-color: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            height: 50px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 24px;
        }
        
        .logout-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .main-container {
            display: flex;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .video-section {
            flex: 2;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .info-section {
            flex: 1;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
        }
        
        video {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid var(--light-color);
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        
        .action-btn {
            padding: 12px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        .plate-display {
            margin-top: 20px;
            text-align: center;
        }
        
        .plate-image {
            width: 100%;
            max-width: 300px;
            border-radius: 8px;
            border: 2px solid var(--light-color);
            margin-bottom: 15px;
        }
        
        .plate-number {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding: 10px;
            background: var(--light-color);
            border-radius: 6px;
        }
        
        .alert-warning {
            background-color: var(--error-color);
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            display: none;
        }
        
        .alert-warning.active {
            display: flex;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .owner-details {
            margin-top: 20px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--light-color);
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .detail-value {
            font-weight: 600;
        }
        
        .registered-vehicle .detail-value {
            color: var(--success-color);
        }
        
        .unregistered-vehicle .detail-value {
            color: var(--error-color);
        }
        
        @keyframes highlight {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }
        
        .highlight {
            animation: highlight 1.5s;
        }
        
        @media (max-width: 1024px) {
            .main-container {
                flex-direction: column;
            }
            
            video {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <img src="uni-logo.png" alt="Campus Security" class="logo">
            <h1 class="page-title">Vehicle Monitoring System</h1>
        </div>
        <button class="logout-btn" onclick="window.location.href='logout.php'">
            <i class="bi bi-box-arrow-right"></i> Logout
        </button>
    </div>

    <div class="main-container">
        <!-- Video Feed Section -->
        <div class="video-section">
            <h2 class="section-title"><i class="bi bi-camera-video-fill"></i> Live Camera View</h2>
            <video id="droidCam" autoplay></video>
            
            <div class="action-buttons">
                <button class="action-btn" onclick="window.location.href='access_history.php'">
                    <i class="bi bi-clock-history"></i> Access History
                </button>
                <button class="action-btn" onclick="window.location.href='authorized_vehicles.php'">
                    <i class="bi bi-car-front-fill"></i> Authorized Vehicle
                </button>
                
            </div>
        </div>

        <!-- Information Section -->
        <div class="info-section">
            <h2 class="section-title"><i class="bi bi-card-text"></i> Detection Details</h2>
            
            <div class="plate-display">
                <img id="plateImage" class="plate-image" src="placeholder-plate.png" alt="No plate detected">
                <div class="plate-number" id="plateText">Waiting for detection...</div>
                
                <div id="unregisteredWarning" class="alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i> UNREGISTERED VEHICLE
                </div>
            </div>
            
            <div class="owner-details" id="ownerDetails">
                <h3 class="section-title"><i class="bi bi-person-badge-fill"></i> Owner Information</h3>
                
                <div class="detail-item">
                    <span class="detail-label">Owner:</span>
                    <span class="detail-value" id="ownerName">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Student ID:</span>
                    <span class="detail-value" id="studentId">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Vehicle:</span>
                    <span class="detail-value" id="carModel">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Permit Type:</span>
                    <span class="detail-value" id="permitType">-</span>
                </div>
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
                plateText.textContent = data.license_number || "No plate detected";
            }
            
            if (plateImage && data.license_number) {
                plateImage.src = `get_plate_image.php?license_number=${encodeURIComponent(data.license_number)}`;
                plateImage.alt = `License plate: ${data.license_number}`;
            }

            // Update owner information if available
            if (data.owner_info) {
                const ownerInfo = data.owner_info;
                const warningDiv = document.getElementById("unregisteredWarning");
                const ownerDetails = document.getElementById("ownerDetails");
                
                // Update all fields
                document.getElementById("ownerName").textContent = ownerInfo.owner_name || "-";
                document.getElementById("studentId").textContent = ownerInfo.student_id || "-";
                document.getElementById("carModel").textContent = ownerInfo.car_brand_model || "-";
                document.getElementById("permitType").textContent = ownerInfo.permit_type || "-";
                
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
                const infoSection = document.querySelector(".info-section");
                infoSection.classList.remove("highlight");
                void infoSection.offsetWidth; // Trigger reflow
                infoSection.classList.add("highlight");
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