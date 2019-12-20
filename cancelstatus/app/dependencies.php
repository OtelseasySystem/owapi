<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');

            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        db::class => function ($c) {
            $settings = $c->get('database');
            $pdo = new PDO("mysql:host=" . $settings['host'] . ";dbname=" . $settings['dbname'],
                $settings['user'], $settings['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
            $pdo->query('SET NAMES utf8');
            return $pdo;
        },
        logwriter::class => function($c) {
            $configs = include('../config.php');

            $settings = $c->get('settings');
            $loggerSettings = $settings['logger'];
            $logger = new \Monolog\Logger('CANCELSTATUS-API'); 
            $file_handler = new \Monolog\Handler\StreamHandler($configs['log']['path']);
            $logger->pushHandler($file_handler);
            return $logger;
        }
    ]);
    
};
