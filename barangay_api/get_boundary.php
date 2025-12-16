<?php
header('Content-Type: application/json');

// Simulated boundary data for Barangay Pacol
$boundary = [
    "type" => "Feature",
    "geometry" => [
        "type" => "Polygon",
        "coordinates" => [
            [
                [122.8097, 10.5289],
                [122.8100, 10.5290],
                [122.8105, 10.5285],
                [122.8097, 10.5289]
            ]
        ]
    ],
    "properties" => [
        "name" => "Barangay Pacol"
    ]
];

echo json_encode($boundary);
?>