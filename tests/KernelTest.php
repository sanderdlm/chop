<?php

namespace Jug\Test;

use Analogo\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function testConnectionCreation(): void
    {
        $socketFile = new Kernel();

        $socketFile->reset();
    }
}
