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
        
        $email = 'janedoe_' . time() . '@example.com';
        $phone = '+551199876' . rand(1000, 9999);

        $response = $this->client->post('api/auth/register.php', [
            'json' => [
                'name' => 'Jane Doe',
                'email' => $email,  
                'phone' => $phone,   
                'password' => 'SecurePassword-123'
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('User registered successfully', $data['message']);  
    }

    public function testRegistrationWithExistingEmail()
    {
        
        $response = $this->client->post('api/auth/register.php', [
            'json' => [
                'name' => 'Jane Doe',
                'email' => 'johndoe@example.com',  
                'password' => 'SecurePassword123',
                'phone' => '+5511998765432'
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());  
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Email or phone already exists', $data['message']);  
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

        $this->assertEquals(400, $response->getStatusCode());  
        $data = json_decode($response->getBody(), true);  
        $this->assertArrayHasKey('message', $data);  
        $this->assertStringContainsString('Email or phone already exists', $data['message']);  
    }

    public function testRegistrationWithMissingFields()
    {
        $response = $this->client->post('api/auth/register.php', [
            'json' => [
                'email' => 'janedoe@example.com',
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());  
        $data = json_decode($response->getBody(), true);  
        $this->assertArrayHasKey('message', $data);  
        $this->assertStringContainsString('All fields are required', $data['message']);  
    }
}
?>
