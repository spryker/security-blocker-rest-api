<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\EventSubscriber;

use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Throttles the OAuth `POST /token` password grant, which bypasses the Glue request validator plugins.
 * API Platform registers the subscriber on its own, so it stays inactive until the login brute-force protection is enabled.
 *
 * Runs right before the token request subscriber of the API (priority 100): every login identifier the endpoint could
 * authenticate is checked and its attempt reserved before the credentials are verified. The outcome is registered for
 * the identifier the endpoint actually authenticated only, so a smuggled identifier can never have its counter reset.
 */
abstract class AbstractTokenLoginAttemptSubscriber implements EventSubscriberInterface
{
    protected const string TOKEN_PATH = '/token';

    protected const int PRIORITY_BEFORE_TOKEN_REQUEST_SUBSCRIBER = 101;

    protected const string ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER = '_security_blocker_login_identifier';

    /**
     * @uses \Spryker\Zed\Oauth\OauthConfig::GRANT_TYPE_PASSWORD
     */
    protected const string GRANT_TYPE_PASSWORD = 'password';

    /**
     * RFC 6749 has no error code for a throttled request; `temporarily_unavailable` is the closest registered one.
     */
    protected const string ERROR_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    /**
     * API Platform registers this subscriber itself, so no factory can resolve it by strategy key: it implements the
     * `ip-and-account` strategy for the `POST /token` endpoints and does nothing under any other key. Under the default
     * `ip` strategy the endpoints stay unthrottled.
     */
    protected const string LOGIN_PROTECTION_STRATEGY = SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT;

    public function __construct(
        protected SecurityBlockerClientInterface $securityBlockerClient,
        protected SecurityBlockerRestApiConfig $securityBlockerRestApiConfig,
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', static::PRIORITY_BEFORE_TOKEN_REQUEST_SUBSCRIBER],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isLoginProtectionStrategyApplicable()) {
            return;
        }

        $request = $event->getRequest();

        if (!$event->isMainRequest() || !$this->isTokenRequest($request) || !$this->isPasswordGrantRequest($request)) {
            return;
        }

        $loginIdentifiers = $this->readLoginIdentifiers($request);

        if ($loginIdentifiers === []) {
            return;
        }

        if (!$this->checkAndReserveLoginAttempts($request, $loginIdentifiers)) {
            $event->setResponse($this->createBlockedResponse($request));

            return;
        }

        $request->attributes->set(static::ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER, $this->findAuthenticatedLoginIdentifier($request));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->isLoginProtectionStrategyApplicable()) {
            return;
        }

        $request = $event->getRequest();
        $loginIdentifier = $request->attributes->get(static::ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER);

        if (!$event->isMainRequest() || !is_string($loginIdentifier)) {
            return;
        }

        $securityCheckAuthContextTransfer = $this->createSecurityCheckAuthContextTransfer($request, $loginIdentifier);

        if ($this->isFailedTokenResponse($request, $event->getResponse())) {
            $this->securityBlockerClient->registerFailedLoginAttempt($securityCheckAuthContextTransfer);

            return;
        }

        if (!$this->isSuccessfulTokenResponse($request, $event->getResponse())) {
            return;
        }

        $this->securityBlockerClient->registerSuccessfulLoginAttempt($securityCheckAuthContextTransfer);
    }

    abstract protected function isPasswordGrantRequest(Request $request): bool;

    /**
     * @return list<string> Every identifier the endpoint could authenticate, without duplicates.
     */
    abstract protected function readLoginIdentifiers(Request $request): array;

    abstract protected function findAuthenticatedLoginIdentifier(Request $request): ?string;

    abstract protected function isSuccessfulTokenResponse(Request $request, Response $response): bool;

    abstract protected function isFailedTokenResponse(Request $request, Response $response): bool;

    abstract protected function createBlockedResponse(Request $request): Response;

    abstract protected function getSecurityBlockerEntityType(): string;

    abstract protected function getLoginRealm(): string;

    protected function isLoginProtectionStrategyApplicable(): bool
    {
        return $this->securityBlockerRestApiConfig->getLoginProtectionStrategy() === static::LOGIN_PROTECTION_STRATEGY;
    }

    protected function isTokenRequest(Request $request): bool
    {
        return $request->getMethod() === Request::METHOD_POST
            && rtrim($request->getPathInfo(), '/') === static::TOKEN_PATH;
    }

    /**
     * @param list<string> $loginIdentifiers
     */
    protected function checkAndReserveLoginAttempts(Request $request, array $loginIdentifiers): bool
    {
        foreach ($loginIdentifiers as $loginIdentifier) {
            $securityCheckAuthContextTransfer = $this->createSecurityCheckAuthContextTransfer($request, $loginIdentifier);

            if ($this->securityBlockerClient->checkLoginAttempt($securityCheckAuthContextTransfer)->getIsBlocked()) {
                return false;
            }
        }

        foreach ($loginIdentifiers as $loginIdentifier) {
            $securityCheckAuthContextTransfer = $this->createSecurityCheckAuthContextTransfer($request, $loginIdentifier);

            if ($this->securityBlockerClient->reserveLoginAttempt($securityCheckAuthContextTransfer)->getIsBlocked()) {
                return false;
            }
        }

        return true;
    }

    protected function createSecurityCheckAuthContextTransfer(Request $request, string $loginIdentifier): SecurityCheckAuthContextTransfer
    {
        return (new SecurityCheckAuthContextTransfer())
            ->setType($this->getSecurityBlockerEntityType())
            ->setIp($request->getClientIp())
            ->setAccount($loginIdentifier)
            ->setRealm($this->getLoginRealm())
            ->setLoginIdentifier($loginIdentifier);
    }
}
