<?php

require_once __DIR__ . '/init.php';


use Swoole\Runtime;
use Utopia\App;
use Utopia\Cache\Adapter\Sharding;
use Utopia\Cache\Cache;
use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Queue\Server;
use Utopia\Registry\Registry;


global $register;



Server::setResource('register', fn() => $register);

Server::setResource('dbForConsole', function (Cache $cache, Registry $register) {
    $pools = $register->get('pools');
    $dbAdapter = $pools
        ->get('console')
        ->pop()
        ->getResource()
    ;

    $database = new Database($dbAdapter, $cache);
    $database->setNamespace('console');

    return $database;
}, ['cache', 'register']);

Server::setResource('cache', function (Registry $register) {
    $pools = $register->get('pools');
    $list = Config::getParam('pools-cache', []);
    $adapters = [];

    foreach ($list as $value) {
        $adapters[] = $pools
            ->get($value)
            ->pop()
            ->getResource()
        ;
    }

    return new Cache(new Sharding($adapters));
}, ['register']);

App::setResource('logger', function ($register) {
    return $register->get('logger');
}, ['register']);


$pools = $register->get('pools');
$client = $pools
    ->get('queue')
    ->pop()
    ->getResource();


$workerNumber = swoole_cpu_num() * intval(App::getEnv('_APP_WORKER_PER_CORE', 6));
$workerNumber = 1;

Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
