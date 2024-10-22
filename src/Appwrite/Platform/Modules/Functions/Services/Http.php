<?php

namespace Appwrite\Platform\Modules\Functions\Services;

use Appwrite\Platform\Modules\Functions\Http\Deployments\CreateDeployment;
use Appwrite\Platform\Modules\Functions\Http\Functions\CreateFunction;
use Appwrite\Platform\Modules\Functions\Http\Functions\UpdateFunction;
use Utopia\Platform\Service;

class Http extends Service
{
    public function __construct()
    {
        $this->type = Service::TYPE_HTTP;
        $this->addAction(CreateFunction::getName(), new CreateFunction());
        $this->addAction(UpdateFunction::getName(), new UpdateFunction());
        $this->addAction(CreateDeployment::getName(), new CreateDeployment());
    }
}
