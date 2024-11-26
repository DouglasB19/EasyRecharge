<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TransferTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulTransfer()
    {
        
        $senderId = '1'; 
        $receiverId = '2'; 
        $amount = 500; 
        $paymentMethod = 'bank_transfer'; 

        
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Transfer was successful.', $data['message']);
    }

    
    public function testTransferWithInsufficientBalance()
    {
        
        $senderId = '1'; 
        $receiverId = '2'; 
        $amount = 1000; 
        $paymentMethod = 'bank_transfer';

        
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ],
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Insufficient balance to complete the transfer.', $data['error']);
    }

    
    public function testTransferWithNegativeAmount()
    {
        
        $senderId = '1'; 
        $receiverId = '2'; 
        $amount = -100; 
        $paymentMethod = 'bank_transfer';

        
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ],
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(422, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('amount', $data['errors']);
    }

    
    public function testTransferWithInvalidPaymentMethod()
    {
        
        $senderId = '1'; 
        $receiverId = '2'; 
        $amount = 500; 
        $paymentMethod = 'invalid_payment_method'; 

        
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ],
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(422, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('payment_method', $data['errors']);
    }
}
?>