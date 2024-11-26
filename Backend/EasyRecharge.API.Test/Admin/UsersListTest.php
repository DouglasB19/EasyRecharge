<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class UsersListTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testGetUsersList()
    {
        
        $token = 'valid_admin_token';

        
        $response = $this->client->get('api/admin/users.php', [
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('users', $data);
        $this->assertIsArray($data['users']);

        
        foreach ($data['users'] as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayHasKey('email', $user);
        }
    }

    
    public function testGetUsersListWithInvalidToken()
    {
        
        $token = 'invalid_token';

        
        $response = $this->client->get('api/admin/users.php', [
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(401, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    
    public function testGetUsersListWithoutToken()
    {
        
        $response = $this->client->get('api/admin/users.php');

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Authorization token is required', $data['error']);
    }
}
?>