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
    public ConfiguresSocketConnection $connection;
    public string $host;
    /** @var string[] */
    private const POSSIBLE_SOCKET_FILE_PATTERNS = [
        '~/.sock/*.sock',
        '/var/run/php*.sock',
        '/var/run/php/*.sock',
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
                        if (
                            strpos($file, (string) PHP_MAJOR_VERSION) !== false &&
                            strpos($file, (string) PHP_MINOR_VERSION) !== false
                        ) {
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

        if (strpos($host, ':') !== false) {
            $last = strrpos($host, ':') ?: null;
            $port = substr($host, $last + 1, strlen($host));
            $host = substr($host, 0, $last);

            $IPv6 = '/^(?:[A-F0-9]{0,4}:){1,7}[A-F0-9]{0,4}$/';
            if (preg_match($IPv6, $host) === 1) {
                $host = "[$host]";
            }

            $this->connection = new NetworkSocket(
                $host,
                (int) $port,
                5000,
                120000
            );
        } else {
            $this->connection = new UnixDomainSocket(
                $host,
                5000,
                120000
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
                sprintf('FastCGI error: %s (host: %s)', $exception->getMessage(), $this->host),
                $exception->getCode()
            );
        }
    }
}
