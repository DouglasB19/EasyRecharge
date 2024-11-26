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
        if (filter_var($jwt, FILTER_SANITIZE_STRING)) {
            return $jwt;
        }
    }
    return null;
}

$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

$name = isset($data->name) ? trim($data->name) : null;
$email = isset($data->email) ? trim($data->email) : null;
$phone = isset($data->phone) ? trim($data->phone) : null;


if (!$name || !$email || !$phone) {
    echo json_encode(["message" => "Name, email, and phone are required"]);
    exit();
}


if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
    echo json_encode(["message" => "Invalid phone number format. Use international format, e.g., +5511998765432"]);
    exit();
}

try {
    $query = "UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Profile updated successfully"]);
    } else {
        echo json_encode(["message" => "Failed to update profile"]);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
}
