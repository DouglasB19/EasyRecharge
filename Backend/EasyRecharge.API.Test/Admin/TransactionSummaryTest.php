<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TransactionSummaryTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // URL base da API
        ]);
    }

    // Teste de obtenção do resumo das transações para um administrador válido
    public function testGetTransactionSummary()
    {
        // Simula um token de administrador válido
        $token = 'valid_admin_token';

        // Envia a requisição GET para obter o resumo das transações
        $response = $this->client->get('api/admin/transaction-summary.php', [
            'headers' => [
                'Authorization' => "Bearer {$token}", // Passando o token de autorização
            ]
        ]);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém os campos esperados, como 'total_transactions' e 'total_amount'
        $this->assertArrayHasKey('total_transactions', $data);
        $this->assertArrayHasKey('total_amount', $data);

        // Verifica se os valores das transações são números positivos
        $this->assertGreaterThanOrEqual(0, $data['total_transactions']);
        $this->assertGreaterThanOrEqual(0, $data['total_amount']);
    }

    // Teste de erro ao tentar obter o resumo das transações com um token inválido
    public function testGetTransactionSummaryWithInvalidToken()
    {
        // Simula um token inválido
        $token = 'invalid_token';

        // Envia a requisição GET para obter o resumo das transações com um token inválido
        $response = $this->client->get('api/admin/transaction-summary.php', [
            'headers' => [
                'Authorization' => "Bearer {$token}", // Passando o token de autorização
            ]
        ]);

        // Verifica se o status da resposta é 401 Unauthorized
        $this->assertEquals(401, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    // Teste de erro ao tentar obter o resumo das transações sem fornecer um token
    public function testGetTransactionSummaryWithoutToken()
    {
        // Envia a requisição GET para obter o resumo das transações sem fornecer o token
        $response = $this->client->get('api/admin/transaction-summary.php');

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Authorization token is required', $data['error']);
    }
}
?>