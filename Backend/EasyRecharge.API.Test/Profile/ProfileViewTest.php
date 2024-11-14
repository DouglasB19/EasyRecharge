<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ProfileViewTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de visualização do perfil com dados válidos
    public function testSuccessfulProfileView()
    {
        // ID do usuário válido para o teste
        $userId = 1;

        // Envia a requisição GET para o endpoint de visualização do perfil
        $response = $this->client->get("api/profile/index.php/{$userId}");

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém os dados esperados do perfil
        $this->assertArrayHasKey('user_id', $data);
        $this->assertEquals($userId, $data['user_id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('phone', $data);
    }

    // Teste de visualização do perfil com ID de usuário inválido
    public function testProfileViewWithInvalidUserId()
    {
        // ID de usuário inválido para o teste
        $userId = 9999; // Supondo que não exista esse ID no banco de dados

        // Envia a requisição GET para o endpoint de visualização do perfil
        $response = $this->client->get("api/profile/index.php/{$userId}");

        // Verifica se o status da resposta é 404 Not Found
        $this->assertEquals(404, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User not found', $data['error']);
    }

    // Teste de visualização do perfil sem fornecer o ID do usuário
    public function testProfileViewWithoutUserId()
    {
        // Envia a requisição GET para o endpoint de visualização do perfil sem o ID do usuário
        $response = $this->client->get('api/profile/index.php/');

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User ID is required', $data['error']);
    }
}
?>