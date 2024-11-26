<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ChangePasswordTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        
        $this->client = new Client([
            'base_uri' => 'http://localhost/EasyRecharge/Backend/EasyRecharge.API/', 
        ]);
    }

    
    public function testSuccessfulPasswordChange()
    {
        
        $userId = 1; 
        $oldPassword = 'oldpassword'; 
        $newPassword = 'newpassword123'; 

        
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        
        $this->assertEquals(200, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Password updated successfully', $data['message']);
    }

    
    public function testChangePasswordWithIncorrectOldPassword()
    {
        
        $userId = 1;
        $oldPassword = 'incorrectpassword'; 
        $newPassword = 'newpassword123';

        
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Old password is incorrect', $data['error']);
    }

    
    public function testChangePasswordWithShortNewPassword()
    {
        
        $userId = 1;
        $oldPassword = 'oldpassword';
        $newPassword = '123'; 

        
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('New password must be at least 6 characters long', $data['error']);
    }

    
    public function testChangePasswordWithSameNewPassword()
    {
        
        $userId = 1;
        $oldPassword = 'oldpassword';
        $newPassword = 'oldpassword'; 

        
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => [
                'user_id' => $userId,
                'old_password' => $oldPassword,
                'new_password' => $newPassword,
            ]
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('New password cannot be the same as the old password', $data['error']);
    }

    
    public function testChangePasswordWithoutRequiredFields()
    {
        
        $response = $this->client->put('api/profile/change-password.php', [
            'json' => []
        ]);

        
        $this->assertEquals(400, $response->getStatusCode());

        
        $data = json_decode($response->getBody(), true);

        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('User ID, old password, and new password are required', $data['error']);
    }
}
?>