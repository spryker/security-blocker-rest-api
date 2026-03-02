<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Validator;

use Generated\Shared\Transfer\RestErrorCollectionTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\RestErrorCollectionBuilderInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;

class SecurityBlockerAgentValidator implements SecurityBlockerAgentValidatorInterface
{
    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface
     */
    protected $securityBlockerClient;

    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface
     */
    protected $authenticationChecker;

    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\RestErrorCollectionBuilderInterface
     */
    protected $restErrorCollectionBuilder;

    public function __construct(
        SecurityBlockerRestApiToSecurityBlockerClientInterface $securityBlockerClient,
        AuthenticationCheckerInterface $authenticationChecker,
        RestErrorCollectionBuilderInterface $restErrorCollectionBuilder
    ) {
        $this->securityBlockerClient = $securityBlockerClient;
        $this->authenticationChecker = $authenticationChecker;
        $this->restErrorCollectionBuilder = $restErrorCollectionBuilder;
    }

    public function isAccountBlocked(RestRequestInterface $restRequest): ?RestErrorCollectionTransfer
    {
        if (!$this->authenticationChecker->isAuthenticationRequest($restRequest, SecurityBlockerRestApiConfig::RESOURCE_AGENT_ACCESS_TOKENS)) {
            return null;
        }

        $securityCheckAuthContextTransfer = $this->createSecurityCheckAuthContextTransfer($restRequest);

        $securityCheckAuthResponseTransfer = $this->securityBlockerClient
            ->isAccountBlocked($securityCheckAuthContextTransfer);

        if (!$securityCheckAuthResponseTransfer->getIsBlocked()) {
            return null;
        }

        return $this->restErrorCollectionBuilder->createRestErrorCollectionTransfer(
            $securityCheckAuthResponseTransfer,
            $restRequest->getMetadata()->getLocale(),
        );
    }

    protected function createSecurityCheckAuthContextTransfer(RestRequestInterface $restRequest): SecurityCheckAuthContextTransfer
    {
        /** @var \Generated\Shared\Transfer\RestAccessTokensAttributesTransfer $restAccessTokensAttributesTransfer */
        $restAccessTokensAttributesTransfer = $restRequest->getResource()->getAttributes();

        return (new SecurityCheckAuthContextTransfer())
            ->setType(SecurityBlockerRestApiConfig::SECURITY_BLOCKER_AGENT_ENTITY_TYPE)
            ->setIp($restRequest->getHttpRequest()->getClientIp())
            ->setAccount($restAccessTokensAttributesTransfer->getUsername());
    }
}
