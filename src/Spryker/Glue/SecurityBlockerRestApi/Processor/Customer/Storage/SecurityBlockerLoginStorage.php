<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Storage;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\SecurityCheckAuthContextBuilderInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Registrar\LoginAttemptRegistrarInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;

class SecurityBlockerLoginStorage implements SecurityBlockerStorageInterface
{
    /**
     * @uses \Spryker\Glue\AuthRestApi\AuthRestApiConfig::RESPONSE_INVALID_LOGIN
     */
    protected const string RESPONSE_INVALID_LOGIN = '003';

    public function __construct(
        protected AuthenticationCheckerInterface $authenticationChecker,
        protected SecurityCheckAuthContextBuilderInterface $securityCheckAuthContextBuilder,
        protected LoginAttemptRegistrarInterface $loginAttemptRegistrar
    ) {
    }

    public function incrementLoginAttemptCount(
        string $action,
        RestRequestInterface $restRequest,
        RestResponseInterface $restResponse
    ): void {
        if (!$this->authenticationChecker->isAuthenticationRequest($restRequest, SecurityBlockerRestApiConfig::RESOURCE_ACCESS_TOKENS)) {
            return;
        }

        $this->loginAttemptRegistrar->registerLoginAttemptOutcome(
            $this->securityCheckAuthContextBuilder->createCustomerSecurityCheckAuthContextTransfers($restRequest),
            $restResponse,
            static::RESPONSE_INVALID_LOGIN,
        );
    }
}
