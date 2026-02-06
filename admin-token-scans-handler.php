<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized - Admin access required']);
    http_response_code(401);
    exit;
}

// Supabase config
$project_url = "https://sdqnovlgutbvoxsctirk.supabase.co";
$anon_key = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";

// Fetch token scans
$url = $project_url . "/rest/v1/token_scans?order=scanned_at.desc";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $anon_key",
    "Authorization: Bearer $anon_key"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $scans = json_decode($response, true);
    
    // Fetch users for the scans
    $usersUrl = $project_url . "/rest/v1/users";
    $ch = curl_init($usersUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $anon_key",
        "Authorization: Bearer $anon_key"
    ]);
    $usersResponse = curl_exec($ch);
    curl_close($ch);
    
    $users = json_decode($usersResponse, true);
    
    // Create users map
    $usersMap = [];
    if ($users) {
        foreach ($users as $user) {
            $usersMap[$user['id']] = $user;
        }
    }
    
    // Create token scans map (flattened structure)
    $tokenScansMap = [];
    if ($scans) {
        foreach ($scans as $scan) {
            if (!isset($tokenScansMap[$scan['token_id']])) {
                $user = $usersMap[$scan['user_id']] ?? null;
                $tokenScansMap[$scan['token_id']] = [
                    'action' => $scan['action'],
                    'scanned_at' => $scan['scanned_at'],
                    'user_id' => $scan['user_id'],
                    'token_id' => $scan['token_id'],
                    'users' => $user
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'scans' => $scans,
        'users_map' => $usersMap,
        'token_scans_map' => $tokenScansMap
    ]);
} else {
    echo json_encode(['error' => "Failed to fetch token scans (HTTP $httpCode)", 'response' => $response]);
    http_response_code($httpCode);
}
