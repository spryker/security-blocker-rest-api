<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ControllerAfterActionPluginInterface;
use Spryker\Glue\Kernel\AbstractPlugin;

/**
 * @method \Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiFactory getFactory()
 * @method \Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig getConfig()
 */
class SecurityBlockerCustomerControllerAfterActionPlugin extends AbstractPlugin implements ControllerAfterActionPluginInterface
{
    /**
     * {@inheritDoc}
     * - Checks if the action is a failed login action and logs the attempt.
     * - With the `ip-and-account` login protection strategy of {@link \Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig::getLoginProtectionStrategy()}:
     *   registers a failed customer login (invalid credentials) against the per-IP and the realm-wide counters, resets the account counter
     *   of the `customer` realm after a successful customer login, and registers nothing for any other outcome (validation or server errors).
     * - Throws an exception when no strategy is registered for the selected key.
     *
     * @api
     *
     * @param string $action
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface $restResponse
     *
     * @return void
     */
    public function afterAction(string $action, RestRequestInterface $restRequest, RestResponseInterface $restResponse): void
    {
        $this->getFactory()
            ->createLoginProtectionSecurityBlockerStorageStrategyResolver()
            ->get($this->getConfig()->getLoginProtectionStrategy())
            ->incrementLoginAttemptCount($action, $restRequest, $restResponse);
    }
}
