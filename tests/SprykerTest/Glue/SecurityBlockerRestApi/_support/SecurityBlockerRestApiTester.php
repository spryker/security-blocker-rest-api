<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\SecurityBlockerRestApi;

use Codeception\Actor;
use Generated\Shared\Transfer\RestAccessTokensAttributesTransfer;
use Generated\Shared\Transfer\RestAgentAccessTokensRequestAttributesTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class SecurityBlockerRestApiTester extends Actor
{
    use _generated\SecurityBlockerRestApiTesterActions;

    /**
     * @uses \Spryker\Glue\AgentAuthRestApi\AgentAuthRestApiConfig::RESPONSE_CODE_INVALID_LOGIN
     *
     * @var string
     */
    protected const RESPONSE_CODE_INVALID_LOGIN = '4101';

    /**
     * @uses \Spryker\Glue\AuthRestApi\AuthRestApiConfig::RESPONSE_INVALID_LOGIN
     *
     * @var string
     */
    protected const RESPONSE_INVALID_LOGIN = '003';

    public function getSecurityCheckAuthContextTransfer(string $type): SecurityCheckAuthContextTransfer
    {
        return (new SecurityCheckAuthContextTransfer())
            ->setType($type)
            ->setAccount('test@spryker.com')
            ->setIp('66.66.66.6');
    }

    public function getLoginSecurityCheckAuthContextTransfer(string $type): SecurityCheckAuthContextTransfer
    {
        $securityCheckAuthContextTransfer = $this->getSecurityCheckAuthContextTransfer($type);

        return $securityCheckAuthContextTransfer
            ->setRealm($type === SecurityBlockerRestApiConfig::SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE ? SecurityBlockerRestApiConfig::LOGIN_REALM_CUSTOMER : SecurityBlockerRestApiConfig::LOGIN_REALM_USER)
            ->setLoginIdentifier($securityCheckAuthContextTransfer->getAccount());
    }

    public function getRestAgentAccessTokensRequestAttributesTransfer(
        SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer
    ): RestAgentAccessTokensRequestAttributesTransfer {
        return (new RestAgentAccessTokensRequestAttributesTransfer())
            ->setUsername($securityCheckAuthContextTransfer->getAccount());
    }

    public function getRestAccessTokensAttributesTransfer(
        SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer
    ): RestAccessTokensAttributesTransfer {
        return (new RestAccessTokensAttributesTransfer())
            ->setUsername($securityCheckAuthContextTransfer->getAccount());
    }

    public function getAgentRestErrorMessageTransfer(): RestErrorMessageTransfer
    {
        return (new RestErrorMessageTransfer())
            ->setCode(static::RESPONSE_CODE_INVALID_LOGIN)
            ->setStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function getCustomerRestErrorMessageTransfer(): RestErrorMessageTransfer
    {
        return (new RestErrorMessageTransfer())
            ->setCode(static::RESPONSE_INVALID_LOGIN)
            ->setStatus(Response::HTTP_UNAUTHORIZED);
    }
}
