<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;

class LogoutTest extends TestCase
{
    private $client;
    private $validToken;
    private $jwtSecretKey = 'your_jwt_secret_key'; 

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/',
        ]);

        
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'johndoe@example.com',
                'password' => '12345678'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->validToken = $data['jwt'];
    }

    public function testLogoutWithValidToken()
    {
        $response = $this->client->post('api/auth/logout.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->validToken,
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Logout successful', $data['message']);
    }

    public function testLogoutWithoutToken()
    {
        $response = $this->client->post('api/auth/logout.php', [
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Token is required', $data['message']);
    }

    public function testLogoutWithInvalidToken()
    {
        $response = $this->client->post('api/auth/logout.php', [
            'headers' => [
                'Authorization' => 'Bearer invalidtoken',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Access denied', $data['message']);
    }

    public function testLogoutWithExpiredToken()
    {
        
        $expiredPayload = [
            'iss' => 'http://localhost/EasyRecharge',
            'user_id' => 1,
            'email' => 'johndoe@example.com',
            'exp' => time() - 3600 
        ];
        
        $expiredToken = JWT::encode($expiredPayload, $this->jwtSecretKey, 'HS256');

        $response = $this->client->post('api/auth/logout.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $expiredToken,
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Access denied: Expired token', $data['message']);
    }
}
?>
