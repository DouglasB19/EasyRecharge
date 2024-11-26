<?php

require_once '../../config/config.php';  
require_once '../../vendor/autoload.php';  
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");


function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        if (preg_match('/^[A-Za-z0-9\-._~\+\/]+=*$/', $jwt)) {
            return $jwt;
        }
    }
    return null;
}


$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    http_response_code(401); 
    exit();
}

try {
    
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    http_response_code(401); 
    exit();
}

try {
    
    $query = "SELECT id, name, email, phone FROM users WHERE id = :user_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    
    $response = [
        'success' => false,
        'message' => 'User not found',
    ];

    if ($user) {
        $response = [
            'success' => true,
            'user' => $user,
        ];
    }

    echo json_encode($response);
} catch (PDOException $e) {
    
    error_log("Database Error: " . $e->getMessage() . " | User ID: $user_id", 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
    http_response_code(500); 
} catch (Exception $e) {
    
    error_log("Error: " . $e->getMessage() . " | User ID: $user_id", 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Failed to fetch user profile"]);
    http_response_code(500); 
}

