<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class BlockUserTest extends TestCase
{
    private $client;
    private $adminToken;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);

        
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'admin@example.com', 
                'password' => 'adminpassword'
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        $this->adminToken = $data['token'];
    }

    
    public function testBlockUserWithValidId()
    {
        $userId = 1; 

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

    
    public function testBlockUserWithInvalidId()
    {
        $userId = 9999; 

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

    
    public function testBlockUserWithInvalidAction()
    {
        $userId = 1; 

        $response = $this->client->post("api/admin/block-user.php", [
            'headers' => [
                'Authorization' => "Bearer {$this->adminToken}"
            ],
            'json' => [
                'user_id' => $userId,
                'action' => 'freeze' 
            ]
        ]);

        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals("Action must be 'block' or 'unblock'", $data['message']);
    }
}
