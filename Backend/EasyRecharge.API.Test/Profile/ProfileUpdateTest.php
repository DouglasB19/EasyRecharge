<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ProfileUpdateTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de atualização de perfil com dados válidos
    public function testSuccessfulProfileUpdate()
    {
        // Dados para a atualização do perfil
        $userId = 1; // ID do usuário válido para o teste
        $name = 'John Doe Updated'; // Novo nome
        $phone = '+5511987654321'; // Novo telefone

        // Envia a requisição PUT para o endpoint de atualização do perfil
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'name' => $name,
                'phone' => $phone
            ]
        ]);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'message' com a mensagem de sucesso
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Profile updated successfully', $data['message']);
    }

    // Teste de atualização de perfil com dados inválidos (telefone inválido)
    public function testProfileUpdateWithInvalidPhone()
    {
        // Dados para a atualização do perfil com telefone inválido
        $userId = 1;
        $name = 'John Doe';
        $phone = 'invalid-phone'; // Telefone inválido

        // Envia a requisição PUT para o endpoint de atualização do perfil
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'name' => $name,
                'phone' => $phone
            ]
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid phone number format', $data['error']);
    }

    // Teste de atualização de perfil sem fornecer o nome
    public function testProfileUpdateWithoutName()
    {
        // Dados para a atualização do perfil sem nome
        $userId = 1;
        $phone = '+5511987654321'; // Telefone válido

        // Envia a requisição PUT para o endpoint de atualização do perfil sem o nome
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'phone' => $phone
            ]
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Name is required', $data['error']);
    }

    // Teste de atualização de perfil sem fornecer o telefone
    public function testProfileUpdateWithoutPhone()
    {
        // Dados para a atualização do perfil sem telefone
        $userId = 1;
        $name = 'John Doe';

        // Envia a requisição PUT para o endpoint de atualização do perfil sem o telefone
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'name' => $name
            ]
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Phone is required', $data['error']);
    }

    // Teste de atualização de perfil sem fornecer nenhum dado
    public function testProfileUpdateWithoutData()
    {
        // Envia a requisição PUT para o endpoint de atualização do perfil sem dados
        $response = $this->client->put('api/profile/update.php', [
            'json' => []
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a chave 'error' com a mensagem de erro adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Name and phone are required', $data['error']);
    }
}
?>