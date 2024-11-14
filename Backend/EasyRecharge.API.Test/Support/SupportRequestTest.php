<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class SupportRequestTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // URL base da API
        ]);
    }

    // Teste de envio de uma solicitação de suporte com dados válidos
    public function testCreateSupportRequest()
    {
        // Dados da solicitação de suporte
        $data = [
            'subject' => 'Problema com recarga',
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta', // Prioridade da solicitação
        ];

        // Simula um token de autenticação válido (token de um usuário autenticado)
        $token = 'valid_user_token';

        // Envia a requisição POST para criar uma solicitação de suporte
        $response = $this->client->post('api/support/request.php', [
            'json' => $data, // Dados da solicitação de suporte
            'headers' => [
                'Authorization' => "Bearer {$token}", // Passando o token de autorização
            ]
        ]);

        // Verifica se o status da resposta é 201 Created
        $this->assertEquals(201, $response->getStatusCode());

        // Decodifica a resposta JSON
        $responseData = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'id' da solicitação de suporte
        $this->assertArrayHasKey('id', $responseData);

        // Verifica se os dados retornados correspondem ao esperado
        $this->assertEquals($data['subject'], $responseData['subject']);
        $this->assertEquals($data['message'], $responseData['message']);
        $this->assertEquals($data['priority'], $responseData['priority']);
    }

    // Teste de erro ao enviar uma solicitação de suporte sem fornecer o assunto
    public function testCreateSupportRequestWithoutSubject()
    {
        // Dados da solicitação de suporte com o 'subject' ausente
        $data = [
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta',
        ];

        // Simula um token de autenticação válido
        $token = 'valid_user_token';

        // Envia a requisição POST para criar uma solicitação de suporte sem o assunto
        $response = $this->client->post('api/support/request.php', [
            'json' => $data,
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $responseData = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem apropriada
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Subject is required', $responseData['error']);
    }

    // Teste de erro ao tentar criar uma solicitação de suporte com um token inválido
    public function testCreateSupportRequestWithInvalidToken()
    {
        // Dados da solicitação de suporte com um token inválido
        $data = [
            'subject' => 'Problema com recarga',
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta',
        ];

        // Simula um token inválido
        $token = 'invalid_token';

        // Envia a requisição POST para criar uma solicitação de suporte com um token inválido
        $response = $this->client->post('api/support/request.php', [
            'json' => $data,
            'headers' => [
                'Authorization' => "Bearer {$token}",
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

    // Teste de erro ao tentar criar uma solicitação de suporte sem um token
    public function testCreateSupportRequestWithoutToken()
    {
        // Dados da solicitação de suporte sem token
        $data = [
            'subject' => 'Problema com recarga',
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta',
        ];

        // Envia a requisição POST para criar uma solicitação de suporte sem o token
        $response = $this->client->post('api/support/request.php', [
            'json' => $data, // Dados da solicitação de suporte
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $responseData = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Authorization token is required', $responseData['error']);
    }
}
?>