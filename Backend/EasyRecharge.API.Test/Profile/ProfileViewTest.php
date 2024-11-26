<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ProfileViewTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulProfileView()
    {
        
        $userId = 1;

        
        $response = $this->client->get("api/profile/index.php/{$userId}");

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('user_id', $data);
        $this->assertEquals($userId, $data['user_id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('phone', $data);
    }

    
    public function testProfileViewWithInvalidUserId()
    {
        
        $userId = 9999; 

        
        $response = $this->client->get("api/profile/index.php/{$userId}");

        
        $this->assertEquals(404, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User not found', $data['error']);
    }

    
    public function testProfileViewWithoutUserId()
    {
        
        $response = $this->client->get('api/profile/index.php/');

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User ID is required', $data['error']);
    }
}
?>