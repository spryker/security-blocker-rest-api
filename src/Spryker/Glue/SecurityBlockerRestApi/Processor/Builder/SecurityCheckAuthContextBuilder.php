<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Builder;

use Generated\Shared\Transfer\RestAccessTokensAttributesTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;

class SecurityCheckAuthContextBuilder implements SecurityCheckAuthContextBuilderInterface
{
    /**
     * @uses \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface::RESOURCE_DATA
     */
    protected const string RESOURCE_DATA = 'data';

    /**
     * @uses \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface::RESOURCE_ATTRIBUTES
     */
    protected const string RESOURCE_ATTRIBUTES = 'attributes';

    /**
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    public function createCustomerSecurityCheckAuthContextTransfers(RestRequestInterface $restRequest): array
    {
        /** @var \Generated\Shared\Transfer\RestAccessTokensAttributesTransfer $restAccessTokensAttributesTransfer */
        $restAccessTokensAttributesTransfer = $restRequest->getResource()->getAttributes();

        return $this->createSecurityCheckAuthContextTransfers(
            $restRequest,
            SecurityBlockerRestApiConfig::SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE,
            SecurityBlockerRestApiConfig::LOGIN_REALM_CUSTOMER,
            $restAccessTokensAttributesTransfer->getUsername(),
        );
    }

    /**
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    public function createAgentSecurityCheckAuthContextTransfers(RestRequestInterface $restRequest): array
    {
        /** @var \Generated\Shared\Transfer\RestAgentAccessTokensRequestAttributesTransfer $restAgentAccessTokensRequestAttributesTransfer */
        $restAgentAccessTokensRequestAttributesTransfer = $restRequest->getResource()->getAttributes();

        return $this->createSecurityCheckAuthContextTransfers(
            $restRequest,
            SecurityBlockerRestApiConfig::SECURITY_BLOCKER_AGENT_ENTITY_TYPE,
            SecurityBlockerRestApiConfig::LOGIN_REALM_USER,
            $restAgentAccessTokensRequestAttributesTransfer->getUsername(),
        );
    }

    /**
     * One context per identifier an endpoint could authenticate. The legacy controller reads the attributes transfer,
     * whose `fromArray()` also maps alias keys such as `Username` and keeps the last one, while the API Platform
     * processor reads the exact `username` attribute. A body carrying both is counted against each of them.
     *
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    protected function createSecurityCheckAuthContextTransfers(
        RestRequestInterface $restRequest,
        string $type,
        string $realm,
        mixed $username
    ): array {
        $usernames = array_values(array_unique(array_filter([$username, $this->findBodyUsername($restRequest)], 'is_string')));

        if ($usernames === []) {
            return [$this->createSecurityCheckAuthContextTransfer($restRequest, $type, $realm, null)];
        }

        return array_map(
            fn (string $candidateUsername): SecurityCheckAuthContextTransfer => $this->createSecurityCheckAuthContextTransfer($restRequest, $type, $realm, $candidateUsername),
            $usernames,
        );
    }

    protected function findBodyUsername(RestRequestInterface $restRequest): ?string
    {
        $body = json_decode((string)$restRequest->getHttpRequest()->getContent(), true);
        $data = is_array($body) ? ($body[static::RESOURCE_DATA] ?? null) : null;
        $attributes = is_array($data) ? ($data[static::RESOURCE_ATTRIBUTES] ?? null) : null;
        $username = is_array($attributes) ? ($attributes[RestAccessTokensAttributesTransfer::USERNAME] ?? null) : null;

        return is_string($username) ? $username : null;
    }

    /**
     * The username stays part of the per-IP key (`account`): without trusted proxies every customer behind one
     * backend-for-frontend shares an IP, and an IP-only key would lock all of them at once.
     */
    protected function createSecurityCheckAuthContextTransfer(
        RestRequestInterface $restRequest,
        string $type,
        string $realm,
        ?string $username
    ): SecurityCheckAuthContextTransfer {
        return (new SecurityCheckAuthContextTransfer())
            ->setType($type)
            ->setIp($restRequest->getHttpRequest()->getClientIp())
            ->setAccount($username)
            ->setRealm($realm)
            ->setLoginIdentifier($username);
    }
}
