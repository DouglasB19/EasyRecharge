<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TicketStatusTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testGetTicketStatusWithValidId()
    {
        
        $token = 'valid_user_token';
        $ticketId = 1; 

        
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('ticket_id', $responseData);
        $this->assertEquals($ticketId, $responseData['ticket_id']);

        
        $this->assertArrayHasKey('status', $responseData);
    }

    
    public function testGetTicketStatusWithInvalidId()
    {
        
        $token = 'valid_user_token';
        $ticketId = 99999; 

        
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(404, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Ticket not found', $responseData['error']);
    }

    
    public function testGetTicketStatusWithoutToken()
    {
        
        $ticketId = 1;

        
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}");

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Authorization token is required', $responseData['error']);
    }

    
    public function testGetTicketStatusWithInvalidToken()
    {
        
        $token = 'invalid_token';
        $ticketId = 1;

        
        $response = $this->client->get("api/support/ticket-status.php/{$ticketId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(401, $response->getStatusCode());

        
        $responseData = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['error']);
    }
}
?>