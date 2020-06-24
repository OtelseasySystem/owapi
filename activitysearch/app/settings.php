<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    $configs = include('../config.php');
    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            'displayErrorDetails' => true, // Should be set to false in production
            'logger' => [
                'name' => 'slim-app',
                'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                'level' => Logger::DEBUG,
            ],
        ],
        "database" => [            
             "host" => $configs['db']['host'],         
             "dbname" => $configs['db']['dbname'],        
             "user" => $configs['db']['user'],            
             "pass" => $configs['db']['password']        
         ],
         "jwt" => [
            'secret' => $configs['secret']
        ]
    ]);
};
