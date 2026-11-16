<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi;

use Spryker\Glue\Kernel\AbstractBundleConfig;

class SecurityBlockerRestApiConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @uses \Spryker\Glue\AuthRestApi\AuthRestApiConfig::RESOURCE_ACCESS_TOKENS
     *
     * @var string
     */
    public const RESOURCE_ACCESS_TOKENS = 'access-tokens';

    /**
     * @api
     *
     * @uses \Spryker\Glue\AgentAuthRestApi\AgentAuthRestApiConfig::RESOURCE_AGENT_ACCESS_TOKENS
     *
     * @var string
     */
    public const RESOURCE_AGENT_ACCESS_TOKENS = 'agent-access-tokens';

    /**
     * @api
     *
     * @uses \SprykerShop\Yves\SecurityBlockerPage\SecurityBlockerPageConfig::SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE
     *
     * @var string
     */
    public const SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE = 'customer';

    /**
     * @api
     *
     * @uses \SprykerShop\Yves\SecurityBlockerPage\SecurityBlockerPageConfig::SECURITY_BLOCKER_AGENT_ENTITY_TYPE
     *
     * @var string
     */
    public const SECURITY_BLOCKER_AGENT_ENTITY_TYPE = 'agent';

    /**
     * @api
     *
     * @uses \Spryker\Shared\SecurityBlockerStorefrontCustomer\SecurityBlockerStorefrontCustomerConfig::LOGIN_REALM_CUSTOMER
     */
    public const string LOGIN_REALM_CUSTOMER = 'customer';

    /**
     * @api
     *
     * @uses \Spryker\Shared\SecurityBlockerBackoffice\SecurityBlockerBackofficeConfig::LOGIN_REALM_USER
     */
    public const string LOGIN_REALM_USER = 'user';

    /**
     * @api
     *
     * @var string
     */
    public const ERROR_RESPONSE_CODE_ACCOUNT_BLOCKED = '4401';

    /**
     * @api
     *
     * @var string
     */
    public const ERROR_RESPONSE_DETAIL_ACCOUNT_BLOCKED = 'security_blocker_page.error.account_blocked';

    /**
     * Specification:
     * - Entity type of the per-IP login attempt counter of the Backend API `POST /token` endpoint.
     * - Falls back to the default security blocker settings unless a settings expander plugin provides this type.
     *
     * @api
     */
    public const string SECURITY_BLOCKER_BACKEND_API_USER_ENTITY_TYPE = 'backend-api-user';

    /**
     * Specification:
     * - Login protection strategy that counts and blocks the failed `access-tokens` and `agent-access-tokens` logins per source IP and username only.
     *
     * @api
     */
    public const string LOGIN_PROTECTION_STRATEGY_IP = 'ip';

    /**
     * Specification:
     * - Login protection strategy with two separate counters of failed `access-tokens` and `agent-access-tokens` logins:
     *   one per source IP and username, and one per account.
     * - Blocks the login when either counter reaches its limit. The account counter ignores the source IP,
     *   so changing the IP does not unlock the account.
     * - Reserves every attempt before the credentials are checked, and also protects the OAuth `POST /token` endpoints.
     *
     * @api
     */
    public const string LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT = 'ip-and-account';

    protected const string TOKEN_ENDPOINT_BLOCKED_ERROR_DESCRIPTION = 'Too many login attempts. Please try again later.';

    protected const string LOGIN_PROTECTION_STRATEGY = self::LOGIN_PROTECTION_STRATEGY_IP;

    /**
     * Specification:
     * - Returns the `error_description` of the HTTP 429 response of the OAuth `POST /token` endpoints.
     * - RFC 6749 section 5.2 allows ASCII characters only, so the text is not translated.
     *
     * @api
     */
    public function getTokenEndpointBlockedErrorDescription(): string
    {
        return static::TOKEN_ENDPOINT_BLOCKED_ERROR_DESCRIPTION;
    }

    /**
     * Specification:
     * - Returns the key of the login protection strategy of the `access-tokens` and `agent-access-tokens` resources and the OAuth `POST /token` endpoints
     *   of the Storefront and Backend API.
     * - {@link LOGIN_PROTECTION_STRATEGY_IP} (default): `access-tokens` and `agent-access-tokens` count and block failed logins by the per-IP counter only,
     *   and the `POST /token` endpoints are not throttled.
     * - {@link LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT}: failed logins also lock the account regardless of the source IP, every attempt is reserved against
     *   the account before the credentials are checked, a successful login resets the account counter, and the `POST /token` endpoints are protected the same way.
     * - A project strategy adds its key to the `SecurityBlockerRestApiFactory::createLoginProtection*StrategyResolver()` methods; the `POST /token` endpoints protect nothing under it.
     * - A key no strategy resolver knows fails with an exception.
     *
     * @api
     */
    public function getLoginProtectionStrategy(): string
    {
        return static::LOGIN_PROTECTION_STRATEGY;
    }
}
