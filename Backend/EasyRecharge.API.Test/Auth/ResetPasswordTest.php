<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ResetPasswordTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/',
        ]);
    }

    public function testSuccessfulPasswordReset()
    {
        $validToken = 'valid-reset-token';
        $newPassword = 'newValidPassword123!';

        $response = $this->client->post('api/auth/reset-password.php', [
            'json' => [
                'token' => $validToken,
                'new_password' => $newPassword,
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Password reset successful', $data['message']);
    }

    public function testResetPasswordWithInvalidToken()
    {
        $invalidToken = 'invalid-reset-token';
        $newPassword = 'newValidPassword123!';

        $response = $this->client->post('api/auth/reset-password.php', [
            'json' => [
                'token' => $invalidToken,
                'new_password' => $newPassword,
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Invalid or expired token: ' . 'Invalid token.', $data['message']);
    }

    public function testResetPasswordWithWeakPassword()
    {
        $validToken = 'valid-reset-token';
        $weakPassword = '123';  

        $response = $this->client->post('api/auth/reset-password.php', [
            'json' => [
                'token' => $validToken,
                'new_password' => $weakPassword,
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('new_password', $data['errors']);
    }

    public function testResetPasswordWithMissingPassword()
    {
        $validToken = 'valid-reset-token';

        $response = $this->client->post('api/auth/reset-password.php', [
            'json' => [
                'token' => $validToken,
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('new_password', $data['errors']);
    }

    public function testResetPasswordWithSamePassword()
    {
        $validToken = 'valid-reset-token';
        $newPassword = 'existingPassword123!';  

        
        $response = $this->client->post('api/auth/reset-password.php', [
            'json' => [
                'token' => $validToken,
                'new_password' => $newPassword,
            ]
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('New password cannot be the same as the current password', $data['message']);
    }
}
?>
