<?php

namespace Arakne\Tests\Spinneret\Translation;

use Arakne\Spinneret\Translation\TranslatableMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class TranslatableMessageTest extends TestCase
{
    #[Test]
    public function translator()
    {
        $translator = new IdentityTranslator();
        $message = new TranslatableMessage('Hello, %name%!', ['%name%' => 'World']);

        $this->assertSame('Hello, World!', $message->trans($translator));
    }
}
