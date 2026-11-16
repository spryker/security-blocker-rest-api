<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Registrar;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;

interface LoginAttemptRegistrarInterface
{
    /**
     * @param list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer> $securityCheckAuthContextTransfers
     */
    public function registerLoginAttemptOutcome(
        array $securityCheckAuthContextTransfers,
        RestResponseInterface $restResponse,
        string $failedLoginResponseCode
    ): void;
}
