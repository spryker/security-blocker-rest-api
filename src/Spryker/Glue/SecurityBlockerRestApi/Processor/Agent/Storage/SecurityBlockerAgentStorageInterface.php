<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Storage;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;

interface SecurityBlockerAgentStorageInterface
{
    public function incrementLoginAttemptCount(
        string $action,
        RestRequestInterface $restRequest,
        RestResponseInterface $restResponse
    ): void;
}
