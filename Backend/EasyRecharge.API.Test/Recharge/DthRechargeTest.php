<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use \Firebase\JWT\JWT;

class DthRechargeTest extends TestCase
{
    protected $client;
    protected $jwt_token;

    // Método chamado antes de cada teste
    public function setUp(): void
    {
        // Inicializa o cliente HTTP Guzzle com o tempo de espera ajustado para 5 segundos
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/api/', // Caminho ajustado para a base URI
            'timeout' => 5.0, // Aumentando o tempo de espera para 5 segundos
        ]);
    }

    // Teste para login e obtenção do token JWT
    public function testLogin()
    {
        $response = $this->client->post('auth/login.php', [ // Verifique se o caminho do arquivo está correto
            'json' => [
                'email' => 'johndoe@example.com',
                'password' => '12345678' // Insira a senha correta do usuário para o teste
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        // Verifique se a resposta não está vazia e se o token JWT está presente
        $this->assertArrayHasKey('jwt', $data, 'JWT token is missing in the response');

        // Inspecione a resposta completa para debugar se o JWT estiver realmente presente
        var_dump($data); // Adicionando para inspecionar a resposta completa

        // Armazene o token JWT para os próximos testes
        $this->jwt_token = $data['jwt'];

        // Verifique se o token foi realmente atribuído
        $this->assertNotEmpty($this->jwt_token, 'JWT token is empty');
    }

    // Teste para DTH Recharge com token JWT
    public function testDthRechargeWithValidToken()
    {
        // Verifica se o token JWT foi obtido com sucesso
        $this->assertNotEmpty($this->jwt_token, 'JWT token is required for this test.');

        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890', // Número de conta válido para a recarga
                'amount' => 50, // Valor válido para a recarga
                'operator_id' => 1, // ID do operador válido
                'payment_method' => 'balance', // Método de pagamento: balance
                'customer_id' => 12345 // ID de cliente válido
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode()); // Verifica se a resposta foi bem-sucedida (código 200)
        $this->assertEquals('DTH recharge was successful', $data['message']); // Verifica se a mensagem de sucesso está presente
    }

    // Teste para recarga DTH sem um token JWT válido
    public function testDthRechargeWithoutToken()
    {
        $response = $this->client->post('recharge/dth.php', [
            'json' => [
                'account_number' => '1234567890',
                'amount' => 50,
                'operator_id' => 1,
                'payment_method' => 'balance',
                'customer_id' => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(401, $response->getStatusCode()); // Verifica o erro 401 para falta de autenticação
        $this->assertEquals('Authorization token is required', $data['message']); // Verifica a mensagem de erro
    }

    // Teste para recarga DTH com dados inválidos
    public function testDthRechargeWithInvalidAmount()
    {
        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890',
                'amount' => 5, // Valor inválido (menos que o valor mínimo de 10)
                'operator_id' => 1,
                'payment_method' => 'balance',
                'customer_id' => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(422, $response->getStatusCode()); // Verifica o erro 422
        $this->assertEquals('Invalid recharge amount. Please enter between 10 and 500.', $data['message']); // Verifica a mensagem de erro
    }

    // Teste para recarga DTH com operador inválido
    public function testDthRechargeWithInvalidOperator()
    {
        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890',
                'amount' => 50,
                'operator_id' => 9999, // Operador inválido
                'payment_method' => 'balance',
                'customer_id' => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(400, $response->getStatusCode()); // Verifica o erro 400
        $this->assertEquals('Invalid operator. Please select a valid operator.', $data['message']); // Verifica a mensagem de erro
    }

    // Teste para recarga DTH com saldo insuficiente
    public function testDthRechargeWithInsufficientBalance()
    {
        // Usar um token válido, mas com saldo insuficiente
        $response = $this->client->post('recharge/dth.php', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt_token
            ],
            'json' => [
                'account_number' => '1234567890',
                'amount' => 1000, // Valor maior do que o saldo disponível
                'operator_id' => 1,
                'payment_method' => 'balance',
                'customer_id' => 12345
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals(400, $response->getStatusCode()); // Verifica o erro 400
        $this->assertEquals('Insufficient balance for the recharge', $data['message']); // Verifica a mensagem de erro
    }
}
?>
