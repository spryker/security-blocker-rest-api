<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Registrar;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface;

class LoginAttemptRegistrar implements LoginAttemptRegistrarInterface
{
    public function __construct(
        protected SecurityBlockerRestApiToSecurityBlockerClientInterface $securityBlockerClient,
        protected AuthenticationCheckerInterface $authenticationChecker
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer> $securityCheckAuthContextTransfers
     */
    public function registerLoginAttemptOutcome(
        array $securityCheckAuthContextTransfers,
        RestResponseInterface $restResponse,
        string $failedLoginResponseCode
    ): void {
        if ($this->authenticationChecker->isFailedAuthenticationResponse($restResponse, $failedLoginResponseCode)) {
            foreach ($securityCheckAuthContextTransfers as $securityCheckAuthContextTransfer) {
                $this->securityBlockerClient->registerFailedLoginAttempt($securityCheckAuthContextTransfer);
            }

            return;
        }

        if (count($securityCheckAuthContextTransfers) !== 1 || !$this->authenticationChecker->isSuccessfulAuthenticationResponse($restResponse)) {
            return;
        }

        $this->securityBlockerClient->registerSuccessfulLoginAttempt($securityCheckAuthContextTransfers[0]);
    }
}
