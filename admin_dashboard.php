<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="css/admin-dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
</head>
<body>

<div class="container">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?> (Admin)</p>
    <a href="logout.php">Logout</a>

    <!-- ================= QR GENERATOR ================= -->
    <div class="qr-box">
        <h2>Generate QR Code for Attendance</h2>

        <label>Location</label>
        <select id="qrLocation">
            <option value="Warehouse">Warehouse</option>
            <option value="Main Building">Main Building</option>
            <option value="Remote">Remote</option>
        </select>

        <button onclick="generateQRCode()">Generate QR Code</button>

        <canvas id="qrCodeCanvas"></canvas>

        <div class="qr-info">
            <p><strong>Token:</strong> <span id="qrToken">—</span></p>
            <p><strong>Created At:</strong> <span id="qrCreated">—</span></p>
            <p><strong>Expires In:</strong> <span id="qrExpires">—</span></p>
            <p><strong>Location:</strong> <span id="qrLocationText">—</span></p>
        </div>

        <p id="qrMsg"></p>
    </div>

    <!-- ================= ACTIVE QRS ================= -->
    <div class="section">
        <h2>Active QR Codes</h2>
        <table id="qrTable">
            <thead>
                <tr>
                    <th>Token</th>
                    <th>Location</th>
                    <th>Expires At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script src="js/admin-dashboard.js"></script>
</body>
</html>
