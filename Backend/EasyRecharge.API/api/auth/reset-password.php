<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php'; 

use \Firebase\JWT\JWT;

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->token) || !isset($data->new_password)) {
    http_response_code(422);
    echo json_encode(array("message" => "Token and new password are required"));
    exit();
}

$jwt = $data->token;
$newPassword = $data->new_password;


if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s]).{8,}$/', $newPassword)) {
    http_response_code(422); 
    echo json_encode(array("message" => "Password must be at least 8 characters long, contain at least one uppercase letter, one number, and one special character"));
    exit();
}

try {

    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));

 
    if (!isset($decoded->email)) {
        http_response_code(400); 
        echo json_encode(array("message" => "Token does not contain valid email information"));
        exit();
    }


    $email = $decoded->email; 
    $phone = isset($decoded->phone) ? $decoded->phone : null;

 
    if (isset($decoded->exp) && $decoded->exp < time()) {
        http_response_code(400); 
        echo json_encode(array("message" => "Token has expired"));
        exit();
    }


    if (isset($email)) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":email", $email);
    } else {
        $query = "SELECT id FROM users WHERE phone = :phone";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":phone", $phone);
    }
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(400);  
        echo json_encode(array("message" => "User not found"));
        exit();
    }

    $user_id = $user['id'];

} catch (Exception $e) {

    http_response_code(400);  
    echo json_encode(array("message" => "Invalid or expired token: " . $e->getMessage()));
    exit();
}

$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT); 


$pdo->beginTransaction();

try {
   
    $query = "SELECT password FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $userData = $stmt->fetch();

    if (password_verify($newPassword, $userData['password'])) {
        http_response_code(400);
        echo json_encode(array("message" => "New password cannot be the same as the current password"));
        exit();
    }

  
    $query = "UPDATE users SET password = :password WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":password", $hashedPassword);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

  
    $attemptQuery = "INSERT INTO password_reset_attempts (user_id, success) VALUES (:user_id, true)";
    $attemptStmt = $pdo->prepare($attemptQuery);
    $attemptStmt->bindParam(":user_id", $user_id);
    $attemptStmt->execute();

   
    $pdo->commit();

    echo json_encode(array("message" => "Password reset successful"));

} catch (Exception $e) {
    $pdo->rollBack(); 
    error_log("Error resetting password: " . $e->getMessage()); 
    echo json_encode(array("message" => "An unexpected error occurred. Please try again later"));
}

