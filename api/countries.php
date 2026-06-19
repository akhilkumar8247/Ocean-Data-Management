<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError("Invalid input format.");
    }
    
    $country_code = isset($input['countryCode']) ? intval($input['countryCode']) : null;
    $country_name = isset($input['countryName']) ? trim($input['countryName']) : '';
    
    // Validations
    if ($country_code === null || $country_code < 0 || $country_code > 999) {
        sendError("Country Code must be an integer between 0 and 999.");
    }
    
    if (empty($country_name)) {
        sendError("Country Name is required.");
    }
    
    if (!preg_match('/^[A-Za-z ]+$/', $country_name)) {
        sendError("Country Name can only contain letters and spaces.");
    }
    
    try {
        // Check if code exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_country` WHERE country_code = :code");
        $stmt->execute(['code' => $country_code]);
        if ($stmt->fetchColumn() > 0) {
            sendError("Country Code $country_code already exists.");
        }
        
        // Check if name exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `t_country` WHERE country_name = :name");
        $stmt->execute(['name' => $country_name]);
        if ($stmt->fetchColumn() > 0) {
            sendError("Country Name '$country_name' already exists.");
        }
        
        // Insert
        $stmt = $pdo->prepare("INSERT INTO `t_country` (country_code, country_name) VALUES (:code, :name)");
        $stmt->execute([
            'code' => $country_code,
            'name' => $country_name
        ]);
        
        sendResponse([
            "countryCode" => $country_code,
            "countryName" => $country_name
        ], 201);
    } catch (PDOException $e) {
        sendError("Failed to create country: " . $e->getMessage(), 500);
    }
} else {
    sendError("Method not allowed", 405);
}
