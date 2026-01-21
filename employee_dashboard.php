<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='employee') header("Location: login.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Dashboard</title>
<link rel="stylesheet" href="css/employee-dashboard.css">
<script src="libs/jsQR.js"></script>
<script src="js/employee-dashboard.js"></script>
</head>
<body>
<div class="container">
<h1>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></h1>
<video id="qrVideo" width="300" height="300"></video>
<p id="attendanceMsg"></p>

<h2>My Attendance</h2>
<table id="attendanceTable">
<thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Location</th></tr></thead>
<tbody></tbody>
</table>
<button onclick="exportCSV()">Export CSV</button>
<a href="logout.php">Logout</a>
</div>
</body>
</html>
