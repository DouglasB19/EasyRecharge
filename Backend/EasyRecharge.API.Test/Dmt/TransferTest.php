<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TransferTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de transferência bem-sucedida
    public function testSuccessfulTransfer()
    {
        // Dados válidos para a transferência
        $senderId = '1'; // ID do usuário remetente
        $receiverId = '2'; // ID do usuário destinatário
        $amount = 500; // Valor da transferência
        $paymentMethod = 'bank_transfer'; // Método de pagamento (exemplo: transferência bancária)

        // Envia a requisição POST para o endpoint de transferência
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
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
        $this->assertEquals('Transfer was successful.', $data['message']);
    }

    // Teste de transferência com saldo insuficiente
    public function testTransferWithInsufficientBalance()
    {
        // Dados válidos para a transferência, mas com saldo insuficiente
        $senderId = '1'; // ID do usuário remetente
        $receiverId = '2'; // ID do usuário destinatário
        $amount = 1000; // Valor da transferência maior que o saldo disponível
        $paymentMethod = 'bank_transfer';

        // Envia a requisição POST para o endpoint de transferência
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
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
        $this->assertEquals('Insufficient balance to complete the transfer.', $data['error']);
    }

    // Teste de transferência com valor negativo
    public function testTransferWithNegativeAmount()
    {
        // Dados inválidos para a transferência (valor negativo)
        $senderId = '1'; // ID do usuário remetente
        $receiverId = '2'; // ID do usuário destinatário
        $amount = -100; // Valor da transferência inválido (negativo)
        $paymentMethod = 'bank_transfer';

        // Envia a requisição POST para o endpoint de transferência
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
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

    // Teste de transferência com método de pagamento inválido
    public function testTransferWithInvalidPaymentMethod()
    {
        // Dados inválidos para o método de pagamento
        $senderId = '1'; // ID do usuário remetente
        $receiverId = '2'; // ID do usuário destinatário
        $amount = 500; // Valor da transferência
        $paymentMethod = 'invalid_payment_method'; // Método de pagamento inválido

        // Envia a requisição POST para o endpoint de transferência
        $response = $this->client->post('api/dmt/transfer.php', [
            'json' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
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