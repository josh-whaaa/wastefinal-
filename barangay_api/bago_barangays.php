<?php
// Fetch JSON data from the PSGC API
$api_url = "https://psgc.cloud/api/barangays";
$json_data = file_get_contents($api_url);
$barangays = json_decode($json_data, true);

// Bago City's city_code (PSGC Code)
$bago_city_code = "064502000";

// Filter barangays belonging to Bago City
$bago_barangays = array_filter($barangays, function($barangay) use ($bago_city_code) {
    return $barangay['city_code'] === $bago_city_code;
});

// Return JSON response
header('Content-Type: application/json');
echo json_encode(array_values($bago_barangays), JSON_PRETTY_PRINT);
?>
