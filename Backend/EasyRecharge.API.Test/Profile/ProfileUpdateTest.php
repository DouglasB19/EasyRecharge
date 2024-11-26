<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ProfileUpdateTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulProfileUpdate()
    {
        
        $userId = 1; 
        $name = 'John Doe Updated'; 
        $phone = '+5511987654321'; 

        
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'name' => $name,
                'phone' => $phone
            ]
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Profile updated successfully', $data['message']);
    }

    
    public function testProfileUpdateWithInvalidPhone()
    {
        
        $userId = 1;
        $name = 'John Doe';
        $phone = 'invalid-phone'; 

        
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'name' => $name,
                'phone' => $phone
            ]
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid phone number format', $data['error']);
    }

    
    public function testProfileUpdateWithoutName()
    {
        
        $userId = 1;
        $phone = '+5511987654321'; 

        
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'phone' => $phone
            ]
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Name is required', $data['error']);
    }

    
    public function testProfileUpdateWithoutPhone()
    {
        
        $userId = 1;
        $name = 'John Doe';

        
        $response = $this->client->put('api/profile/update.php', [
            'json' => [
                'user_id' => $userId,
                'name' => $name
            ]
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Phone is required', $data['error']);
    }

    
    public function testProfileUpdateWithoutData()
    {
        
        $response = $this->client->put('api/profile/update.php', [
            'json' => []
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Name and phone are required', $data['error']);
    }
}
?>