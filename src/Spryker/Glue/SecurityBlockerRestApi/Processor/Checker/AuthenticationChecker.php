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
    protected const int STATUS_NOT_SET = 0;

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

    /**
     * Success needs positive evidence: an error page that does not parse as JSON:API also has no errors.
     * Through the API Platform bridge the real status is known; on the legacy Glue path the status is still `0`
     * when the plugin runs, so the issued token resource is the evidence there.
     */
    public function isSuccessfulAuthenticationResponse(RestResponseInterface $restResponse): bool
    {
        if ($restResponse->getErrors()) {
            return false;
        }

        $status = $restResponse->getStatus();

        if ($status !== static::STATUS_NOT_SET) {
            return $status >= Response::HTTP_OK && $status < Response::HTTP_MULTIPLE_CHOICES;
        }

        return $restResponse->getResources() !== [];
    }
}
