<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TransactionStatusTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulTransactionStatus()
    {
        
        $transactionId = '123456'; 

        
        $response = $this->client->get('api/dmt/transaction-status.php/' . $transactionId);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('transaction_id', $data);
        $this->assertEquals($transactionId, $data['transaction_id']);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('message', $data);
    }

    
    public function testTransactionStatusWithInvalidId()
    {
        
        $transactionId = 'invalid123'; 

        
        $response = $this->client->get('api/dmt/transaction-status.php/' . $transactionId, [
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(404, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Transaction not found.', $data['error']);
    }

    
    public function testTransactionStatusWithInvalidFormat()
    {
        
        $transactionId = 'abc123'; 

        
        $response = $this->client->get('api/dmt/transaction-status.php/' . $transactionId, [
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid transaction ID format.', $data['error']);
    }
}
?>