<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Api\Storefront\EventSubscriber;

use Generated\Shared\Transfer\OauthRequestTransfer;
use Spryker\ApiPlatform\Attribute\ApiType;
use Spryker\Glue\SecurityBlockerRestApi\EventSubscriber\AbstractTokenLoginAttemptSubscriber;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttles the storefront OAuth `POST /token` password grant (RFC 6749 form body) for customers.
 *
 * @see \Spryker\Glue\AuthRestApi\Api\Storefront\EventSubscriber\TokenRequestSubscriber
 */
#[ApiType(types: ['storefront'])]
class StorefrontTokenLoginAttemptSubscriber extends AbstractTokenLoginAttemptSubscriber
{
    protected const string KEY_ERROR = 'error';

    protected const string KEY_ERROR_DESCRIPTION = 'error_description';

    protected function isPasswordGrantRequest(Request $request): bool
    {
        return $this->createOauthRequestTransfer($request)->getGrantType() === static::GRANT_TYPE_PASSWORD;
    }

    /**
     * @return list<string>
     */
    protected function readLoginIdentifiers(Request $request): array
    {
        $loginIdentifier = $this->findAuthenticatedLoginIdentifier($request);

        return $loginIdentifier === null ? [] : [$loginIdentifier];
    }

    protected function findAuthenticatedLoginIdentifier(Request $request): ?string
    {
        $username = $this->createOauthRequestTransfer($request)->getUsername();

        return is_string($username) ? $username : null;
    }

    protected function isSuccessfulTokenResponse(Request $request, Response $response): bool
    {
        return $response->getStatusCode() === Response::HTTP_OK;
    }

    protected function isFailedTokenResponse(Request $request, Response $response): bool
    {
        return $response->getStatusCode() === Response::HTTP_BAD_REQUEST;
    }

    protected function createBlockedResponse(Request $request): Response
    {
        return new JsonResponse([
            static::KEY_ERROR => static::ERROR_TEMPORARILY_UNAVAILABLE,
            static::KEY_ERROR_DESCRIPTION => $this->securityBlockerRestApiConfig->getTokenEndpointBlockedErrorDescription(),
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    protected function getSecurityBlockerEntityType(): string
    {
        return SecurityBlockerRestApiConfig::SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE;
    }

    protected function getLoginRealm(): string
    {
        return SecurityBlockerRestApiConfig::LOGIN_REALM_CUSTOMER;
    }

    protected function createOauthRequestTransfer(Request $request): OauthRequestTransfer
    {
        return (new OauthRequestTransfer())->fromArray($request->request->all(), true);
    }
}
