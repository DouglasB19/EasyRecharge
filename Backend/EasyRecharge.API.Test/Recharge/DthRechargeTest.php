<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class DthRechargeTest extends TestCase
{
    protected $client;
    protected $jwt_token;

    
    public function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/api/',
            'timeout'  => 5.0,
        ]);

        
        $response = $this->client->post('auth/login.php', [
            'json' => [
                'email'    => 'johndoe@example.com',
                'password' => '12345678'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('jwt', $data, 'JWT token is missing in the response');
        
        $this->jwt_token = $data['jwt'];
        $this->assertNotEmpty($this->jwt_token, 'JWT token is empty');
    }

    
    public function testDthRechargeWithValidToken()
    {
        $this->assertNotEmpty($this->jwt_token, 'JWT token is required for this test.');

        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890',
                'amount'         => 50,
                'operator_id'    => 1,
                'payment_method' => 'balance',
                'customer_id'    => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('DTH recharge was successful', $data['message']);
    }

    
    public function testDthRechargeWithoutToken()
    {
        $response = $this->client->post('recharge/dth.php', [
            'json' => [
                'account_number' => '1234567890',
                'amount'         => 50,
                'operator_id'    => 1,
                'payment_method' => 'balance',
                'customer_id'    => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Authorization token is required', $data['message']);
    }

    
    public function testDthRechargeWithInvalidAmount()
    {
        $this->assertNotEmpty($this->jwt_token, 'JWT token is required for this test.');

        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890',
                'amount'         => 5, 
                'operator_id'    => 1,
                'payment_method' => 'balance',
                'customer_id'    => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Invalid recharge amount. Please enter between 10 and 500.', $data['message']);
    }

    
    public function testDthRechargeWithInvalidOperator()
    {
        $this->assertNotEmpty($this->jwt_token, 'JWT token is required for this test.');

        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890',
                'amount'         => 50,
                'operator_id'    => 9999,
                'payment_method' => 'balance',
                'customer_id'    => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid operator. Please select a valid operator.', $data['message']);
    }

    
    public function testDthRechargeWithInsufficientBalance()
    {
        $this->assertNotEmpty($this->jwt_token, 'JWT token is required for this test.');

        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890',
                'amount'         => 1000, 
                'operator_id'    => 1,
                'payment_method' => 'balance',
                'customer_id'    => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Insufficient balance for the recharge', $data['message']);
    }
}
?>
