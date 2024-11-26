<?php

require_once '../../config/config.php';  
require_once '../../vendor/autoload.php';  


use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;


function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        return filter_var($jwt, FILTER_SANITIZE_STRING) ? $jwt : null;
    }
    return null;
}


function sanitizeInput($input) {
    return filter_var(trim($input), FILTER_SANITIZE_STRING);
}


$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    http_response_code(400);
    exit();
}


try {
    
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid token", "error" => $e->getMessage()]);
    http_response_code(400);
    exit();
}


$subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : null;
$message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : null;
$priority = isset($_POST['priority']) ? sanitizeInput($_POST['priority']) : 'medium';


if (!$subject || !$message) {
    echo json_encode(["message" => "Subject and message are required"]);
    http_response_code(400);
    exit();
}


try {
    
    $stmt = $pdo->prepare("INSERT INTO support_requests (user_id, subject, message, priority, status) 
                           VALUES (:user_id, :subject, :message, :priority, 'open')");
    $stmt->bindParam(':user_id', $user_id);  
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':priority', $priority);

    
    $stmt->execute();

    
    echo json_encode(["message" => "Support request submitted successfully"]);
    http_response_code(200);  
} catch (PDOException $e) {
    
    error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Error inserting support request", "error" => $e->getMessage()]);
    http_response_code(500);  
}


