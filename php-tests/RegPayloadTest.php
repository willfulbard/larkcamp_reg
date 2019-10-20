<?php
namespace LarkRegistration\Tests;

use PHPUnit\Framework\TestCase;

require 'public/php/RegPayload.php';

class RegistrationPayloadClassTest extends TestCase
{
    public function testCreate(): void
    {
        $rp = new \Responses\RegPayload('"test"');
        $this->assertInstanceOf(\Responses\RegPayload::class, $rp);
    }
}
