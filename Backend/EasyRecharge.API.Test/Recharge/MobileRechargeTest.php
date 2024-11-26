<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;

class MobileRechargeTest extends TestCase
{
    private $client;
    private $jwtToken;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/',
        ]);

        
        $response = $this->client->post('api/auth/login.php', [
            'json' => [
                'email' => 'johndoe@example.com',  
                'password' => '12345678'           
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->jwtToken = $data['jwt'] ?? null;
        $this->assertNotNull($this->jwtToken, 'Failed to obtain JWT token');
    }

    public function testSuccessfulMobileRecharge()
    {
        $response = $this->client->post('api/recharge/mobile.php', [
            'json' => [
                'phone_number' => '+5511998765432',
                'country_code' => 'BR',
                'amount' => 50,
                'operator_id' => 1, 
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwtToken,
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Mobile recharge successful', $data['message']);
    }

    public function testMobileRechargeWithInvalidPhoneNumber()
    {
        $response = $this->client->post('api/recharge/mobile.php', [
            'json' => [
                'phone_number' => '12345',
                'country_code' => 'BR',
                'amount' => 50,
                'operator_id' => 1,
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwtToken,
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Invalid phone number for the given country', $data['message']);
    }

    public function testMobileRechargeWithInvalidAmount()
    {
        $response = $this->client->post('api/recharge/mobile.php', [
            'json' => [
                'phone_number' => '+5511998765432',
                'country_code' => 'BR',
                'amount' => -50,
                'operator_id' => 1,
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwtToken,
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Invalid recharge amount. Please enter between 10 and 500.', $data['message']);
    }

    public function testMobileRechargeWithInsufficientBalance()
    {
        $response = $this->client->post('api/recharge/mobile.php', [
            'json' => [
                'phone_number' => '+5511998765432',
                'country_code' => 'BR',
                'amount' => 500,
                'operator_id' => 1,
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwtToken,
            ],
            'http_errors' => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Insufficient balance for the recharge', $data['message']);
    }
}
?>
