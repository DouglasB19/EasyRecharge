<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

// Função para extrair o token JWT do cabeçalho
function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        return str_replace('Bearer ', '', $headers['Authorization']);
    }
    return null;
}

// Extrair e validar o token JWT
$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
    if ($decoded->exp < time()) {
        echo json_encode(["message" => "Token has expired"]);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    exit();
}

// Capturar os dados de entrada
$data = json_decode(file_get_contents("php://input"));
$current_password = $data->current_password ?? null;
$new_password = $data->new_password ?? null;
$confirm_password = $data->confirm_password ?? null;

// Validações de senha
if (!$current_password || !$new_password || !$confirm_password) {
    echo json_encode(["message" => "Current, new, and confirm password are required"]);
    exit();
}

if (strlen($new_password) < 8) {
    echo json_encode(["message" => "New password must be at least 8 characters long"]);
    exit();
}

if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W_]/', $new_password)) {
    echo json_encode(["message" => "New password must contain at least one uppercase letter, one number, and one special character"]);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode(["message" => "New password and confirmation do not match"]);
    exit();
}

// Verificar a senha atual no banco de dados
try {
    $query = "SELECT password FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password'])) {
        echo json_encode(["message" => "Current password is incorrect"]);
        exit();
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "An error occurred while processing your request"]);
    exit();
}

// Criptografar a nova senha e atualizar no banco de dados
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

try {
    $update_query = "UPDATE users SET password = :new_password WHERE id = :user_id";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->bindParam(':new_password', $hashed_password, PDO::PARAM_STR);
    $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        echo json_encode(["message" => "Password updated successfully"]);
    } else {
        echo json_encode(["message" => "Failed to update password"]);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "An error occurred while processing your request"]);
}
?>