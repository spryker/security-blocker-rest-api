<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Reserver;

use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;

/**
 * Every identifier is checked before any is reserved, so a blocked identifier never consumes an attempt of another one.
 * The reservation happens before the credentials are checked, so a parallel burst cannot exceed the account limit.
 */
class LoginAttemptReserver implements LoginAttemptReserverInterface
{
    public function __construct(protected SecurityBlockerRestApiToSecurityBlockerClientInterface $securityBlockerClient)
    {
    }

    /**
     * @param list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer> $securityCheckAuthContextTransfers
     */
    public function checkAndReserveLoginAttempts(array $securityCheckAuthContextTransfers): SecurityCheckAuthResponseTransfer
    {
        $securityCheckAuthResponseTransfer = new SecurityCheckAuthResponseTransfer();

        foreach ($securityCheckAuthContextTransfers as $securityCheckAuthContextTransfer) {
            $securityCheckAuthResponseTransfer = $this->securityBlockerClient->checkLoginAttempt($securityCheckAuthContextTransfer);

            if ($securityCheckAuthResponseTransfer->getIsBlocked()) {
                return $securityCheckAuthResponseTransfer;
            }
        }

        foreach ($securityCheckAuthContextTransfers as $securityCheckAuthContextTransfer) {
            $securityCheckAuthResponseTransfer = $this->securityBlockerClient->reserveLoginAttempt($securityCheckAuthContextTransfer);

            if ($securityCheckAuthResponseTransfer->getIsBlocked()) {
                return $securityCheckAuthResponseTransfer;
            }
        }

        return $securityCheckAuthResponseTransfer;
    }
}
