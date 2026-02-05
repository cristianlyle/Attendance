<?php
session_start();

// Supabase REST API config
$project_url = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
$storage_url = "https://sdqnovlgutbvoxsctirk.supabase.co/storage/v1";
$apikey = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey"; // your anon key
$bucket_name = "profile_images"; // bucket name
$table = "users";


// GET request helper
function supabase_get($endpoint, $query = '') {
    global $project_url, $apikey;
    $url = $project_url . '/' . $endpoint;
    if ($query) $url .= '?' . $query;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// POST request helper
function supabase_post($endpoint, $data) {
    global $project_url, $apikey;
    $ch = curl_init($project_url . '/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// PATCH request helper (for updates)
function supabase_patch($endpoint, $data, $query) {
    global $project_url, $apikey;
    $url = $project_url . '/' . $endpoint . '?' . $query;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// DELETE request helper
function supabase_delete($endpoint, $query) {
    global $project_url, $apikey;
    $url = $project_url . '/' . $endpoint . '?' . $query;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Upload file to Supabase Storage using REST API
function supabase_upload_image($file) {
    global $storage_url, $apikey, $bucket_name;
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['error' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp'];
    }
    
    // Generate unique filename
    $fileName = 'profile_' . uniqid() . '.' . $fileExtension;
    
    // URL encode the filename
    $encodedFileName = urlencode($fileName);
    
    $url = $storage_url . "/object/$bucket_name/$encodedFileName";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey",
        "Content-Type: " . $file['type'],
        "cache-control: max-age=3600"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file['tmp_name']));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 201) {
        // Return the public URL
        $publicUrl = $storage_url . "/object/public/$bucket_name/$fileName";
        return ['path' => $publicUrl];
    }
    
    return ['error' => "Upload failed (HTTP $httpCode): $error, Response: $response"];
}

// Delete file from Supabase Storage
function supabase_delete_image($path) {
    global $storage_url, $apikey, $bucket_name;
    
    // Extract the file path from the URL
    $fileName = str_replace("$storage_url/object/public/$bucket_name/", '', $path);
    $encodedFileName = urlencode($fileName);
    
    $url = $storage_url . "/object/$bucket_name/$encodedFileName";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey"
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200 || $httpCode === 204;
}

// Get profile image URL with default fallback
function get_profile_image($profileImage = null) {
    if (!empty($profileImage)) {
        return $profileImage;
    }
    
    // Return default avatar (Boxicons user icon as SVG data URI)
    return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23c0c0c0"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
}
