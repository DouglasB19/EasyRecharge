<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class DepositTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de depósito bem-sucedido
    public function testSuccessfulDeposit()
    {
        // Dados válidos para o depósito
        $userId = '1'; // ID do usuário que está fazendo o depósito
        $amount = 500; // Valor do depósito
        $paymentMethod = 'bank_transfer'; // Método de pagamento (exemplo: transferência bancária)

        // Envia a requisição POST para o endpoint de depósito
        $response = $this->client->post('api/aeps/deposit.php', [
            'json' => [
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]
        ]);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém uma mensagem de sucesso
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Deposit was successful.', $data['message']);
    }

    // Teste de depósito com valor negativo
    public function testDepositWithNegativeAmount()
    {
        // Dados inválidos para o depósito (valor negativo)
        $userId = '1';
        $amount = -100; // Valor do depósito inválido (negativo)
        $paymentMethod = 'bank_transfer';

        // Envia a requisição POST para o endpoint de depósito
        $response = $this->client->post('api/aeps/deposit.php', [
            'json' => [
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ],
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 422 Unprocessable Entity
        $this->assertEquals(422, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém um erro para o valor negativo
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('amount', $data['errors']);
    }

    // Teste de depósito com método de pagamento inválido
    public function testDepositWithInvalidPaymentMethod()
    {
        // Dados inválidos para o método de pagamento
        $userId = '1';
        $amount = 100;
        $paymentMethod = 'invalid_payment_method'; // Método de pagamento inválido

        // Envia a requisição POST para o endpoint de depósito
        $response = $this->client->post('api/aeps/deposit.php', [
            'json' => [
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ],
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 422 Unprocessable Entity
        $this->assertEquals(422, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém um erro para o método de pagamento inválido
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('payment_method', $data['errors']);
    }

    // Teste de depósito com saldo insuficiente
    public function testDepositWithInsufficientBalance()
    {
        // Dados válidos, mas com saldo insuficiente para o depósito
        $userId = '1';
        $amount = 1000; // Valor do depósito maior que o saldo disponível
        $paymentMethod = 'bank_transfer';

        // Envia a requisição POST para o endpoint de depósito
        $response = $this->client->post('api/aeps/deposit.php', [
            'json' => [
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ],
            'http_errors' => false, // Não lançar erro em caso de falha
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém a mensagem de erro para saldo insuficiente
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Insufficient balance to complete the deposit.', $data['error']);
    }
}
?>