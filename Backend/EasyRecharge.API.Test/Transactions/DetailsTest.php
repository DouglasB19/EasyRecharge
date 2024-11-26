<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class DetailsTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulTransactionDetails()
    {
        
        $transactionId = '1'; 

        
        $response = $this->client->get('api/transactions/details.php/' . $transactionId);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('transaction_id', $data);
        $this->assertArrayHasKey('amount', $data);
        $this->assertArrayHasKey('date', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('user_id', $data);
    }

    
    public function testTransactionDetailsWithInvalidId()
    {
        
        $transactionId = '9999'; 

        
        $response = $this->client->get('api/transactions/details.php/' . $transactionId, [
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(404, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Transaction not found.', $data['error']);
    }

    
    public function testTransactionDetailsWithoutId()
    {
        
        $response = $this->client->get('api/transactions/details.php', [
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Transaction ID is required.', $data['error']);
    }
}
?>