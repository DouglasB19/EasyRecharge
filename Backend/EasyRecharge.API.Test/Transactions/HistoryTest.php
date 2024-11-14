<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class HistoryTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de consulta bem-sucedida do histórico de transações
    public function testSuccessfulTransactionHistory()
    {
        // ID de um usuário válido
        $userId = '1'; // Substitua com um ID de usuário válido que tenha transações no banco

        // Envia a requisição GET para o endpoint de consulta de histórico de transações
        $response = $this->client->get('api/transactions/history.php', [
            'query' => ['user_id' => $userId] // Passa o ID do usuário como parâmetro na URL
        ]);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém uma lista de transações
        $this->assertArrayHasKey('transactions', $data);
        $this->assertIsArray($data['transactions']);
        
        // Verifica se a primeira transação tem os campos esperados
        if (count($data['transactions']) > 0) {
            $this->assertArrayHasKey('transaction_id', $data['transactions'][0]);
            $this->assertArrayHasKey('amount', $data['transactions'][0]);
            $this->assertArrayHasKey('date', $data['transactions'][0]);
            $this->assertArrayHasKey('status', $data['transactions'][0]);
        }
    }

    // Teste de consulta de histórico de transações com ID de usuário inválido
    public function testTransactionHistoryWithInvalidUserId()
    {
        // ID de usuário inválido
        $userId = '9999'; // ID inválido para teste

        // Envia a requisição GET para o endpoint de consulta de histórico de transações
        $response = $this->client->get('api/transactions/history.php', [
            'query' => ['user_id' => $userId], // Passa o ID do usuário como parâmetro na URL
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 404 Not Found
        $this->assertEquals(404, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave de erro e uma mensagem adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User not found.', $data['error']);
    }

    // Teste de consulta de histórico de transações sem passar o ID do usuário
    public function testTransactionHistoryWithoutUserId()
    {
        // Envia a requisição GET para o endpoint de consulta de histórico de transações
        $response = $this->client->get('api/transactions/history.php', [
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave de erro e uma mensagem adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User ID is required.', $data['error']);
    }
}
?>