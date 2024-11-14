<?php
require_once '../../config/config.php';  // Configuração de banco de dados e conexão
require_once '../../vendor/autoload.php';  // Caminho da biblioteca JWT

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

// Função para extrair e verificar o token JWT
function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        return filter_var($jwt, FILTER_SANITIZE_STRING) ? $jwt : null;
    }
    return null;
}

// Verificar Token
$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    http_response_code(401);  // Unauthorized
    exit();
}

try {
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    if ($decoded->role !== 'admin') {
        echo json_encode(["message" => "Access denied"]);
        http_response_code(403);  // Forbidden
        exit();
    }
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    http_response_code(401);  // Unauthorized
    exit();
}

// Receber e validar o ID do usuário e ação (block ou unblock)
$data = json_decode(file_get_contents("php://input"));
if (!isset($data->user_id) || !is_numeric($data->user_id) || $data->user_id <= 0) {
    echo json_encode(["message" => "Valid user_id is required"]);
    http_response_code(400);  // Bad Request
    exit();
}

$user_id = (int) $data->user_id;
$action = isset($data->action) ? $data->action : null;

if ($action !== 'block' && $action !== 'unblock') {
    echo json_encode(["message" => "Action must be 'block' or 'unblock'"]);
    http_response_code(400);  // Bad Request
    exit();
}

// Função para bloquear ou desbloquear o usuário
try {
    $query = "SELECT role FROM users WHERE id = :user_id LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(["message" => "User not found"]);
        http_response_code(404);  // Not Found
        exit();
    }

    if ($action === 'block') {
        if ($user['role'] === 'blocked') {
            echo json_encode(["message" => "User is already blocked"]);
            http_response_code(400);  // Bad Request
            exit();
        }

        // Atualizar o role do usuário para 'blocked'
        $query = "UPDATE users SET role = 'blocked' WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "User blocked successfully"]);
            http_response_code(200);  // OK
        } else {
            echo json_encode(["message" => "Failed to block the user"]);
            http_response_code(500);  // Internal Server Error
        }
    } elseif ($action === 'unblock') {
        if ($user['role'] !== 'blocked') {
            echo json_encode(["message" => "User is not blocked"]);
            http_response_code(400);  // Bad Request
            exit();
        }

        // Atualizar o role do usuário para 'user' (desbloqueando)
        $query = "UPDATE users SET role = 'user' WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "User unblocked successfully"]);
            http_response_code(200);  // OK
        } else {
            echo json_encode(["message" => "Failed to unblock the user"]);
            http_response_code(500);  // Internal Server Error
        }
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
    http_response_code(500);  // Internal Server Error
}
?>
