<?php

namespace Arakne\Tests\Spinneret\Security\User;

use Arakne\Spinneret\Security\User\ObjectUserHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ObjectUserHandlerTest extends TestCase
{
    public function testFromArray(): void
    {
        $handler = new ObjectUserHandler();

        $data = [
            'id' => 1,
            'username' => 'john.doe',
        ];

        $user = $handler->fromArray($data);

        $this->assertIsObject($user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('john.doe', $user->username);
    }

    public function testToArray(): void
    {
        $handler = new ObjectUserHandler();

        $user = (object) [
            'id' => 1,
            'username' => 'john.doe',
        ];

        $data = $handler->toArray($user);

        $this->assertSame([
            'id' => 1,
            'username' => 'john.doe',
        ], $data);
    }

    #[Test]
    public function refresh()
    {
        $handler = new ObjectUserHandler();

        $user = (object) [
            'id' => 1,
            'username' => 'john.doe',
        ];

        $this->assertSame($user, $handler->refresh($user));
    }
}
