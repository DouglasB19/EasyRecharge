<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class PasswordResetRequestTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/',
        ]);
    }

    public function testSuccessfulPasswordResetRequest()
    {
        $response = $this->client->post('api/auth/password-reset-request.php', [
            'json' => [
                'email' => 'johndoe@example.com',
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        // Depuração: Imprimir o conteúdo da resposta
        $body = (string) $response->getBody();
        echo "Response Body: " . $body . "\n";

        // Verificar os cabeçalhos de resposta
        $headers = $response->getHeaders();
        echo "Response Headers: \n";
        print_r($headers);

        // Tentar decodificar a resposta JSON
        $data = json_decode($body, true);

        // Verifica se a resposta não é null e é um array
        $this->assertNotNull($data, 'Expected response to be a valid JSON.');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Password reset email sent.', $data['message']);
    }

    public function testPasswordResetRequestWithUnregisteredEmail()
    {
        $response = $this->client->post('api/auth/password-reset-request.php', [
            'json' => [
                'email' => 'unregistered@example.com',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());

        // Depuração: Imprimir o conteúdo da resposta
        $body = (string) $response->getBody();
        echo "Response Body: " . $body . "\n";

        // Verificar os cabeçalhos de resposta
        $headers = $response->getHeaders();
        echo "Response Headers: \n";
        print_r($headers);

        // Tentar decodificar a resposta JSON
        $data = json_decode($body, true);
        
        $this->assertNotNull($data, 'Expected response to be a valid JSON.');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Email or phone not found', $data['message']);
    }

    public function testPasswordResetRequestWithInvalidEmailFormat()
    {
        $response = $this->client->post('api/auth/password-reset-request.php', [
            'json' => [
                'email' => 'invalid-email',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(422, $response->getStatusCode());

        // Depuração: Imprimir o conteúdo da resposta
        $body = (string) $response->getBody();
        echo "Response Body: " . $body . "\n";

        // Verificar os cabeçalhos de resposta
        $headers = $response->getHeaders();
        echo "Response Headers: \n";
        print_r($headers);

        // Tentar decodificar a resposta JSON
        $data = json_decode($body, true);

        // Verifica se a resposta é um array válido
        $this->assertNotNull($data, 'Expected response to be a valid JSON.');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('email', $data['message']);
    }

    public function testPasswordResetRequestWithMissingEmail()
    {
        $response = $this->client->post('api/auth/password-reset-request.php', [
            'json' => [],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());

        // Depuração: Imprimir o conteúdo da resposta
        $body = (string) $response->getBody();
        echo "Response Body: " . $body . "\n";

        // Verificar os cabeçalhos de resposta
        $headers = $response->getHeaders();
        echo "Response Headers: \n";
        print_r($headers);

        // Tentar decodificar a resposta JSON
        $data = json_decode($body, true);

        // Verifica se a resposta é um array válido
        $this->assertNotNull($data, 'Expected response to be a valid JSON.');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('email', $data['message']);
    }
}
?>
