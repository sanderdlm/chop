<?php

namespace Chop\Test;

use Chop\Kernel;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function testIPv4NetworkConnection(): void
    {
        $kernel = new Kernel('127.0.0.1:9000');

        $this->assertInstanceOf(NetworkSocket::class, $kernel->connection);
        $this->assertEquals('tcp://127.0.0.1:9000', $kernel->connection->getSocketAddress());
    }

    public function testIPv6NetworkConnection(): void
    {
        $kernel = new Kernel('::9000');

        $this->assertInstanceOf(NetworkSocket::class, $kernel->connection);
        $this->assertEquals('tcp://[:]:9000', $kernel->connection->getSocketAddress());
    }

    public function testSocketConnection(): void
    {
        $socketFolder = getenv('HOME') . '/.sock';

        mkdir($socketFolder);
        file_put_contents($socketFolder . '/pseudo.sock', '');

        $kernel = new Kernel($socketFolder . '/pseudo.sock');

        $this->assertInstanceOf(UnixDomainSocket::class, $kernel->connection);
        $this->assertEquals('unix://' . $socketFolder . '/pseudo.sock', $kernel->connection->getSocketAddress());

        unlink($socketFolder . '/pseudo.sock');
        rmdir($socketFolder);
    }

    public function testHostDetectionWithPhpVersion(): void
    {
        $socketFolder = getenv('HOME') . '/.sock';

        mkdir($socketFolder);
        file_put_contents($socketFolder . '/pseudo82.sock', '');
        file_put_contents($socketFolder . '/pseudo80.sock', '');
        file_put_contents($socketFolder . '/pseudo.sock', '');
        $kernel = new Kernel();

        $this->assertEquals('unix://' . $socketFolder . '/pseudo82.sock', $kernel->connection->getSocketAddress());

        unlink($socketFolder . '/pseudo82.sock');
        unlink($socketFolder . '/pseudo80.sock');
        unlink($socketFolder . '/pseudo.sock');
        rmdir($socketFolder);
    }
}
