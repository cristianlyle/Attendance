<?php
session_start();

// Supabase REST API config
$project_url = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
$apikey = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey"; // your anon key
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
