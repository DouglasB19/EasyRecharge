<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class LoginTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/',
        ]);
    }

    public function testLoginWithValidCredentials()
    {
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'johndoe@example.com',
                'password' => '12345678',
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('jwt', $data);
        $this->assertNotEmpty($data['jwt'], 'JWT token should not be empty');
    }

    public function testLoginWithInvalidCredentials()
    {
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'johndoe@example.com',
                'password' => 'wrongpassword',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid email or password', $data['error']);
    }

    public function testLoginWithMissingFields()
    {
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'johndoe@example.com',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Password is required', $data['error']);
    }

    public function testLoginWithPhoneNumber()
    {
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'phone' => '+5511998765432',
                'password' => '12345678',
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('jwt', $data);
        $this->assertNotEmpty($data['jwt'], 'JWT token should not be empty');
    }

    public function testLoginWithInvalidEmailFormat()
    {
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'invalidemailformat',
                'password' => '12345678',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid email format', $data['error']);
    }
}
?>
