<?php
// Incluir a configuração e dependências
require_once '../../config/config.php';  // Configuração do banco de dados
require_once '../../vendor/autoload.php';  // Dependências externas (por exemplo, JWT)

// Importando a classe JWT do Firebase
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Função para extrair o token JWT do cabeçalho Authorization
function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        return filter_var($jwt, FILTER_SANITIZE_STRING) ? $jwt : null;
    }
    return null;
}

// Função para sanitizar entrada de dados
function sanitizeInput($input) {
    return filter_var(trim($input), FILTER_SANITIZE_STRING);
}

// Verificar se o token JWT foi enviado
$jwt = extractJwt();
if (!$jwt) {
    echo json_encode(["message" => "Token JWT is required"]);
    http_response_code(400);
    exit();
}

// Decodificar e validar o JWT
try {
    // Decodificando o token JWT
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    // O ID do usuário está dentro do objeto decodificado
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    echo json_encode(["message" => "Invalid token", "error" => $e->getMessage()]);
    http_response_code(400);
    exit();
}

// Verificar se os dados da solicitação foram enviados
$subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : null;
$message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : null;
$priority = isset($_POST['priority']) ? sanitizeInput($_POST['priority']) : 'medium';

// Validar campos obrigatórios
if (!$subject || !$message) {
    echo json_encode(["message" => "Subject and message are required"]);
    http_response_code(400);
    exit();
}

// Inserir a solicitação de suporte no banco de dados
try {
    // Preparando a query para inserir a solicitação no banco de dados
    $stmt = $pdo->prepare("INSERT INTO support_requests (user_id, subject, message, priority, status) 
                           VALUES (:user_id, :subject, :message, :priority, 'open')");
    $stmt->bindParam(':user_id', $user_id);  // ID do usuário obtido do JWT
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':priority', $priority);

    // Executando a inserção
    $stmt->execute();

    // Enviar resposta de sucesso
    echo json_encode(["message" => "Support request submitted successfully"]);
    http_response_code(200);  // Código de sucesso (200 OK)
} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registrar o erro e responder com mensagem
    error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    echo json_encode(["message" => "Error inserting support request", "error" => $e->getMessage()]);
    http_response_code(500);  // Código de erro interno do servidor (500)
}

?>
