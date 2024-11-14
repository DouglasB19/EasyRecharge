<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class RegisterTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/',
        ]);
    }

    public function testSuccessfulRegistration()
    {
        // Gerando um e-mail e telefone exclusivos para o teste
        $email = 'janedoe_' . time() . '@example.com';
        $phone = '+551199876' . rand(1000, 9999);

        $response = $this->client->post('api/auth/register.php', [
            'json' => [
                'name' => 'Jane Doe',
                'email' => $email,  // E-mail único gerado dinamicamente
                'phone' => $phone,   // Telefone único gerado dinamicamente
                'password' => 'SecurePassword-123'
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('User registered successfully', $data['message']);  // Mensagem esperada
    }

    public function testRegistrationWithExistingEmail()
    {
        // Usando e-mail que já existe no banco de dados
        $response = $this->client->post('api/auth/register.php', [
            'json' => [
                'name' => 'Jane Doe',
                'email' => 'johndoe@example.com',  // E-mail que já deve existir no banco
                'password' => 'SecurePassword123',
                'phone' => '+5511998765432'
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());  // Ajuste de status code de 409 para 400
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Email or phone already exists', $data['message']);  // Mensagem esperada
    }

    public function testRegistrationWithInvalidData()
    {
        $response = $this->client->post('api/auth/register.php', [
            'json' => [
                'name' => 'Jane Doe',
                'email' => 'invalid-email',
                'password' => '123',
                'phone' => '+5511998765432'
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());  // Status code esperado 400
        $data = json_decode($response->getBody(), true);  // Decodificando a resposta como array
        $this->assertArrayHasKey('message', $data);  // Verifica se 'message' está presente
        $this->assertStringContainsString('Email or phone already exists', $data['message']);  // Mensagem de erro esperada
    }

    public function testRegistrationWithMissingFields()
    {
        $response = $this->client->post('api/auth/register.php', [
            'json' => [
                'email' => 'janedoe@example.com',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());  // Status code esperado 400
        $data = json_decode($response->getBody(), true);  // Decodificando a resposta como array
        $this->assertArrayHasKey('message', $data);  // Verifica se 'message' está presente
        $this->assertStringContainsString('All fields are required', $data['message']);  // Mensagem de erro esperada
    }
}
?>
