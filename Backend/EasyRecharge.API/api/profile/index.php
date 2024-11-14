<?php
// Incluir configurações e dependências necessárias
require_once '../../config/config.php';  // Conexão com o banco de dados
require_once '../../vendor/autoload.php';  // Carregar autoload do composer (caso esteja usando JWT)
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");

// Função para verificar e extrair o JWT
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

// Extrair o token JWT
$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    http_response_code(401); // Unauthorized
    exit();
}

try {
    // Decodificar o token e obter o user_id
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid or expired token"]);
    http_response_code(401); // Unauthorized
    exit();
}

try {
    // Consultar as informações do usuário no banco de dados
    $query = "SELECT id, name, email, phone FROM users WHERE id = :user_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Buscar o resultado da consulta
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Estrutura de resposta
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
    // Registrar erro no log e retornar mensagem genérica de erro
    error_log("Database Error: " . $e->getMessage() . " | User ID: $user_id", 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Database error. Please try again later."]);
    http_response_code(500); // Internal Server Error
} catch (Exception $e) {
    // Registrar erro genérico no log e retornar mensagem genérica de erro
    error_log("Error: " . $e->getMessage() . " | User ID: $user_id", 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Failed to fetch user profile"]);
    http_response_code(500); // Internal Server Error
}
?>
