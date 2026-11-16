<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Checker;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;

interface AuthenticationCheckerInterface
{
    public function isAuthenticationRequest(RestRequestInterface $restRequest, string $expectedResourceType): bool;

    public function isFailedAuthenticationResponse(RestResponseInterface $restResponse, string $expectedCode): bool;

    public function isSuccessfulAuthenticationResponse(RestResponseInterface $restResponse): bool;
}
