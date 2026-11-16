<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Reserver;

use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;

interface LoginAttemptReserverInterface
{
    /**
     * @param list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer> $securityCheckAuthContextTransfers
     */
    public function checkAndReserveLoginAttempts(array $securityCheckAuthContextTransfers): SecurityCheckAuthResponseTransfer;
}
