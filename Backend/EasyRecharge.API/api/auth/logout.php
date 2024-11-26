<?php
require_once '../../config/config.php';  
require_once '../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");  


$headers = apache_request_headers();  


if (isset($headers['Authorization'])) {
    $jwt = str_replace("Bearer ", "", $headers['Authorization']);  
} else {
    echo json_encode(array("message" => "Token is required"));
    http_response_code(400); 
    exit();
}

try {
    
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));  
    
    
    if (isset($decoded->exp) && $decoded->exp < time()) {
        echo json_encode(array("message" => "Token has expired"));
        http_response_code(401);  
        exit();
    }

    
    $user_id = $decoded->user_id;  
    $email = $decoded->email;     
    
   
    echo json_encode(array("message" => "Logout successful"));
    http_response_code(200); 
} catch (Exception $e) {
    echo json_encode(array("message" => "Access denied: " . $e->getMessage()));
    http_response_code(401); 
    exit();
}

