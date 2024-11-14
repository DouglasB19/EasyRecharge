<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ChangePasswordTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Inicializa o cliente HTTP com a URL base da API
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', // Substitua pela URL correta da API
        ]);
    }

    // Teste de alteração bem-sucedida de senha
    public function testSuccessfulPasswordChange()
    {
        // Dados para a alteração de senha
        $userId = 1; // ID de usuário válido para teste
        $oldPassword = 'oldpassword'; // Senha antiga válida
        $newPassword = 'newpassword123'; // Nova senha válida

        // Envia a requisição PUT para o endpoint de alteração de senha
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        // Verifica se o status da resposta é 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém uma mensagem de sucesso
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Password updated successfully', $data['message']);
    }

    // Teste de alteração de senha com senha antiga incorreta
    public function testChangePasswordWithIncorrectOldPassword()
    {
        // Dados para a alteração de senha
        $userId = 1;
        $oldPassword = 'incorrectpassword'; // Senha antiga incorreta
        $newPassword = 'newpassword123';

        // Envia a requisição PUT para o endpoint de alteração de senha
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém uma mensagem de erro adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Old password is incorrect', $data['error']);
    }

    // Teste de alteração de senha com senha nova inválida (muito curta)
    public function testChangePasswordWithShortNewPassword()
    {
        // Dados para a alteração de senha
        $userId = 1;
        $oldPassword = 'oldpassword';
        $newPassword = '123'; // Nova senha muito curta

        // Envia a requisição PUT para o endpoint de alteração de senha
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém uma mensagem de erro sobre a senha ser muito curta
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('New password must be at least 6 characters long', $data['error']);
    }

    // Teste de alteração de senha com nova senha igual à antiga
    public function testChangePasswordWithSameNewPassword()
    {
        // Dados para a alteração de senha
        $userId = 1;
        $oldPassword = 'oldpassword';
        $newPassword = 'oldpassword'; // Nova senha igual à antiga

        // Envia a requisição PUT para o endpoint de alteração de senha
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém uma mensagem de erro adequada
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('New password cannot be the same as the old password', $data['error']);
    }

    // Teste de alteração de senha sem fornecer os parâmetros necessários
    public function testChangePasswordWithoutRequiredFields()
    {
        // Envia a requisição PUT para o endpoint de alteração de senha sem dados necessários
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => []
        ]);

        // Verifica se o status da resposta é 400 Bad Request
        $this->assertEquals(400, $response->getStatusCode());

        // Decodifica a resposta JSON
        $data = json_decode($response->getBody(), true);

        // Verifica se a resposta contém uma mensagem de erro sobre os parâmetros ausentes
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User ID, old password, and new password are required', $data['error']);
    }
}
?>