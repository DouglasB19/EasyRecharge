<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function sendError($message, $statusCode, $errorDetails = null) {
    echo json_encode(["message" => $message, "error" => $errorDetails]);
    http_response_code($statusCode);
    exit();
}

function extractJwt() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        return filter_var($jwt, FILTER_SANITIZE_STRING) ? $jwt : null;
    }
    return null;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

try {
    // Verificar se o JWT foi fornecido
    $jwt = extractJwt();
    if (!$jwt) {
        sendError("Token JWT is required", 400);
    }

    // Decodificar o token JWT e verificar o papel
    $decoded = JWT::decode($jwt, new Key($jwt_secret_key, 'HS256'));
    if ($decoded->role !== 'admin') {
        sendError("Access denied", 403);
    }

    // Recuperar e validar os parâmetros de entrada
    $ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : null;
    if ($ticket_id && $ticket_id <= 0) {
        sendError("Invalid ticket_id", 400);
    }

    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
    $validStatuses = ['open', 'in_progress', 'closed'];
    if ($status && !in_array($status, $validStatuses)) {
        sendError("Invalid status parameter", 400);
    }

    // Construir condições dinamicamente para a consulta SQL
    $conditions = [];
    if ($ticket_id) {
        $conditions[] = "id = :ticket_id";
    }
    if ($status) {
        $conditions[] = "status = :status";
    }

    $sql = "SELECT * FROM support_requests";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // Adicionar suporte à paginação
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 10;  // Limitar máximo de 100
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Vincular os parâmetros da consulta
    if ($ticket_id) {
        $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    }
    if ($status) {
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
        sendError("Error retrieving ticket status", 500);
    }

    // Verificar se encontrou tickets
    if (empty($tickets)) {
        echo json_encode(["tickets" => []]);
        http_response_code(200);
        exit();
    }

    // Obter o total de tickets para paginar
    $countSql = "SELECT COUNT(*) FROM support_requests";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $totalTickets = $countStmt->fetchColumn();

    // Retornar os tickets e a informação de paginação
    echo json_encode([
        "tickets" => $tickets,
        "total" => $totalTickets,
        "limit" => $limit,
        "offset" => $offset
    ]);
    http_response_code(200);

} catch (Exception $e) { 
    // Capturar e registrar qualquer erro não previsto
    error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage(), 3, '../../logs/error_logs.log');
    sendError("Internal server error", 500);
}
?>
