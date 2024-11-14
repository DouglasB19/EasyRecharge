<?php
require_once '../../config/config.php';  // Configuração de banco de dados e conexão
require_once '../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json");  // Define que a resposta será em JSON

// Receber o token JWT da requisição
$headers = apache_request_headers();  // Obtém os cabeçalhos da requisição

// Verifica se o token foi enviado no cabeçalho Authorization
if (isset($headers['Authorization'])) {
    $jwt = str_replace("Bearer ", "", $headers['Authorization']);  // Remove o "Bearer" do token
} else {
    echo json_encode(array("message" => "Token is required"));
    http_response_code(400);  // Retorna código 400 para erro de requisição
    exit();
}

try {
    // Decodifica o token JWT
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));  // Decodifica o token com a chave secreta
    
    // Verifica se o token não expirou
    if (isset($decoded->exp) && $decoded->exp < time()) {
        echo json_encode(array("message" => "Token has expired"));
        http_response_code(401);  // Retorna 401 para token expirado
        exit();
    }

    // O token foi validado com sucesso, então podemos acessar os dados do usuário
    $user_id = $decoded->user_id;  // Recupera o ID do usuário do payload do token
    $email = $decoded->email;      // Recupera o email do usuário (ou qualquer outro dado que você queira)
    
    // Sucesso, retornamos uma resposta JSON com a mensagem de logout
    echo json_encode(array("message" => "Logout successful"));
    http_response_code(200);  // Retorna 200 OK para sucesso no logout
} catch (Exception $e) {
    echo json_encode(array("message" => "Access denied: " . $e->getMessage()));
    http_response_code(401);  // Retorna 401 para erro de acesso
    exit();
}
?>
