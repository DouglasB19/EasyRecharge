<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class DetailsTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de consulta bem-sucedida de detalhes de transação
    public function testSuccessfulTransactionDetails()
    {
        // ID de uma transação válida
        $transactionId = '1'; // Substitua com um ID de transação válido no banco

        // Envia a requisição GET para o endpoint de consulta de detalhes de transação
        $response = $this->client->get('api/transactions/details.php/' . $transactionId);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém os detalhes esperados da transação
        $this->assertArrayHasKey('transaction_id', $data);
        $this->assertArrayHasKey('amount', $data);
        $this->assertArrayHasKey('date', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('user_id', $data);
    }

    // Teste de consulta de detalhes de transação com ID inválido
    public function testTransactionDetailsWithInvalidId()
    {
        // ID de transação inválido
        $transactionId = '9999'; // ID inválido para teste

        // Envia a requisição GET para o endpoint de consulta de detalhes de transação
        $response = $this->client->get('api/transactions/details.php/' . $transactionId, [
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 404 Not Found
        $this->assertEquals(404, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave de erro e uma mensagem adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Transaction not found.', $data['error']);
    }

    // Teste de consulta de detalhes de transação sem passar o ID
    public function testTransactionDetailsWithoutId()
    {
        // Envia a requisição GET para o endpoint de consulta de detalhes de transação sem ID
        $response = $this->client->get('api/transactions/details.php', [
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave de erro e uma mensagem adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Transaction ID is required.', $data['error']);
    }
}
?>