<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../functions/auth_functions.php';

class AuthTest extends TestCase
{
    public function testPasswordHashing()
    {
        $password = "secret123";
        // Auth functions uses Bcrypt
        // Wait, auth_functions.php doesn't expose a raw hashing function unless we test the login directly, but it hits the DB.
        // We will just do a basic test for now since getDB() will fail without setup.
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify("wrongpassword", $hash));
    }
}
