<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TransactionSummaryTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testGetTransactionSummary()
    {
        
        $token = 'valid_admin_token';

        
        $response = $this->client->get('api/admin/transaction-summary.php', [
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('total_transactions', $data);
        $this->assertArrayHasKey('total_amount', $data);

        
        $this->assertGreaterThanOrEqual(0, $data['total_transactions']);
        $this->assertGreaterThanOrEqual(0, $data['total_amount']);
    }

    
    public function testGetTransactionSummaryWithInvalidToken()
    {
        
        $token = 'invalid_token';

        
        $response = $this->client->get('api/admin/transaction-summary.php', [
            'headers' => [
                'Authorization' => "Bearer {$token}", 
            ]
        ]);

        
        $this->assertEquals(401, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    
    public function testGetTransactionSummaryWithoutToken()
    {
        
        $response = $this->client->get('api/admin/transaction-summary.php');

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Authorization token is required', $data['error']);
    }
}
?>