<?php

namespace Appwrite\Platform\Workers;

use Appwrite\Extend\Exception;
use Utopia\CLI\Console;
use Utopia\Database\Database;
use Utopia\Database\DateTime;
use Utopia\Database\Document;
use Utopia\Database\Exception\NotFound;
use Utopia\Platform\Action;
use Utopia\Queue\Message;
use Utopia\System\System;

const METRIC_COLLECTION_LEVEL_STORAGE = 4;
const METRIC_DATABASE_LEVEL_STORAGE = 3;
const METRIC_PROJECT_LEVEL_STORAGE = 2;

class UsageDump extends Action
{
    protected array $stats = [];
    protected array $periods = [
        '1h' => 'Y-m-d H:00',
        '1d' => 'Y-m-d 00:00',
        'inf' => '0000-00-00 00:00'
    ];

    private static array $seenMetrics = [];

    public static function getName(): string
    {
        return 'usage-dump';
    }

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this
            ->inject('message')
            ->inject('getProjectDB')
            ->callback([$this, 'action']);
    }

    /**
     * @param Message $message
     * @param callable(Document): Database $getProjectDB
     * @return void
     * @throws Exception
     * @throws \Throwable
     */
    public function action(Message $message, callable $getProjectDB): void
    {
        $payload = $message->getPayload() ?? [];
        if (empty($payload)) {
            throw new Exception('Missing payload');
        }

        try {
            foreach ($payload['stats'] ?? [] as $stats) {
                static::$seenMetrics = [];

                $project = new Document($stats['project'] ?? []);
                $numberOfKeys = !empty($stats['keys']) ? \count($stats['keys']) : 0;
                $receivedAt = $stats['receivedAt'] ?? 'NONE';
                if ($numberOfKeys === 0) {
                    continue;
                }

                $dbForProject = $getProjectDB($project);
                $projectDocuments = [];

                //Console::log('['.DateTime::now().'] Id: '.$project->getId(). ' InternalId: '.$project->getInternalId(). ' Db: '.$project->getAttribute('database').' ReceivedAt: '.$receivedAt. ' Keys: '.$numberOfKeys . ' Started');
                $start = \microtime(true);

                foreach ($stats['keys'] ?? [] as $key => $value) {
                    if ($value == 0) {
                        continue;
                    }

                    foreach ($this->periods as $period => $format) {
                        $time = 'inf' === $period ? null : \date($format, \time());
                        $id = \md5("{$time}_{$period}_{$key}");

                        if (\str_contains($key, METRIC_DATABASES_STORAGE)) {
                            static::handleDatabaseStorage(
                                $id,
                                $key,
                                $time,
                                $period,
                                $dbForProject,
                                $projectDocuments,
                            );
                            continue;
                        }

                        static::addStatsDocument($projectDocuments, $id, $period, $time, $key, $value);
                    }
                }

                //\var_dump($projectDocuments);

                $dbForProject->createOrUpdateDocumentsWithIncrease(
                    collection: 'stats',
                    attribute: 'value',
                    documents: \array_values($projectDocuments)
                );

                $end = \microtime(true);
                //Console::log('['.DateTime::now().'] Id: '.$project->getId(). ' InternalId: '.$project->getInternalId(). ' Db: '.$project->getAttribute('database').' ReceivedAt: '.$receivedAt. ' Keys: '.$numberOfKeys. ' Time: '.($end - $start).'s');
            }
        } catch (\Exception $e) {
            Console::error('[' . DateTime::now() . '] Error processing stats: ' . $e->getMessage());
        }
    }

    private static function getUniqueKey(string $key, string $period, ?string $time): string
    {
        return "{$key}.{$period}.{$time}";
    }

    private static function handleDatabaseStorage(
        string $id,
        string $key,
        ?string $time,
        string $period,
        Database $dbForProject,
        array &$projectDocuments,
    ): void {
        $data = \explode('.', $key);
        $value = 0;
        $unique = static::getUniqueKey($key, $period, $time);

        if (isset(static::$seenMetrics[$unique])) {
            Console::log("[DUPLICATE CALL] handleDatabaseStorage() called twice for: {$unique}");
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        static::$seenMetrics[$unique] = true;

        try {
            if (isset($projectDocuments[$unique])) {
                $previousValue = $projectDocuments[$unique]['value'];
                Console::log("[PREVIOUS VALUE] Found in projectDocuments: {$previousValue} for {$unique}");
            } else {
                $previousValue = $dbForProject->getDocument('stats', $id)->getAttribute('value', 0);
                Console::log("[PREVIOUS VALUE] Fetched from DB: {$previousValue} for {$unique}");
            }
        } catch (\Exception) {
            $previousValue = 0;
            Console::log("[PREVIOUS VALUE] Defaulted to 0 for {$unique}");
        }

        switch (\count($data)) {
            case METRIC_COLLECTION_LEVEL_STORAGE:
                $databaseInternalId = $data[0];
                $collectionInternalId = $data[1];
                $collectionId = "database_{$databaseInternalId}_collection_{$collectionInternalId}";

                try {
                    $value = $dbForProject->getSizeOfCollection($collectionId);
                } catch (\Exception $e) {
                    if (!$e instanceof NotFound) {
                        throw $e;
                    }
                }

                $diff = $value - $previousValue;

                //Console::info('['.DateTime::now().'] Collection: '.$collectionId. ' Value: '.$value. ' PreviousValue: '.$previousValue. ' Diff: '.$diff);

                if ($diff <= 0) {
                    break;
                }

                $keys = [
                    $key,
                    \str_replace(['{databaseInternalId}'], [$data[0]], METRIC_DATABASE_ID_STORAGE),
                    METRIC_DATABASES_STORAGE
                ];

                \var_dump('[PROCESSING COLLECTION KEYS] ' . \json_encode($keys));

                foreach ($keys as $metric) {
                    static::addStatsDocument($projectDocuments, $id, $period, $time, $metric, $diff);
                }

                break;
            case METRIC_DATABASE_LEVEL_STORAGE:
                $databaseInternalId = $data[0];
                $databaseId = "database_{$databaseInternalId}";

                $collections = [];
                try {
                    $collections = $dbForProject->find($databaseId);
                } catch (\Exception $e) {
                    if (!$e instanceof NotFound) {
                        Console::error('[Error] Type: ' . get_class($e));
                        Console::error('[Error] Message: ' . $e->getMessage());
                        Console::error('[Error] File: ' . $e->getFile());
                        Console::error('[Error] Line: ' . $e->getLine());
                        Console::error('[Error] Trace: ' . $e->getTraceAsString());

                        throw $e;
                    }
                }

                foreach ($collections as $collection) {
                    $collectionId = "{$databaseId}_collection_{$collection->getInternalId()}";

                    try {
                        $value = $dbForProject->getSizeOfCollection($collectionId);
                    } catch (\Exception $e) {
                        if (!$e instanceof NotFound) {
                            throw $e;
                        }
                    }
                }

                $diff = $value - $previousValue;

                Console::info('['.DateTime::now().'] Database: '.$databaseId. ' Value: '.$value. ' PreviousValue: '.$previousValue. ' Diff: '.$diff);

                if ($diff <= 0) {
                    break;
                }


                $keys = [
                    \str_replace(['{databaseInternalId}'], [$data[0]], METRIC_DATABASE_ID_STORAGE),
                    METRIC_DATABASES_STORAGE
                ];

                \var_dump('[PROCESSING DATABASE KEYS] ' . \json_encode($keys));

                foreach ($keys as $metric) {
                    static::addStatsDocument($projectDocuments, $id, $period, $time, $metric, $diff);
                }

                break;
            case METRIC_PROJECT_LEVEL_STORAGE:
                $databases = [];
                try {
                    $databases = $dbForProject->find('database');
                } catch (\Exception $e) {
                    if (!$e instanceof NotFound) {
                        Console::error('[Error] Type: ' . get_class($e));
                        Console::error('[Error] Message: ' . $e->getMessage());
                        Console::error('[Error] File: ' . $e->getFile());
                        Console::error('[Error] Line: ' . $e->getLine());
                        Console::error('[Error] Trace: ' . $e->getTraceAsString());

                        throw $e;
                    }
                }

                foreach ($databases as $database) {
                    $databaseId = "database_{$database->getInternalId()}";

                    $collections = [];
                    try {
                        $collections = $dbForProject->find($databaseId);
                    } catch (\Exception $e) {
                        if (!$e instanceof NotFound) {
                            Console::error('[Error] Type: ' . get_class($e));
                            Console::error('[Error] Message: ' . $e->getMessage());
                            Console::error('[Error] File: ' . $e->getFile());
                            Console::error('[Error] Line: ' . $e->getLine());
                            Console::error('[Error] Trace: ' . $e->getTraceAsString());

                            throw $e;
                        }
                    }

                    foreach ($collections as $collection) {
                        $collectionId = "{$databaseId}_collection_{$collection->getInternalId()}";

                        try {
                            $value = $dbForProject->getSizeOfCollection($collectionId);
                        } catch (\Exception $e) {
                            if (!$e instanceof NotFound) {
                                throw $e;
                            }
                        }
                    }
                }

                $diff = $value - $previousValue;

                $project = $dbForProject->getSharedTables()
                    ? $dbForProject->getTenant()
                    : $dbForProject->getNamespace();

                //Console::info('['.DateTime::now().'] Project: '. $project . ' Value: '.$value. ' PreviousValue: '.$previousValue. ' Diff: '.$diff);

                if ($diff <= 0) {
                    break;
                }

                $keys = [
                    METRIC_DATABASES_STORAGE
                ];

                \var_dump('[PROCESSING PROJECT KEYS] ' . \json_encode($keys));

                foreach ($keys as $metric) {
                    static::addStatsDocument($projectDocuments, $id, $period, $time, $metric, $diff);
                }

                break;
        }
    }

    private static function addStatsDocument(
        array &$projectDocuments,
        string $id,
        string $period,
        ?string $time,
        string $key,
        int $diff
    ): void {
        $unique = static::getUniqueKey($key, $period, $time);

        if (isset($projectDocuments[$unique])) {
            Console::log("[DUPLICATE DETECTED] Metric: {$unique} (Incrementing by {$diff})");
            Console::log("Previous Value: " . $projectDocuments[$unique]['value']);
            \debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            $projectDocuments[$unique]['value'] += $diff;
            return;
        }

        Console::log("[ADDING] New metric: {$unique} (Value: {$diff})");

        $projectDocuments[$unique] = new Document([
            '$id' => $id,
            'period' => $period,
            'time' => $time,
            'metric' => $key,
            'value' => $diff,
            'region' => System::getEnv('_APP_REGION', 'default'),
        ]);
    }
}
