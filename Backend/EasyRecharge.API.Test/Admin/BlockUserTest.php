<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class BlockUserTest extends TestCase
{
    private $client;
    private $adminToken;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);

        // Supondo que haja um endpoint de login que retorna um token de administrador válido
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'admin@example.com', // Substitua com credenciais válidas de administrador
                'password' => 'adminpassword'
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        $this->adminToken = $data['token'];
    }

    // Teste de bloqueio de usuário com ID válido
    public function testBlockUserWithValidId()
    {
        $userId = 1; // Substitua com um ID válido de um usuário para teste

        $response = $this->client->post("api/admin/block-user.php", [
            'headers' => [
                'Authorization' => "Bearer {$this->adminToken}"
            ],
            'json' => [
                'user_id' => $userId,
                'action' => 'block'
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('User blocked successfully', $data['message']);
    }

    // Teste de bloqueio de usuário com ID inválido
    public function testBlockUserWithInvalidId()
    {
        $userId = 9999; // ID inexistente no banco de dados

        $response = $this->client->post("api/admin/block-user.php", [
            'headers' => [
                'Authorization' => "Bearer {$this->adminToken}"
            ],
            'json' => [
                'user_id' => $userId,
                'action' => 'block'
            ]
        ]);

        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('User not found', $data['message']);
    }

    // Teste de bloqueio de usuário sem fornecer o ID
    public function testBlockUserWithoutId()
    {
        $response = $this->client->post("api/admin/block-user.php", [
            'headers' => [
                'Authorization' => "Bearer {$this->adminToken}"
            ],
            'json' => [
                'action' => 'block'
            ]
        ]);

        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Valid user_id is required', $data['message']);
    }

    // Teste de ação inválida
    public function testBlockUserWithInvalidAction()
    {
        $userId = 1; // ID válido para teste

        $response = $this->client->post("api/admin/block-user.php", [
            'headers' => [
                'Authorization' => "Bearer {$this->adminToken}"
            ],
            'json' => [
                'user_id' => $userId,
                'action' => 'freeze' // Ação inválida
            ]
        ]);

        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals("Action must be 'block' or 'unblock'", $data['message']);
    }
}
