<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication;

use Generated\Shared\Transfer\RestErrorCollectionTransfer;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RestRequestValidatorPluginInterface;
use Spryker\Glue\Kernel\AbstractPlugin;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiFactory getFactory()
 * @method \Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig getConfig()
 */
class SecurityBlockerCustomerRestRequestValidatorPlugin extends AbstractPlugin implements RestRequestValidatorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Checks and denies access to the customer who submitted too many failed login attempts.
     * - With the `ip-and-account` login protection strategy of {@link \Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig::getLoginProtectionStrategy()}:
     *   also denies the login when the customer account (`customer` realm, regardless of the IP) is blocked, and otherwise counts the attempt against the account
     *   before the credentials are checked and denies it when the account limit is exceeded.
     * - Throws an exception when no strategy is registered for the selected key.
     *
     * @api
     *
     * @param \Symfony\Component\HttpFoundation\Request $httpRequest
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return \Generated\Shared\Transfer\RestErrorCollectionTransfer|null
     */
    public function validate(Request $httpRequest, RestRequestInterface $restRequest): ?RestErrorCollectionTransfer
    {
        return $this->getFactory()
            ->createLoginProtectionSecurityBlockerValidatorStrategyResolver()
            ->get($this->getConfig()->getLoginProtectionStrategy())
            ->isAccountBlocked($restRequest);
    }
}
