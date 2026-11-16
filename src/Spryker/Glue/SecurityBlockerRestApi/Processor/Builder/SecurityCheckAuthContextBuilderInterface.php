<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Builder;

use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;

interface SecurityCheckAuthContextBuilderInterface
{
    /**
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    public function createCustomerSecurityCheckAuthContextTransfers(RestRequestInterface $restRequest): array;

    /**
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    public function createAgentSecurityCheckAuthContextTransfers(RestRequestInterface $restRequest): array;
}
