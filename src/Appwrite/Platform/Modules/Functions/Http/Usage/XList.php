<?php

namespace Appwrite\Platform\Modules\Functions\Http\Usage;

use Appwrite\Platform\Modules\Compute\Base;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Response;
use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\WhiteList;

class XList extends Base
{
    use HTTP;

    public static function getName()
    {
        return 'getFunctionsUsage';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/functions/usage')
            ->desc('Get functions usage')
            ->groups(['api', 'functions', 'usage'])
            ->label('scope', 'functions.read')
            ->label('resourceType', RESOURCE_TYPE_FUNCTIONS)
            ->label('sdk', new Method(
                namespace: 'functions',
                name: 'listUsage',
                description: <<<EOT
                Get usage metrics and statistics for all functions in the project. View statistics including total deployments, builds, logs, storage usage, and compute time. The response includes both current totals and historical data for each metric. Use the optional range parameter to specify the time window for historical data: 24h (last 24 hours), 30d (last 30 days), or 90d (last 90 days). If not specified, defaults to 30 days.
                EOT,
                auth: [AuthType::ADMIN],
                responses: [
                    new SDKResponse(
                        code: Response::STATUS_CODE_OK,
                        model: Response::MODEL_USAGE_FUNCTIONS,
                    )
                ]
            ))
            ->param('range', '30d', new WhiteList(['24h', '30d', '90d']), 'Date range.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->callback([$this, 'action']);
    }

    public function action(string $range, Response $response, Database $dbForProject)
    {
        $periods = Config::getParam('usage', []);
        $stats = $usage = [];
        $days = $periods[$range];
        $metrics = [
            METRIC_FUNCTIONS,
            METRIC_DEPLOYMENTS,
            METRIC_DEPLOYMENTS_STORAGE,
            METRIC_BUILDS,
            METRIC_BUILDS_STORAGE,
            METRIC_BUILDS_COMPUTE,
            METRIC_EXECUTIONS,
            METRIC_EXECUTIONS_COMPUTE,
            METRIC_BUILDS_MB_SECONDS,
            METRIC_EXECUTIONS_MB_SECONDS,
        ];

        Authorization::skip(function () use ($dbForProject, $days, $metrics, &$stats) {
            foreach ($metrics as $metric) {
                $result =  $dbForProject->findOne('stats', [
                    Query::equal('metric', [$metric]),
                    Query::equal('period', ['inf'])
                ]);

                $stats[$metric]['total'] = $result['value'] ?? 0;
                $limit = $days['limit'];
                $period = $days['period'];
                $results = $dbForProject->find('stats', [
                    Query::equal('metric', [$metric]),
                    Query::equal('period', [$period]),
                    Query::limit($limit),
                    Query::orderDesc('time'),
                ]);
                $stats[$metric]['data'] = [];
                foreach ($results as $result) {
                    $stats[$metric]['data'][$result->getAttribute('time')] = [
                        'value' => $result->getAttribute('value'),
                    ];
                }
            }
        });

        $format = match ($days['period']) {
            '1h' => 'Y-m-d\TH:00:00.000P',
            '1d' => 'Y-m-d\T00:00:00.000P',
        };

        foreach ($metrics as $metric) {
            $usage[$metric]['total'] =  $stats[$metric]['total'];
            $usage[$metric]['data'] = [];
            $leap = time() - ($days['limit'] * $days['factor']);
            while ($leap < time()) {
                $leap += $days['factor'];
                $formatDate = date($format, $leap);
                $usage[$metric]['data'][] = [
                    'value' => $stats[$metric]['data'][$formatDate]['value'] ?? 0,
                    'date' => $formatDate,
                ];
            }
        }
        $response->dynamic(new Document([
            'range' => $range,
            'functionsTotal' => $usage[$metrics[0]]['total'],
            'deploymentsTotal' => $usage[$metrics[1]]['total'],
            'deploymentsStorageTotal' => $usage[$metrics[2]]['total'],
            'buildsTotal' => $usage[$metrics[3]]['total'],
            'buildsStorageTotal' => $usage[$metrics[4]]['total'],
            'buildsTimeTotal' => $usage[$metrics[5]]['total'],
            'executionsTotal' => $usage[$metrics[6]]['total'],
            'executionsTimeTotal' => $usage[$metrics[7]]['total'],
            'functions' => $usage[$metrics[0]]['data'],
            'deployments' => $usage[$metrics[1]]['data'],
            'deploymentsStorage' => $usage[$metrics[2]]['data'],
            'builds' => $usage[$metrics[3]]['data'],
            'buildsStorage' => $usage[$metrics[4]]['data'],
            'buildsTime' => $usage[$metrics[5]]['data'],
            'executions' => $usage[$metrics[6]]['data'],
            'executionsTime' => $usage[$metrics[7]]['data'],
            'buildsMbSecondsTotal' => $usage[$metrics[8]]['total'],
            'buildsMbSeconds' => $usage[$metrics[8]]['data'],
            'executionsMbSeconds' => $usage[$metrics[9]]['data'],
            'executionsMbSecondsTotal' => $usage[$metrics[9]]['total'],
        ]), Response::MODEL_USAGE_FUNCTIONS);
    }
}
