<?php

declare(strict_types=1);

namespace Chop;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use RuntimeException;
use Throwable;

final class Kernel
{
    public readonly ConfiguresSocketConnection $connection;
    /** @var string[] */
    private const POSSIBLE_SOCKET_FILE_PATTERNS = [
        '~/.sock/*.sock',
        '/var/run/php*.sock',
        '/var/run/php/*.sock',
    ];

    public function __construct(string $host = null)
    {
        if ($host === null) {
            $host = $this->locateSocketPath() ?? '127.0.0.1:9000';
        }

        if (file_exists($host)) {
            $this->connection = new UnixDomainSocket(
                socketPath: $host,
                connectTimeout: 5000,
                readWriteTimeout: 12000
            );
        } else {
            [$host, $port] = $this->determineHostAndPort($host);

            $this->connection = new NetworkSocket(
                host: $host,
                port: (int) $port,
                connectTimeout: 5000,
                readWriteTimeout: 12000
            );
        }
    }

    public function reset(): bool
    {
        $file = sprintf("%s/chop-%s.php", sys_get_temp_dir(), bin2hex(random_bytes(16)));

        file_put_contents($file, '<?= opcache_reset();');
        chmod($file, 0666);

        try {
            $response = (new Client())->sendRequest($this->connection, new PostRequest($file, ''));

            unlink($file);

            return (bool) $response->getBody();
        } catch (Throwable $exception) {
            unlink($file);

            throw new RuntimeException(
                sprintf(
                    'FastCGI error: %s (host: %s)',
                    $exception->getMessage(),
                    $this->connection->getSocketAddress()
                ),
                $exception->getCode()
            );
        }
    }

    private function locateSocketPath(): ?string
    {
        $socketPath = null;

        foreach (self::POSSIBLE_SOCKET_FILE_PATTERNS as $possibleSocketFilePattern) {
            $matchingFiles = glob($possibleSocketFilePattern);

            if (!$matchingFiles) {
                continue;
            }

            if (file_exists($matchingFiles[0])) {
                $socketPath = $matchingFiles[0];
            }

            if (count($matchingFiles) > 1) {
                foreach ($matchingFiles as $file) {
                    if (
                        str_contains($file, (string)PHP_MAJOR_VERSION) &&
                        str_contains($file, (string)PHP_MINOR_VERSION)
                    ) {
                        $socketPath = $file;
                        break;
                    }
                }
            }
        }
        
        return $socketPath;
    }

    /** @return string[] */
    private function determineHostAndPort(string $host): array
    {
        $last = strrpos($host, ':') ?: null;
        $port = substr($host, $last + 1, strlen($host));
        $host = substr($host, 0, $last);

        $IPv6 = '/^(?:[A-F0-9]{0,4}:){1,7}[A-F0-9]{0,4}$/';
        if (preg_match($IPv6, $host) === 1) {
            $host = "[$host]";
        }

        return [$host, $port];
    }
}
