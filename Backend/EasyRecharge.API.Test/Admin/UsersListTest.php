<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class UsersListTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // URL base da API
        ]);
    }

    // Teste de obtenção da lista de usuários para um administrador válido
    public function testGetUsersList()
    {
        // Simula um token de administrador válido
        $token = 'valid_admin_token';

        // Envia a requisição GET para obter a lista de usuários
        $response = $this->client->get('api/admin/users.php', [
            'headers' => [
                'Authorization' => "Bearer {$token}", // Passando o token de autorização
            ]
        ]);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'users' com uma lista de usuários
        $this->assertArrayHasKey('users', $data);
        $this->assertIsArray($data['users']);

        // Verifica se cada item na lista de usuários contém as chaves 'id', 'name', 'email'
        foreach ($data['users'] as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayHasKey('email', $user);
        }
    }

    // Teste de erro ao tentar obter a lista de usuários com um token inválido
    public function testGetUsersListWithInvalidToken()
    {
        // Simula um token inválido
        $token = 'invalid_token';

        // Envia a requisição GET para obter a lista de usuários com um token inválido
        $response = $this->client->get('api/admin/users.php', [
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

    // Teste de erro ao tentar obter a lista de usuários sem fornecer um token
    public function testGetUsersListWithoutToken()
    {
        // Envia a requisição GET para obter a lista de usuários sem fornecer o token
        $response = $this->client->get('api/admin/users.php');

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