<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function extension_loaded;

class ConfigProvider
{
    public function __invoke() : array
    {
        $config = PHP_SAPI === 'cli' && extension_loaded('swoole')
            ? ['dependencies' => $this->getDependencies()]
            : [];

        $config['mezzio-swoole'] = $this->getDefaultConfig();

        return $config;
    }

    public function getDefaultConfig() : array
    {
        return [
            'swoole-http-server' => [
                'options' => [
                    // We set a default for this. Without one, Swoole\Http\Server
                    // defaults to the value of `ulimit -n`. Unfortunately, in
                    // virtualized or containerized environments, this often
                    // reports higher than the host container allows. 1024 is a
                    // sane default; users should check their host system, however,
                    // and set a production value to match.
                    'max_conn' => 1024,
                ],
            ],
        ];
    }

    public function getDependencies() : array
    {
        return [
            'factories'  => [
                Command\ReloadCommand::class          => Command\ReloadCommandFactory::class,
                Command\StartCommand::class           => Command\StartCommandFactory::class,
                Command\StatusCommand::class          => Command\StatusCommandFactory::class,
                Command\StopCommand::class            => Command\StopCommandFactory::class,
                Log\AccessLogInterface::class         => Log\AccessLogFactory::class,
                PidManager::class                     => PidManagerFactory::class,
                SwooleRequestHandlerRunner::class     => SwooleRequestHandlerRunnerFactory::class,
                ServerRequestInterface::class         => ServerRequestSwooleFactory::class,
                StaticResourceHandlerInterface::class => StaticResourceHandlerFactory::class,
                SwooleHttpServer::class               => HttpServerFactory::class,
            ],
            'aliases' => [
                RequestHandlerRunner::class           => SwooleRequestHandlerRunner::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Swoole\Command\ReloadCommand::class => Command\ReloadCommand::class,
                \Zend\Expressive\Swoole\Command\StartCommand::class => Command\StartCommand::class,
                \Zend\Expressive\Swoole\Command\StatusCommand::class => Command\StatusCommand::class,
                \Zend\Expressive\Swoole\Command\StopCommand::class => Command\StopCommand::class,
                \Zend\Expressive\Swoole\Log\AccessLogInterface::class => Log\AccessLogInterface::class,
                \Zend\Expressive\Swoole\PidManager::class => PidManager::class,
                \Zend\Expressive\Swoole\SwooleRequestHandlerRunner::class => SwooleRequestHandlerRunner::class,
                \Zend\Expressive\Swoole\StaticResourceHandlerInterface::class => StaticResourceHandlerInterface::class,
                \Zend\HttpHandlerRunner\RequestHandlerRunner::class => RequestHandlerRunner::class,
            ],
            'delegators' => [
                'Mezzio\WhoopsPageHandler' => [
                    WhoopsPrettyPageHandlerDelegator::class,
                ],
            ],
        ];
    }
}
