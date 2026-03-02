<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Storage;

use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;

class SecurityBlockerStorage implements SecurityBlockerStorageInterface
{
    /**
     * @uses \Spryker\Glue\AuthRestApi\AuthRestApiConfig::RESPONSE_INVALID_LOGIN
     *
     * @var string
     */
    protected const RESPONSE_INVALID_LOGIN = '003';

    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface
     */
    protected $securityBlockerClient;

    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface
     */
    protected $authenticationChecker;

    public function __construct(
        SecurityBlockerRestApiToSecurityBlockerClientInterface $securityBlockerClient,
        AuthenticationCheckerInterface $authenticationChecker
    ) {
        $this->securityBlockerClient = $securityBlockerClient;
        $this->authenticationChecker = $authenticationChecker;
    }

    public function incrementLoginAttemptCount(
        string $action,
        RestRequestInterface $restRequest,
        RestResponseInterface $restResponse
    ): void {
        if (
            !$this->authenticationChecker->isAuthenticationRequest($restRequest, SecurityBlockerRestApiConfig::RESOURCE_ACCESS_TOKENS)
            || !$this->authenticationChecker->isFailedAuthenticationResponse($restResponse, static::RESPONSE_INVALID_LOGIN)
        ) {
            return;
        }

        $securityCheckAuthContextTransfer = $this->createSecurityCheckAuthContextTransfer($restRequest);

        $this->securityBlockerClient->incrementLoginAttemptCount($securityCheckAuthContextTransfer);
    }

    protected function createSecurityCheckAuthContextTransfer(RestRequestInterface $restRequest): SecurityCheckAuthContextTransfer
    {
        /** @var \Generated\Shared\Transfer\RestAccessTokensAttributesTransfer $restAccessTokensAttributesTransfer */
        $restAccessTokensAttributesTransfer = $restRequest->getResource()->getAttributes();

        return (new SecurityCheckAuthContextTransfer())
            ->setType(SecurityBlockerRestApiConfig::SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE)
            ->setIp($restRequest->getHttpRequest()->getClientIp())
            ->setAccount($restAccessTokensAttributesTransfer->getUsername());
    }
}
