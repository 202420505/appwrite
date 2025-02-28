<?php

namespace Appwrite\Platform\Modules\Functions\Http\Deployments\Builds;

use Appwrite\Event\Build;
use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Storage\Device;

class Create extends Action
{
    use HTTP;

    public static function getName()
    {
        return 'createDeploymentBuild';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/functions/:functionId/deployments/:deploymentId/build')
            ->httpAlias('/v1/functions/:functionId/deployments/:deploymentId/builds/:buildId')
            ->desc('Rebuild deployment')
            ->groups(['api', 'functions'])
            ->label('scope', 'functions.write')
            ->label('resourceType', RESOURCE_TYPE_FUNCTIONS)
            ->label('event', 'functions.[functionId].deployments.[deploymentId].update')
            ->label('audits.event', 'deployment.update')
            ->label('audits.resource', 'function/{request.functionId}')
            ->label('sdk', new Method(
                namespace: 'functions',
                name: 'createBuild',
                description: <<<EOT
                Create a new build for an existing function deployment. This endpoint allows you to rebuild a deployment with the updated function configuration, including its entrypoint and build commands if they have been modified. The build process will be queued and executed asynchronously. The original deployment's code will be preserved and used for the new build.
                EOT,
                auth: [AuthType::KEY],
                responses: [
                    new SDKResponse(
                        code: Response::STATUS_CODE_NOCONTENT,
                        model: Response::MODEL_NONE,
                    )
                ]
            ))
            ->param('functionId', '', new UID(), 'Function ID.')
            ->param('deploymentId', '', new UID(), 'Deployment ID.')
            ->param('buildId', '', new UID(), 'Build unique ID.', true) // added as optional param for backward compatibility
            ->inject('response')
            ->inject('dbForProject')
            ->inject('queueForEvents')
            ->inject('queueForBuilds')
            ->inject('deviceForFunctions')
            ->callback([$this, 'action']);
    }

    public function action(string $functionId, string $deploymentId, string $buildId, Response $response, Database $dbForProject, Event $queueForEvents, Build $queueForBuilds, Device $deviceForFunctions)
    {
        $function = $dbForProject->getDocument('functions', $functionId);

        if ($function->isEmpty()) {
            throw new Exception(Exception::FUNCTION_NOT_FOUND);
        }
        $deployment = $dbForProject->getDocument('deployments', $deploymentId);

        if ($deployment->isEmpty()) {
            throw new Exception(Exception::DEPLOYMENT_NOT_FOUND);
        }

        $path = $deployment->getAttribute('path');
        if (empty($path) || !$deviceForFunctions->exists($path)) {
            throw new Exception(Exception::DEPLOYMENT_NOT_FOUND);
        }

        $deploymentId = ID::unique();

        $destination = $deviceForFunctions->getPath($deploymentId . '.' . \pathinfo('code.tar.gz', PATHINFO_EXTENSION));
        $deviceForFunctions->transfer($path, $destination, $deviceForFunctions);

        $deployment->removeAttribute('$internalId');
        $deployment = $dbForProject->createDocument('deployments', $deployment->setAttributes([
            '$internalId' => '',
            '$id' => $deploymentId,
            'buildId' => '',
            'buildInternalId' => '',
            'path' => $destination,
            'entrypoint' => $function->getAttribute('entrypoint'),
            'commands' => $function->getAttribute('commands', ''),
            'search' => implode(' ', [$deploymentId, $function->getAttribute('entrypoint')]),
        ]));

        $queueForBuilds
            ->setType(BUILD_TYPE_DEPLOYMENT)
            ->setResource($function)
            ->setDeployment($deployment);

        $queueForEvents
            ->setParam('functionId', $function->getId())
            ->setParam('deploymentId', $deployment->getId());

        $response->noContent();
    }
}
