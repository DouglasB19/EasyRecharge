<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class SupportRequestTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testCreateSupportRequest()
    {
        
        $data = [
            'subject' => 'Problema com recarga',
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta', 
        ];

        
        $token = 'valid_user_token';

        
        $response = $this->client->post('api/support/request.php', [
            'json' => $data, 
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(201, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('id', $responseData);

        
        $this->assertEquals($data['subject'], $responseData['subject']);
        $this->assertEquals($data['message'], $responseData['message']);
        $this->assertEquals($data['priority'], $responseData['priority']);
    }

    
    public function testCreateSupportRequestWithoutSubject()
    {
        
        $data = [
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta',
        ];

        
        $token = 'valid_user_token';

        
        $response = $this->client->post('api/support/request.php', [
            'json' => $data,
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Subject is required', $responseData['error']);
    }

    
    public function testCreateSupportRequestWithInvalidToken()
    {
        
        $data = [
            'subject' => 'Problema com recarga',
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta',
        ];

        
        $token = 'invalid_token';

        
        $response = $this->client->post('api/support/request.php', [
            'json' => $data,
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        
        $this->assertEquals(401, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }

    
    public function testCreateSupportRequestWithoutToken()
    {
        
        $data = [
            'subject' => 'Problema com recarga',
            'message' => 'Estou enfrentando dificuldades ao tentar realizar uma recarga.',
            'priority' => 'Alta',
        ];

        
        $response = $this->client->post('api/support/request.php', [
            'json' => $data, 
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Authorization token is required', $responseData['error']);
    }
}
?>