<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class WithdrawTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulWithdraw()
    {
        
        $userId = '1'; 
        $amount = 500; 
        $paymentMethod = 'bank_transfer'; 

        
        $response = $this->client->post('api/aeps/withdraw.php', [
            'json' => [
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Withdrawal was successful.', $data['message']);
    }

    
    public function testWithdrawWithNegativeAmount()
    {
        
        $userId = '1';
        $amount = -100; 
        $paymentMethod = 'bank_transfer';

        
        $response = $this->client->post('api/aeps/withdraw.php', [
            'json' => [
                'user_id' => $userId,
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

    
    public function testWithdrawWithInsufficientBalance()
    {
        
        $userId = '1';
        $amount = 1000; 
        $paymentMethod = 'bank_transfer';

        
        $response = $this->client->post('api/aeps/withdraw.php', [
            'json' => [
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ],
            'http_errors' => false, 
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Insufficient balance to complete the withdrawal.', $data['error']);
    }

    
    public function testWithdrawWithInvalidPaymentMethod()
    {
        
        $userId = '1';
        $amount = 100;
        $paymentMethod = 'invalid_payment_method'; 

        
        $response = $this->client->post('api/aeps/withdraw.php', [
            'json' => [
                'user_id' => $userId,
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