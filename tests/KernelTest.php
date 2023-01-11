<?php

namespace Jug\Test;

use Analogo\Kernel;
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
        $kernel = new Kernel(__DIR__ . '/pseudo.sock');

        $this->assertInstanceOf(UnixDomainSocket::class, $kernel->connection);
        $this->assertEquals('unix://' . __DIR__ . '/pseudo.sock', $kernel->connection->getSocketAddress());
    }

    public function testHostDetection(): void
    {
        $kernel = new Kernel();

        $this->assertInstanceOf(UnixDomainSocket::class, $kernel->connection);
    }
}