<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class HistoryTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulTransactionHistory()
    {
        
        $userId = '1'; 

        
        $response = $this->client->get('api/transactions/history.php', [
            'query' => ['user_id' => $userId] 
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('transactions', $data);
        $this->assertIsArray($data['transactions']);
        
        
        if (count($data['transactions']) > 0) {
            $this->assertArrayHasKey('transaction_id', $data['transactions'][0]);
            $this->assertArrayHasKey('amount', $data['transactions'][0]);
            $this->assertArrayHasKey('date', $data['transactions'][0]);
            $this->assertArrayHasKey('status', $data['transactions'][0]);
        }
    }

    
    public function testTransactionHistoryWithInvalidUserId()
    {
        
        $userId = '9999'; 

        
        $response = $this->client->get('api/transactions/history.php', [
            'query' => ['user_id' => $userId], 
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(404, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User not found.', $data['error']);
    }

    
    public function testTransactionHistoryWithoutUserId()
    {
        
        $response = $this->client->get('api/transactions/history.php', [
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User ID is required.', $data['error']);
    }
}
?>