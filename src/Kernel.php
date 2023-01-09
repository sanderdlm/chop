<?php

declare(strict_types=1);

namespace Analogo;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use RuntimeException;

final class Kernel
{
    private ConfiguresSocketConnection $connection;
    private string $host;
    /** @var string[] */
    private const POSSIBLE_SOCKET_FILE_PATTERNS = [
        '/var/run/php*.sock',
        '/var/run/php/*.sock',
        '~/.sock/*.sock',
    ];

    public function __construct(string $host = null)
    {
        if ($host === null) {
            foreach (self::POSSIBLE_SOCKET_FILE_PATTERNS as $possibleSocketFilePattern) {
                $matchingFiles = glob($possibleSocketFilePattern);

                if (!$matchingFiles) {
                    continue;
                }

                if (file_exists($matchingFiles[0])) {
                    $host = $matchingFiles[0];
                }

                if (count($matchingFiles) > 1) {
                    foreach ($matchingFiles as $file) {
                        if (str_contains($file, (string) PHP_MAJOR_VERSION) &&
                            str_contains($file, (string) PHP_MINOR_VERSION)) {
                            $host = $file;
                            break;
                        }
                    }
                }
            }

            if ($host === null) {
                $host = '127.0.0.1:9000';
            }
        }

        $this->host = $host;

        if (str_contains($host, ':')) {
            $parts = explode(':', $host);
            $host = end($parts);
            $port = reset($parts);

            $IPv6 = '/^(?:[A-F0-9]{0,4}:){1,7}[A-F0-9]{0,4}$/';
            if (preg_match($IPv6, $host) === 1) {
                // IPv6 addresses need to be surrounded by brackets
                // see: https://www.php.net/manual/en/function.stream-socket-client.php#refsect1-function.stream-socket-client-notes
                $host = "[$host]";
            }

            $this->connection = new NetworkSocket(
                host: $host,
                port: (int) $port,
                connectTimeout: 5000,
                readWriteTimeout:120000
            );
        } else {
            $this->connection = new UnixDomainSocket(
                socketPath: $host,
                connectTimeout: 5000,
                readWriteTimeout: 120000
            );
        }
    }

    public function reset(): void
    {
        $file = $this->createResetScript();

        try {
            $client = new Client();
            $request = new PostRequest($file, '');
            $client->sendRequest($this->connection, $request);

            unlink($file);
        } catch (\Throwable $exception) {
            unlink($file);

            throw new RuntimeException(
                sprintf('FastCGI error: %s (host: %s)', $exception->getMessage(), $this->host),
                $exception->getCode(),
                $exception
            );
        }
    }

    protected function createResetScript(): string
    {
        $resetScript = <<<RESET
<?php
opcache_reset();

RESET;

        $file = sprintf("%s/analogo-%s.php", sys_get_temp_dir(), bin2hex(random_bytes(16)));

        file_put_contents($file, $resetScript);
        chmod($file, 0666);

        return $file;
    }
}
