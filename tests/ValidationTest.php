<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../functions/validation.php';

class ValidationTest extends TestCase
{
    public function testValidateInteger()
    {
        $this->assertTrue(validateInteger(10, 1, 20));
        $this->assertTrue(validateInteger("15", 1, 20));
        $this->assertFalse(validateInteger("abc", 1, 20));
        $this->assertFalse(validateInteger(25, 1, 20));
        $this->assertFalse(validateInteger(0, 1, 20));
    }

    public function testValidateString()
    {
        $this->assertTrue(validateString("hello", 1, 10));
        $this->assertFalse(validateString("hello world", 1, 10));
        $this->assertFalse(validateString("", 1, 10));
    }

    public function testValidateEmail()
    {
        $this->assertTrue(validateEmail("test@example.com"));
        $this->assertFalse(validateEmail("invalid-email"));
    }

    public function testValidateDate()
    {
        $this->assertTrue(validateDate("2026-05-10"));
        $this->assertFalse(validateDate("10-05-2026"));
        $this->assertFalse(validateDate("2026-13-45"));
    }
}
