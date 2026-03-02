<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Checker;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationChecker implements AuthenticationCheckerInterface
{
    public function isAuthenticationRequest(RestRequestInterface $restRequest, string $expectedResourceType): bool
    {
        return $restRequest->getResource()->getType() === $expectedResourceType
            && $restRequest->getHttpRequest()->getMethod() === Request::METHOD_POST;
    }

    public function isFailedAuthenticationResponse(RestResponseInterface $restResponse, string $expectedCode): bool
    {
        if (!$restResponse->getErrors()) {
            return false;
        }

        foreach ($restResponse->getErrors() as $restErrorMessageTransfer) {
            if (
                $restErrorMessageTransfer->getStatus() === Response::HTTP_UNAUTHORIZED
                && $restErrorMessageTransfer->getCode() === $expectedCode
            ) {
                return true;
            }
        }

        return false;
    }
}
