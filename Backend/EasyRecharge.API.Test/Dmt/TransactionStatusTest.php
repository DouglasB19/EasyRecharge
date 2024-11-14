<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TransactionStatusTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de consulta de status de transação bem-sucedida
    public function testSuccessfulTransactionStatus()
    {
        // ID de uma transação existente
        $transactionId = '123456'; // Substitua com um ID de transação válida

        // Envia a requisição GET para o endpoint de consulta de status de transação
        $response = $this->client->get('api/dmt/transaction-status.php/' . $transactionId);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém as chaves esperadas
        $this->assertArrayHasKey('transaction_id', $data);
        $this->assertEquals($transactionId, $data['transaction_id']);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('message', $data);
    }

    // Teste de consulta de status de transação com ID inválido
    public function testTransactionStatusWithInvalidId()
    {
        // ID de transação inexistente
        $transactionId = 'invalid123'; // ID inválido para teste

        // Envia a requisição GET para o endpoint de consulta de status de transação
        $response = $this->client->get('api/dmt/transaction-status.php/' . $transactionId, [
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 404 Not Found
        $this->assertEquals(404, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a mensagem de erro apropriada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Transaction not found.', $data['error']);
    }

    // Teste de consulta de status de transação com formato inválido no ID
    public function testTransactionStatusWithInvalidFormat()
    {
        // ID de transação com formato inválido (exemplo: ID numérico esperado)
        $transactionId = 'abc123'; // ID inválido para teste (deve ser numérico)

        // Envia a requisição GET para o endpoint de consulta de status de transação
        $response = $this->client->get('api/dmt/transaction-status.php/' . $transactionId, [
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a mensagem de erro para formato inválido
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid transaction ID format.', $data['error']);
    }
}
?>