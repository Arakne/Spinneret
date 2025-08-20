<?php

namespace Arakne\Tests\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FactoryHelperTraitTest extends TestCase
{
    #[Test]
    public function call()
    {
        $fn = new FunctionServiceFactory(strtoupper(...));
        $call = $fn->call(['foo']);
        $this->assertSame($fn, $call->function);
        $this->assertSame(['foo'], $call->arguments);
    }

    #[Test]
    public function fcc()
    {
        $fn = new FunctionServiceFactory(strtoupper(...));
        $fcc = $fn->fcc();

        $this->assertSame($fn, $fcc->function);
    }
}
