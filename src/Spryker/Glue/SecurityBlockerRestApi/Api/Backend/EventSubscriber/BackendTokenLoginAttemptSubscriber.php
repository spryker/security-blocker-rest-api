<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Api\Backend\EventSubscriber;

use Spryker\ApiPlatform\Attribute\ApiType;
use Spryker\Glue\SecurityBlockerRestApi\EventSubscriber\AbstractTokenLoginAttemptSubscriber;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttles the Backend API `POST /token` for users: the JSON:API `tokens` resource and the legacy OAuth body.
 *
 * The legacy endpoint reads its credentials from whichever encoding decodes first (JSON text, form fields, raw
 * `parse_str`), so every username any of them carries is checked and reserved; the outcome is registered for the one
 * the endpoint authenticated, resolved here with the same precedence.
 *
 * @see \Spryker\Glue\OauthBackendApi\Api\Backend\EventSubscriber\TokenRequestSubscriber::extractAttributes()
 */
#[ApiType(types: ['backend'])]
class BackendTokenLoginAttemptSubscriber extends AbstractTokenLoginAttemptSubscriber
{
    protected const string CONTENT_TYPE_JSON_API = 'application/vnd.api+json';

    protected const array GRANT_TYPE_KEYS = ['grantType', 'grant_type'];

    protected const string KEY_USERNAME = 'username';

    protected const string KEY_DATA = 'data';

    protected const string KEY_ATTRIBUTES = 'attributes';

    protected const string KEY_ERRORS = 'errors';

    protected const string KEY_STATUS = 'status';

    protected const string KEY_CODE = 'code';

    protected const string KEY_DETAIL = 'detail';

    protected const string KEY_MESSAGE = 'message';

    protected function isPasswordGrantRequest(Request $request): bool
    {
        if ($this->isJsonApiRequest($request)) {
            return true;
        }

        $grantType = $this->findGrantType($this->extractLegacyAttributes($request));

        return $grantType === null || $grantType === '' || $grantType === static::GRANT_TYPE_PASSWORD;
    }

    /**
     * @return list<string>
     */
    protected function readLoginIdentifiers(Request $request): array
    {
        if ($this->isJsonApiRequest($request)) {
            $loginIdentifier = $this->findAuthenticatedLoginIdentifier($request);

            return $loginIdentifier === null ? [] : [$loginIdentifier];
        }

        $content = (string)$request->getContent();
        parse_str($content, $parsedAttributes);
        $jsonAttributes = json_decode($content, true);

        $loginIdentifiers = [
            $this->findAuthenticatedLoginIdentifier($request),
            $this->findUsername(is_array($jsonAttributes) ? $jsonAttributes : []),
            $this->findUsername($request->request->all()),
            $this->findUsername($parsedAttributes),
        ];

        return array_values(array_unique(array_filter($loginIdentifiers, 'is_string')));
    }

    protected function findAuthenticatedLoginIdentifier(Request $request): ?string
    {
        if ($this->isJsonApiRequest($request)) {
            $body = json_decode((string)$request->getContent(), true);
            $attributes = $body[static::KEY_DATA][static::KEY_ATTRIBUTES] ?? null;

            return is_array($attributes) ? $this->findUsername($attributes) : null;
        }

        return $this->findUsername($this->extractLegacyAttributes($request));
    }

    protected function isSuccessfulTokenResponse(Request $request, Response $response): bool
    {
        $expectedStatus = $this->isJsonApiRequest($request) ? Response::HTTP_CREATED : Response::HTTP_OK;

        return $response->getStatusCode() === $expectedStatus;
    }

    protected function isFailedTokenResponse(Request $request, Response $response): bool
    {
        $expectedStatus = $this->isJsonApiRequest($request) ? Response::HTTP_UNAUTHORIZED : Response::HTTP_BAD_REQUEST;

        return $response->getStatusCode() === $expectedStatus;
    }

    protected function createBlockedResponse(Request $request): Response
    {
        $errorDescription = $this->securityBlockerRestApiConfig->getTokenEndpointBlockedErrorDescription();

        if (!$this->isJsonApiRequest($request)) {
            return new JsonResponse([[
                static::KEY_MESSAGE => $errorDescription,
                static::KEY_STATUS => Response::HTTP_TOO_MANY_REQUESTS,
                static::KEY_CODE => static::ERROR_TEMPORARILY_UNAVAILABLE,
            ]], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return new JsonResponse([
            static::KEY_ERRORS => [[
                static::KEY_STATUS => Response::HTTP_TOO_MANY_REQUESTS,
                static::KEY_CODE => SecurityBlockerRestApiConfig::ERROR_RESPONSE_CODE_ACCOUNT_BLOCKED,
                static::KEY_DETAIL => $errorDescription,
            ]],
        ], Response::HTTP_TOO_MANY_REQUESTS, ['Content-Type' => static::CONTENT_TYPE_JSON_API]);
    }

    protected function getSecurityBlockerEntityType(): string
    {
        return SecurityBlockerRestApiConfig::SECURITY_BLOCKER_BACKEND_API_USER_ENTITY_TYPE;
    }

    protected function getLoginRealm(): string
    {
        return SecurityBlockerRestApiConfig::LOGIN_REALM_USER;
    }

    protected function isJsonApiRequest(Request $request): bool
    {
        return str_starts_with((string)$request->headers->get('Content-Type'), static::CONTENT_TYPE_JSON_API);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function extractLegacyAttributes(Request $request): array
    {
        $content = (string)$request->getContent();
        $jsonAttributes = json_decode($content, true);

        if (is_array($jsonAttributes)) {
            return $jsonAttributes;
        }

        $formAttributes = $request->request->all();

        if ($formAttributes !== []) {
            return $formAttributes;
        }

        parse_str($content, $parsedAttributes);

        return $parsedAttributes;
    }

    /**
     * @param array<int|string, mixed> $attributes
     */
    protected function findGrantType(array $attributes): ?string
    {
        foreach (static::GRANT_TYPE_KEYS as $grantTypeKey) {
            if (isset($attributes[$grantTypeKey]) && is_scalar($attributes[$grantTypeKey])) {
                return (string)$attributes[$grantTypeKey];
            }
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $attributes
     */
    protected function findUsername(array $attributes): ?string
    {
        $username = $attributes[static::KEY_USERNAME] ?? null;

        return is_scalar($username) ? (string)$username : null;
    }
}
