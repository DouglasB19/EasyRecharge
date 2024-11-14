<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TicketStatusTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // URL base da API
        ]);
    }

    // Teste de consulta do status de um ticket com ID válido
    public function testGetTicketStatusWithValidId()
    {
        // Simula um token de autenticação válido
        $token = 'valid_user_token';
        $ticketId = 1; // ID de um ticket existente

        // Envia uma requisição GET para consultar o status do ticket com ID 1
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}", // Passando o token de autorização
            ]
        ]);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $responseData = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'ticket_id'
        $this->assertArrayHasKey('ticket_id', $responseData);
        $this->assertEquals($ticketId, $responseData['ticket_id']);

        // Verifica se a resposta contém a chave 'status' do ticket
        $this->assertArrayHasKey('status', $responseData);
    }

    // Teste de erro ao tentar consultar o status de um ticket com ID inválido
    public function testGetTicketStatusWithInvalidId()
    {
        // Simula um token de autenticação válido
        $token = 'valid_user_token';
        $ticketId = 99999; // ID de um ticket inexistente

        // Envia uma requisição GET para consultar o status do ticket com ID inválido
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}", // Passando o token de autorização
            ]
        ]);

        // Verifica se o status da resposta é 404 Not Found
        $this->assertEquals(404, $response->getStatusCode());

        // Decodifica a resposta JSON
        $responseData = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem apropriada
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Ticket not found', $responseData['error']);
    }

    // Teste de erro ao tentar consultar o status de um ticket sem fornecer o token de autenticação
    public function testGetTicketStatusWithoutToken()
    {
        // ID de um ticket válido
        $ticketId = 1;

        // Envia uma requisição GET sem o token de autenticação
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}");

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $responseData = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Authorization token is required', $responseData['error']);
    }

    // Teste de erro ao tentar consultar o status de um ticket com token inválido
    public function testGetTicketStatusWithInvalidToken()
    {
        // Simula um token inválido
        $token = 'invalid_token';
        $ticketId = 1;

        // Envia uma requisição GET para consultar o status do ticket com token inválido
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}", // Passando o token de autenticação inválido
            ]
        ]);

        // Verifica se o status da resposta é 401 Unauthorized
        $this->assertEquals(401, $response->getStatusCode());

        // Decodifica a resposta JSON
        $responseData = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }
}
?>