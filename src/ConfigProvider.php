<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Swoole\Event\SwooleWorkerDispatcher;
use Mezzio\Swoole\Event\SwooleWorkerDispatcherInterface;
use Mezzio\Swoole\Event\WorkerListenerProvider;
use Mezzio\Swoole\Event\WorkerListenerProviderInterface;
use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Mezzio\Swoole\HotCodeReload\Reloader;
use Mezzio\Swoole\HotCodeReload\ReloaderFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepository;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Zend\Expressive\Swoole\Command\ReloadCommand as LegacyReloadCommand;
use Zend\Expressive\Swoole\Command\StartCommand as LegacyStartCommand;
use Zend\Expressive\Swoole\Command\StatusCommand as LegacyStatusCommand;
use Zend\Expressive\Swoole\Command\StopCommand as LegacyStopCommand;
use Zend\Expressive\Swoole\HotCodeReload\FileWatcherInterface as LegacyFileWatcherInterface;
use Zend\Expressive\Swoole\HotCodeReload\Reloader as LegacyReloader;
use Zend\Expressive\Swoole\Log\AccessLogInterface as LegacyAccessLogInterface;
use zend\expressive\swoole\pidmanager as LegacyPidManager;
use Zend\Expressive\Swoole\StaticResourceHandler as LegacyStaticResourceHandler;
use Zend\Expressive\Swoole\StaticResourceHandlerInterface as LegacyStaticResourceHandlerInterface;
use Zend\Expressive\Swoole\SwooleRequestHandlerRunner as LegacySwooleRequestHandlerRunner;
use Zend\HttpHandlerRunner\RequestHandlerRunner as LegacyRequestHandlerRunner;

use function extension_loaded;

use const PHP_SAPI;

class ConfigProvider
{
    public function __invoke(): array
    {
        $config = PHP_SAPI === 'cli' && extension_loaded('swoole')
            ? ['dependencies' => $this->getDependencies()]
            : [];

        $config['mezzio-swoole'] = $this->getDefaultConfig();

        return $config;
    }

    public function getDefaultConfig(): array
    {
        return [
            'swoole-http-server' => [
                // A prefix for the process name of the master process and workers.
                // By default the master process will be named `mezzio-master`,
                // each http worker `mezzio-worker-n` and each task worker
                // `mezzio-task-worker-n` where n is the id of the worker
                'process-name' => 'mezzio',
                'options'      => [
                    // We set a default for this. Without one, Swoole\Http\Server
                    // defaults to the value of `ulimit -n`. Unfortunately, in
                    // virtualized or containerized environments, this often
                    // reports higher than the host container allows. 1024 is a
                    // sane default; users should check their host system, however,
                    // and set a production value to match.
                    'max_conn' => 1024,
                ],
                'static-files' => [
                    'enable' => true,
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories'  => [
                Command\ReloadCommand::class           => Command\ReloadCommandFactory::class,
                Command\StartCommand::class            => Command\StartCommandFactory::class,
                Command\StatusCommand::class           => Command\StatusCommandFactory::class,
                Command\StopCommand::class             => Command\StopCommandFactory::class,
                Log\AccessLogInterface::class          => Log\AccessLogFactory::class,
                Log\SwooleLoggerFactory::SWOOLE_LOGGER => Log\SwooleLoggerFactory::class,
                PidManager::class                      => PidManagerFactory::class,
                SwooleRequestHandlerRunner::class      => SwooleRequestHandlerRunnerFactory::class,
                ServerRequestInterface::class          => ServerRequestSwooleFactory::class,
                StaticResourceHandler::class           => StaticResourceHandlerFactory::class,
                StaticMappedResourceHandler::class     => StaticMappedResourceHandlerFactory::class,
                SwooleHttpServer::class                => HttpServerFactory::class,
                Reloader::class                        => ReloaderFactory::class,
                FileLocationRepository::class          => FileLocationRepositoryFactory::class,
            ],
            'invokables' => [
                InotifyFileWatcher::class => InotifyFileWatcher::class,
            ],
            'aliases'    => [
                RequestHandlerRunner::class             => SwooleRequestHandlerRunner::class,
                StaticResourceHandlerInterface::class   => StaticResourceHandler::class,
                FileWatcherInterface::class             => InotifyFileWatcher::class,
                FileLocationRepositoryInterface::class  => FileLocationRepository::class,
                WorkerListenerProviderInterface::class  => WorkerListenerProvider::class,

                // Legacy Zend Framework aliases
                LegacyReloadCommand::class                  => Command\ReloadCommand::class,
                LegacyStartCommand::class                   => Command\StartCommand::class,
                LegacyStatusCommand::class                  => Command\StatusCommand::class,
                LegacyStopCommand::class                    => Command\StopCommand::class,
                LegacyAccessLogInterface::class             => Log\AccessLogInterface::class,
                LegacyPidManager::class                     => PidManager::class,
                LegacySwooleRequestHandlerRunner::class     => SwooleRequestHandlerRunner::class,
                LegacyStaticResourceHandler::class          => StaticResourceHandler::class,
                LegacyReloader::class                       => Reloader::class,
                LegacyRequestHandlerRunner::class           => RequestHandlerRunner::class,
                LegacyStaticResourceHandlerInterface::class => StaticResourceHandlerInterface::class,
                LegacyFileWatcherInterface::class           => FileWatcherInterface::class,
            ],
            'delegators' => [
                'Mezzio\WhoopsPageHandler' => [
                    WhoopsPrettyPageHandlerDelegator::class,
                ],
            ],
        ];
    }
}
