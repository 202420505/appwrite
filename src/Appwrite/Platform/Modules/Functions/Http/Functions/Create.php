<?php

namespace Appwrite\Platform\Modules\Functions\Http\Functions;

use Appwrite\Event\Build;
use Appwrite\Event\Event;
use Appwrite\Event\Validator\FunctionEvent;
use Appwrite\Extend\Exception;
use Appwrite\Functions\Validator\RuntimeSpecification;
use Appwrite\Platform\Modules\Compute\Base;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Task\Validator\Cron;
use Appwrite\Utopia\Database\Validator\CustomId;
use Appwrite\Utopia\Response;
use Utopia\Abuse\Abuse;
use Utopia\App;
use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Database\DateTime;
use Utopia\Database\Document;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Helpers\Permission;
use Utopia\Database\Helpers\Role;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\Roles;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Swoole\Request;
use Utopia\System\System;
use Utopia\Validator\ArrayList;
use Utopia\Validator\Boolean;
use Utopia\Validator\Range;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;
use Utopia\VCS\Adapter\Git\GitHub;

class Create extends Base
{
    use HTTP;

    public static function getName()
    {
        return 'createFunction';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/functions')
            ->desc('Create function')
            ->groups(['api', 'functions'])
            ->label('scope', 'functions.write')
            ->label('event', 'functions.[functionId].create')
            ->label('resourceType', RESOURCE_TYPE_FUNCTIONS)
            ->label('audits.event', 'function.create')
            ->label('audits.resource', 'function/{response.$id}')
            ->label('sdk', new Method(
                namespace: 'functions',
                name: 'create',
                description: <<<EOT
                Create a new function. You can pass a list of [permissions](https://appwrite.io/docs/permissions) to allow different project users or team with access to execute the function using the client API.
                EOT,
                auth: [AuthType::KEY],
                responses: [
                    new SDKResponse(
                        code: Response::STATUS_CODE_CREATED,
                        model: Response::MODEL_FUNCTION,
                    )
                ],
            ))
            ->param('functionId', '', new CustomId(), 'Function ID. Choose a custom ID or generate a random ID with `ID.unique()`. Valid chars are a-z, A-Z, 0-9, period, hyphen, and underscore. Can\'t start with a special char. Max length is 36 chars.')
            ->param('name', '', new Text(128), 'Function name. Max length: 128 chars.')
            ->param('runtime', '', new WhiteList(array_keys(Config::getParam('runtimes')), true), 'Execution runtime.')
            ->param('execute', [], new Roles(APP_LIMIT_ARRAY_PARAMS_SIZE), 'An array of role strings with execution permissions. By default no user is granted with any execute permissions. [learn more about roles](https://appwrite.io/docs/permissions#permission-roles). Maximum of ' . APP_LIMIT_ARRAY_PARAMS_SIZE . ' roles are allowed, each 64 characters long.', true)
            ->param('events', [], new ArrayList(new FunctionEvent(), APP_LIMIT_ARRAY_PARAMS_SIZE), 'Events list. Maximum of ' . APP_LIMIT_ARRAY_PARAMS_SIZE . ' events are allowed.', true)
            ->param('schedule', '', new Cron(), 'Schedule CRON syntax.', true)
            ->param('timeout', 15, new Range(1, (int) System::getEnv('_APP_COMPUTE_TIMEOUT', 900)), 'Function maximum execution time in seconds.', true)
            ->param('enabled', true, new Boolean(), 'Is function enabled? When set to \'disabled\', users cannot access the function but Server SDKs with and API key can still access the function. No data is lost when this is toggled.', true)
            ->param('logging', true, new Boolean(), 'Whether executions will be logged. When set to false, executions will not be logged, but will reduce resource used by your Appwrite project.', true)
            ->param('entrypoint', '', new Text(1028, 0), 'Entrypoint File. This path is relative to the "providerRootDirectory".', true)
            ->param('commands', '', new Text(8192, 0), 'Build Commands.', true)
            ->param('scopes', [], new ArrayList(new WhiteList(array_keys(Config::getParam('scopes')), true), APP_LIMIT_ARRAY_PARAMS_SIZE), 'List of scopes allowed for API key auto-generated for every execution. Maximum of ' . APP_LIMIT_ARRAY_PARAMS_SIZE . ' scopes are allowed.', true)
            ->param('installationId', '', new Text(128, 0), 'Appwrite Installation ID for VCS (Version Control System) deployment.', true)
            ->param('providerRepositoryId', '', new Text(128, 0), 'Repository ID of the repo linked to the function.', true)
            ->param('providerBranch', '', new Text(128, 0), 'Production branch for the repo linked to the function.', true)
            ->param('providerSilentMode', false, new Boolean(), 'Is the VCS (Version Control System) connection in silent mode for the repo linked to the function? In silent mode, comments will not be made on commits and pull requests.', true)
            ->param('providerRootDirectory', '', new Text(128, 0), 'Path to function code in the linked repo.', true)
            ->param('templateRepository', '', new Text(128, 0), 'Repository name of the template.', true)
            ->param('templateOwner', '', new Text(128, 0), 'The name of the owner of the template.', true)
            ->param('templateRootDirectory', '', new Text(128, 0), 'Path to function code in the template repo.', true)
            ->param('templateVersion', '', new Text(128, 0), 'Version (tag) for the repo linked to the function template.', true)
            ->param('specification', APP_COMPUTE_SPECIFICATION_DEFAULT, fn (array $plan) => new RuntimeSpecification(
                $plan,
                Config::getParam('runtime-specifications', []),
                App::getEnv('_APP_COMPUTE_CPUS', APP_COMPUTE_CPUS_DEFAULT),
                App::getEnv('_APP_COMPUTE_MEMORY', APP_COMPUTE_MEMORY_DEFAULT)
            ), 'Runtime specification for the function and builds.', true, ['plan'])
            ->inject('request')
            ->inject('response')
            ->inject('dbForProject')
            ->inject('timelimit')
            ->inject('project')
            ->inject('user')
            ->inject('queueForEvents')
            ->inject('queueForBuilds')
            ->inject('dbForPlatform')
            ->inject('gitHub')
            ->callback([$this, 'action']);
    }

    public function action(string $functionId, string $name, string $runtime, array $execute, array $events, string $schedule, int $timeout, bool $enabled, bool $logging, string $entrypoint, string $commands, array $scopes, string $installationId, string $providerRepositoryId, string $providerBranch, bool $providerSilentMode, string $providerRootDirectory, string $templateRepository, string $templateOwner, string $templateRootDirectory, string $templateVersion, string $specification, Request $request, Response $response, Database $dbForProject, callable $timelimit, Document $project, Document $user, Event $queueForEvents, Build $queueForBuilds, Database $dbForPlatform, GitHub $github)
    {

        // Temporary abuse check
        $abuseCheck = function () use ($project, $timelimit, $response) {
            $abuseKey = "projectId:{projectId},url:{url}";
            $abuseLimit = App::getEnv('_APP_FUNCTIONS_CREATION_ABUSE_LIMIT', 50);
            $abuseTime = 86400; // 1 day

            $timeLimit = $timelimit($abuseKey, $abuseLimit, $abuseTime);
            $timeLimit
                ->setParam('{projectId}', $project->getId())
                ->setParam('{url}', '/v1/functions');

            $abuse = new Abuse($timeLimit);
            $remaining = $timeLimit->remaining();
            $limit = $timeLimit->limit();
            $time = $timeLimit->time() + $abuseTime;

            $response
                ->addHeader('X-RateLimit-Limit', $limit)
                ->addHeader('X-RateLimit-Remaining', $remaining)
                ->addHeader('X-RateLimit-Reset', $time);

            $enabled = System::getEnv('_APP_OPTIONS_ABUSE', 'enabled') !== 'disabled';
            if ($enabled && $abuse->check()) {
                throw new Exception(Exception::GENERAL_RATE_LIMIT_EXCEEDED);
            }
        };

        $abuseCheck();

        $functionId = ($functionId == 'unique()') ? ID::unique() : $functionId;

        $allowList = \array_filter(\explode(',', System::getEnv('_APP_FUNCTIONS_RUNTIMES', '')));

        if (!empty($allowList) && !\in_array($runtime, $allowList)) {
            throw new Exception(Exception::FUNCTION_RUNTIME_UNSUPPORTED, 'Runtime "' . $runtime . '" is not supported');
        }

        // build from template
        $template = new Document([]);
        if (
            !empty($templateRepository)
            && !empty($templateOwner)
            && !empty($templateRootDirectory)
            && !empty($templateVersion)
        ) {
            $template->setAttribute('repositoryName', $templateRepository)
                ->setAttribute('ownerName', $templateOwner)
                ->setAttribute('rootDirectory', $templateRootDirectory)
                ->setAttribute('version', $templateVersion);
        }

        $installation = $dbForPlatform->getDocument('installations', $installationId);

        if (!empty($installationId) && $installation->isEmpty()) {
            throw new Exception(Exception::INSTALLATION_NOT_FOUND);
        }

        if (!empty($providerRepositoryId) && (empty($installationId) || empty($providerBranch))) {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'When connecting to VCS (Version Control System), you need to provide "installationId" and "providerBranch".');
        }

        $function = $dbForProject->createDocument('functions', new Document([
            '$id' => $functionId,
            'execute' => $execute,
            'enabled' => $enabled,
            'live' => true,
            'logging' => $logging,
            'name' => $name,
            'runtime' => $runtime,
            'deploymentInternalId' => '',
            'deployment' => '',
            'events' => $events,
            'schedule' => $schedule,
            'scheduleInternalId' => '',
            'scheduleId' => '',
            'timeout' => $timeout,
            'entrypoint' => $entrypoint,
            'commands' => $commands,
            'scopes' => $scopes,
            'search' => implode(' ', [$functionId, $name, $runtime]),
            'version' => 'v4',
            'installationId' => $installation->getId(),
            'installationInternalId' => $installation->getInternalId(),
            'providerRepositoryId' => $providerRepositoryId,
            'repositoryId' => '',
            'repositoryInternalId' => '',
            'providerBranch' => $providerBranch,
            'providerRootDirectory' => $providerRootDirectory,
            'providerSilentMode' => $providerSilentMode,
            'specification' => $specification
        ]));

        $schedule = Authorization::skip(
            fn () => $dbForPlatform->createDocument('schedules', new Document([
                'region' => System::getEnv('_APP_REGION', 'default'), // Todo replace with projects region
                'resourceType' => 'function',
                'resourceId' => $function->getId(),
                'resourceInternalId' => $function->getInternalId(),
                'resourceUpdatedAt' => DateTime::now(),
                'projectId' => $project->getId(),
                'schedule'  => $function->getAttribute('schedule'),
                'active' => false,
            ]))
        );

        $function->setAttribute('scheduleId', $schedule->getId());
        $function->setAttribute('scheduleInternalId', $schedule->getInternalId());

        // Git connect logic
        if (!empty($providerRepositoryId)) {
            $teamId = $project->getAttribute('teamId', '');

            $repository = $dbForPlatform->createDocument('repositories', new Document([
                '$id' => ID::unique(),
                '$permissions' => [
                    Permission::read(Role::team(ID::custom($teamId))),
                    Permission::update(Role::team(ID::custom($teamId), 'owner')),
                    Permission::update(Role::team(ID::custom($teamId), 'developer')),
                    Permission::delete(Role::team(ID::custom($teamId), 'owner')),
                    Permission::delete(Role::team(ID::custom($teamId), 'developer')),
                ],
                'installationId' => $installation->getId(),
                'installationInternalId' => $installation->getInternalId(),
                'projectId' => $project->getId(),
                'projectInternalId' => $project->getInternalId(),
                'providerRepositoryId' => $providerRepositoryId,
                'resourceId' => $function->getId(),
                'resourceInternalId' => $function->getInternalId(),
                'resourceType' => 'function',
                'providerPullRequestIds' => []
            ]));

            $function->setAttribute('repositoryId', $repository->getId());
            $function->setAttribute('repositoryInternalId', $repository->getInternalId());
        }

        $function = $dbForProject->updateDocument('functions', $function->getId(), $function);

        if (!empty($providerRepositoryId)) {
            // Deploy VCS
            $this->redeployVcsFunction($request, $function, $project, $installation, $dbForProject, $queueForBuilds, $template, $github);
        } elseif (!$template->isEmpty()) {
            // Deploy non-VCS from template
            $deploymentId = ID::unique();
            $deployment = $dbForProject->createDocument('deployments', new Document([
                '$id' => $deploymentId,
                '$permissions' => [
                    Permission::read(Role::any()),
                    Permission::update(Role::any()),
                    Permission::delete(Role::any()),
                ],
                'resourceId' => $function->getId(),
                'resourceInternalId' => $function->getInternalId(),
                'resourceType' => 'functions',
                'entrypoint' => $function->getAttribute('entrypoint', ''),
                'commands' => $function->getAttribute('commands', ''),
                'type' => 'manual',
                'search' => implode(' ', [$deploymentId, $function->getAttribute('entrypoint', '')]),
                'activate' => true,
            ]));

            $queueForBuilds
                ->setType(BUILD_TYPE_DEPLOYMENT)
                ->setResource($function)
                ->setDeployment($deployment)
                ->setTemplate($template);
        }

        $queueForEvents->setParam('functionId', $function->getId());

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($function, Response::MODEL_FUNCTION);
    }
}
