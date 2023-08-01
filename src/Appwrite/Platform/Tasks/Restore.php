<?php

namespace Appwrite\Platform\Tasks;

use Exception;
use Utopia\App;
use Utopia\Platform\Action;
use Utopia\CLI\Console;
use Utopia\Storage\Device;
use Utopia\Storage\Device\DOSpaces;
use Utopia\Storage\Device\Local;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Restore extends Action
{
    //  docker compose exec appwrite-backup db-restore --cloud=false --filename=2023-07-19_09:25:11.tar.gz --project=db_fra1_02 --folder=daily
    // todo: Carefully double check this is not a production value!!!!!!!!!!!!!!!
    // todo: it will be erased!!!!
    //protected string $containerName = 'appwrite-mariadb';
    protected string $host = 'mariadb';
    protected int $processors = 4;

    public static function getName(): string
    {
        return 'restore';
    }

    public function __construct()
    {
        $this
            ->desc('Restore a DB')
            ->param('id', '', new Text(100), 'Folder Identifier')
            ->param('cloud', null, new WhiteList(['true', 'false'], true), 'Take file from cloud?')
            ->param('project', null, new WhiteList(['db_fra1_02'], true), 'From _APP_CONNECTIONS_DB_PROJECT')
            ->callback(fn ($id, $cloud, $project) => $this->action($id, $cloud, $project));
    }

    public function action(string $id, string $cloud, string $project): void
    {
        $this->checkEnvVariables();
        $filename = $id . '.tar.gz';
        Backup::log('--- Restore Start ' . $filename . ' --- ');
        $start = microtime(true);
        $cloud = $cloud === 'true';

        $local = new Local(Backup::$backups . '/' . $project . '/full/' . $id);
        $files = $local->getRoot() . '/files';

        if ($cloud) {
            $local = new Local(Backup::$backups . '/downloads/' . $id);
            $this->download($project, $filename, $local);
            $files = $local->getRoot() . '/files';
            if (!file_exists($files) && !mkdir($files, 0755, true)) {
                Console::error('Error creating directory: ' . $files);
                Console::exit();
            }

            $file = $local->getPath($filename);

            $stdout = '';
            $stderr = '';
            $cmd = 'tar -xzf ' . $file . ' -C ' . $files;
            Backup::log($cmd);
            Console::execute($cmd, '', $stdout, $stderr);
            if (!empty($stderr)) {
                Console::error($stderr);
                Console::exit();
            }

            if (!file_exists($file)) {
                Console::error('Restore file not found: ' . $file);
                Console::exit();
            }
        }

        $this->decompress($files);
        $this->prepare($files);

        Backup::log("Restore Finish in " . (microtime(true) - $start) . " seconds");
    }

    public function download(string $project, string $filename, Device $local)
    {
        if (!file_exists($local->getRoot()) && !mkdir($local->getRoot(), 0755, true)) {
            Console::error('Error creating directory: ' . $local->getRoot());
            Console::exit();
        }

        $s3 = new DOSpaces($project . '/full', App::getEnv('_DO_SPACES_ACCESS_KEY'), App::getEnv('_DO_SPACES_SECRET_KEY'), App::getEnv('_DO_SPACES_BUCKET_NAME'), App::getEnv('_DO_SPACES_REGION'));

        try {
            $path = $s3->getPath($filename);

            if (!$s3->exists($path)) {
                Console::error('File: ' . $path . ' does not exist on cloud');
                Console::exit();
            }

            $file = $local->getPath($filename);
            Backup::log('Downloading: ' . $file);

            if (!$s3->transfer($path, $file, $local)) {
                Console::error('Error Downloading ' . $file);
                Console::exit();
            }
        } catch (Exception $e) {
            Console::error($e->getMessage());
            Console::exit();
        }
    }

    public function decompress(string $target)
    {
        if (!file_exists($target)) {
            Console::error('decompress error directory not found: ' . $target);
            Console::exit();
        }

        $args = [
            '--user=root',
            '--password=' . App::getEnv('_APP_DB_ROOT_PASS'),
            '--host=' . $this->host,
            '--decompress',
            '--parallel=' . $this->processors,
            '--compress-threads=' . $this->processors,
            '--target-dir=' . $target,
        ];

        $stdout = '';
        $stderr = '';
        $cmd = 'docker exec appwrite-xtrabackup xtrabackup ' . implode(' ', $args);
        Backup::log($cmd);
        Console::execute($cmd, '', $stdout, $stderr);
        if (!empty($stderr)) {
            Console::error($stderr);
            //Console::exit();
        }

        if (!str_contains($stderr, 'completed OK!')) {
            Console::error('Error decompressing: ' . $target);
            Console::exit();
        }
    }

    public function prepare(string $target)
    {
        if (!file_exists($target)) {
            Console::error('prepare error directory not found: ' . $target);
            Console::exit();
        }

        $args = [
            '--user=root',
            '--password=' . App::getEnv('_APP_DB_ROOT_PASS'),
            '--host=' . $this->host,
            '--prepare',
            '--target-dir=' . $target,
        ];

        $stdout = '';
        $stderr = '';
        $cmd = 'docker exec appwrite-xtrabackup xtrabackup ' . implode(' ', $args);
        Backup::log($cmd);
        Console::execute($cmd, '', $stdout, $stderr);
        if (!empty($stderr)) {
            Console::error($stderr);
            //Console::exit();
        }

        if (!str_contains($stderr, 'completed OK!')) {
            Console::error('Error preparing: ' . $target);
            Console::exit();
        }
    }

//    public function action(string $filename, string $cloud, string $project, string $folder): void
//    {
//        $this->checkEnvVariables();
//
//        Backup::log('--- Restore Start ' . $filename . ' --- ');
//        $start = microtime(true);
//
//        $cloud = $cloud === 'true';
//        $file = Backup::$backups . '/' . $project . '/' . $folder . '/' . $filename;
//        $s3 = new DOSpaces('/v1/' . $project . '/' . $folder, App::getEnv('_DO_SPACES_ACCESS_KEY'), App::getEnv('_DO_SPACES_SECRET_KEY'), App::getEnv('_DO_SPACES_BUCKET_NAME'), App::getEnv('_DO_SPACES_REGION'));
//        $download = new Local(Backup::$backups . '/downloads');
//
//        if (!file_exists($download->getRoot()) && !mkdir($download->getRoot(), 0755, true)) {
//            Console::error('Error creating directory: ' . $download->getRoot());
//            Console::exit();
//        }
//
//        if ($cloud) {
//            try {
//                $path = $s3->getPath($filename);
//
//                if (!$s3->exists($path)) {
//                    Console::error('File: ' . $path . ' does not exist on cloud');
//                    Console::exit();
//                }
//
//                $file = $download->getPath($filename);
//                Backup::log('Downloading: ' . $file);
//
//                if (!$s3->transfer($path, $file, $download)) {
//                    Console::error('Error Downloading ' . $file);
//                    Console::exit();
//                }
//            } catch (Exception $e) {
//                Console::error($e->getMessage());
//                Console::exit();
//            }
//        }
//
//        if (!file_exists($file)) {
//            Console::error('Restore file not found: ' . $file);
//            Console::exit();
//        }
//
//        Backup::stopMysqlContainer($this->containerName);
//
//        $stdout = '';
//        $stderr = '';
//        //$cmd = 'mv ' . Backup::$mysqlDirectory . '/* ' . ' ' . $original . '/';
//        // todo: do we care about original?
//        $cmd = 'rm -r ' . Backup::$mysqlDirectory . '/*';
//        Backup::log($cmd);
//        Console::execute($cmd, '', $stdout, $stderr);
//        Backup::log($stdout);
//        if (!empty($stderr)) {
//            Console::error($stderr);
//            Console::exit();
//        }
//
//        $stdout = '';
//        $stderr = '';
//        $cmd = 'tar -xzf ' . $file . ' -C ' . Backup::$mysqlDirectory;
//        Backup::log($cmd);
//        Console::execute($cmd, '', $stdout, $stderr);
//        if (!empty($stderr)) {
//            Console::error($stderr);
//            Console::exit();
//        }
//
//        Backup::startMysqlContainer($this->containerName);
//
//        Backup::log("Restore Finish in " . (microtime(true) - $start) . " seconds");
//    }

    public function checkEnvVariables(): void
    {
        foreach (
            [
                '_DO_SPACES_BUCKET_NAME',
                '_DO_SPACES_ACCESS_KEY',
                '_DO_SPACES_SECRET_KEY',
                '_DO_SPACES_REGION',
                '_APP_DB_ROOT_PASS'
            ] as $env
        ) {
            if (empty(App::getEnv($env))) {
                Console::error('Can\'t read ' . $env);
                Console::exit();
            }
        }
    }
}
