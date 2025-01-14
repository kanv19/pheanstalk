<?php

declare(strict_types=1);

namespace Enqueue\Pheanstalk;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use LogicException;
use Pheanstalk\Pheanstalk;

class PheanstalkConnectionFactory implements ConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Pheanstalk
     */
    private $connection;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost with default settings.
     *
     * [
     *     'host'  => 'localhost',
     *     'port'  => 11300,
     *     'timeout' => null,
     *     'persisted' => true,
     * ]
     *
     * or
     *
     * beanstalk: - connects to localhost:11300
     * beanstalk://host:port
     *
     * @param array|string $config
     */
    public function __construct($config = 'beanstalk:')
    {
        if (empty($config) || 'beanstalk:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) { } else {
            throw new LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * @return PheanstalkContext
     */
    public function createContext(): Context
    {
        return new PheanstalkContext($this->establishConnection());
    }

    /**
     * @return Pheanstalk
     */
    private function establishConnection(): Pheanstalk
    {
        if (false == $this->connection) {
            $this->connection = Pheanstalk::create(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout']
            );
        }

        return $this->connection;
    }

    /**
     * @param string $dsn
     * @return array
     */
    private function parseDsn(string $dsn): array
    {
        $dsnConfig = parse_url($dsn);
        if (false === $dsnConfig) {
            throw new LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        $dsnConfig = array_replace([
            'scheme' => null,
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
        ], $dsnConfig);

        if ('beanstalk' !== $dsnConfig['scheme']) {
            throw new LogicException(
                sprintf(
                    'The given DSN scheme "%s" is not supported. Could be "beanstalk" only.',
                    $dsnConfig['scheme']
                )
            );
        }

        $query = [];
        if ($dsnConfig['query']) {
            parse_str($dsnConfig['query'], $query);
        }

        return array_replace($query, [
            'port' => $dsnConfig['port'],
            'host' => $dsnConfig['host'],
        ]);
    }

    /**
     * @return array
     */
    private function defaultConfig(): array
    {
        return [
            'host' => 'localhost',
            'port' => Pheanstalk::DEFAULT_PORT,
            'timeout' => 10,
            'persisted' => true,
        ];
    }
}
