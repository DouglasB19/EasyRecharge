<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class WithdrawTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de saque bem-sucedido
    public function testSuccessfulWithdraw()
    {
        // Dados válidos para o saque
        $userId = '1'; // ID do usuário que está fazendo o saque
        $amount = 500; // Valor do saque
        $paymentMethod = 'bank_transfer'; // Método de pagamento (exemplo: transferência bancária)

        // Envia a requisição POST para o endpoint de saque
        $response = $this->client->post('api/aeps/withdraw.php', [
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
        $this->assertEquals('Withdrawal was successful.', $data['message']);
    }

    // Teste de saque com valor negativo
    public function testWithdrawWithNegativeAmount()
    {
        // Dados inválidos para o saque (valor negativo)
        $userId = '1';
        $amount = -100; // Valor do saque inválido (negativo)
        $paymentMethod = 'bank_transfer';

        // Envia a requisição POST para o endpoint de saque
        $response = $this->client->post('api/aeps/withdraw.php', [
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

    // Teste de saque com saldo insuficiente
    public function testWithdrawWithInsufficientBalance()
    {
        // Dados válidos, mas com saldo insuficiente para o saque
        $userId = '1';
        $amount = 1000; // Valor do saque maior que o saldo disponível
        $paymentMethod = 'bank_transfer';

        // Envia a requisição POST para o endpoint de saque
        $response = $this->client->post('api/aeps/withdraw.php', [
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
        $this->assertEquals('Insufficient balance to complete the withdrawal.', $data['error']);
    }

    // Teste de saque com método de pagamento inválido
    public function testWithdrawWithInvalidPaymentMethod()
    {
        // Dados inválidos para o método de pagamento
        $userId = '1';
        $amount = 100;
        $paymentMethod = 'invalid_payment_method'; // Método de pagamento inválido

        // Envia a requisição POST para o endpoint de saque
        $response = $this->client->post('api/aeps/withdraw.php', [
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
}
?>