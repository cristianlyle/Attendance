<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Unauthorized - User not logged in']);
    http_response_code(401);
    exit;
}

// Get the logged-in user ID
$logged_in_user_id = $_SESSION['user']['id'];

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Supabase config - get keys from environment or use defaults
$project_url = "https://sdqnovlgutbvoxsctirk.supabase.co";
$anon_key = getenv('SUPABASE_ANON_KEY') ?: "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";
$service_key = getenv('SUPABASE_SERVICE_KEY') ?: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNkcW5vdmxndXRidm94c2N0aXJrIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2ODgzOTQ0NCwiZXhwIjoyMDg0NDE1NDQ0fQ.-o1opTA1wUOwAz2oPb1084EHFlts168qm3uPNuO2qwU";

if ($method === 'POST') {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $token_id = $input['token_id'] ?? null;
    $action = $input['action'] ?? null;
    
    // Get user_id from session (more secure)
    $user_id = $_SESSION['user']['id'] ?? null;
    
    // Validate required fields
    if (!$user_id || !$token_id || !$action) {
        echo json_encode(['error' => 'Missing required fields: token_id, action']);
        http_response_code(400);
        exit;
    }
    
    // Validate action
    $valid_actions = ['time_in', 'lunch_out', 'lunch_in', 'time_out'];
    if (!in_array($action, $valid_actions)) {
        echo json_encode(['error' => 'Invalid action']);
        http_response_code(400);
        exit;
    }
    
    // Insert token scan using Supabase REST API
    $url = $project_url . "/rest/v1/token_scans";
    
    $data = [
        'user_id' => $user_id,
        'token_id' => $token_id,
        'action' => $action
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $anon_key",
        "Authorization: Bearer $anon_key",
        "Content-Type: application/json",
        "Accept: application/json",
        "Prefer: return=minimal"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(['success' => true, 'message' => 'Token scan recorded successfully']);
    } else {
        // If anon key fails, try service role key
        if ($httpCode === 401 || $httpCode === 403) {
            if ($service_key !== "YOUR_SERVICE_ROLE_KEY_HERE") {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "apikey: $service_key",
                    "Authorization: Bearer $service_key",
                    "Content-Type: application/json",
                    "Accept: application/json",
                    "Prefer: return=minimal"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    echo json_encode(['success' => true, 'message' => 'Token scan recorded successfully (using service key)']);
                    exit;
                }
            }
        }
        
        echo json_encode(['error' => "Failed to record token scan (HTTP $httpCode)", 'details' => $response]);
        http_response_code($httpCode);
    }
} else {
    echo json_encode(['error' => 'Method not allowed']);
    http_response_code(405);
}
