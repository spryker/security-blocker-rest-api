<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Validator;

use Generated\Shared\Transfer\RestErrorCollectionTransfer;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\RestErrorCollectionBuilderInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\SecurityCheckAuthContextBuilderInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Reserver\LoginAttemptReserverInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;

class SecurityBlockerAgentLoginValidator implements SecurityBlockerAgentValidatorInterface
{
    public function __construct(
        protected AuthenticationCheckerInterface $authenticationChecker,
        protected RestErrorCollectionBuilderInterface $restErrorCollectionBuilder,
        protected SecurityCheckAuthContextBuilderInterface $securityCheckAuthContextBuilder,
        protected LoginAttemptReserverInterface $loginAttemptReserver
    ) {
    }

    /**
     * Also reserves the attempt against the account before the credentials are checked, so a burst of parallel
     * requests cannot authenticate more often than the account limit allows.
     */
    public function isAccountBlocked(RestRequestInterface $restRequest): ?RestErrorCollectionTransfer
    {
        if (!$this->authenticationChecker->isAuthenticationRequest($restRequest, SecurityBlockerRestApiConfig::RESOURCE_AGENT_ACCESS_TOKENS)) {
            return null;
        }

        $securityCheckAuthResponseTransfer = $this->loginAttemptReserver->checkAndReserveLoginAttempts(
            $this->securityCheckAuthContextBuilder->createAgentSecurityCheckAuthContextTransfers($restRequest),
        );

        if (!$securityCheckAuthResponseTransfer->getIsBlocked()) {
            return null;
        }

        return $this->restErrorCollectionBuilder->createRestErrorCollectionTransfer(
            $securityCheckAuthResponseTransfer,
            $restRequest->getMetadata()->getLocale(),
        );
    }
}
