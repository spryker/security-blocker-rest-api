<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\SecurityBlockerRestApi\Api\Storefront\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use Spryker\Glue\AuthRestApi\Api\Storefront\EventSubscriber\TokenRequestSubscriber;
use Spryker\Glue\SecurityBlockerRestApi\Api\Storefront\EventSubscriber\StorefrontTokenLoginAttemptSubscriber;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group SecurityBlockerRestApi
 * @group Api
 * @group Storefront
 * @group EventSubscriber
 * @group StorefrontTokenLoginAttemptSubscriberTest
 * Add your own group annotations below this line
 */
class StorefrontTokenLoginAttemptSubscriberTest extends Unit
{
    /**
     * @uses \Spryker\Glue\SecurityBlockerRestApi\EventSubscriber\AbstractTokenLoginAttemptSubscriber::ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER
     */
    protected const string ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER = '_security_blocker_login_identifier';

    protected const string USERNAME = 'victim@example.com';

    protected const string CLIENT_IP = '203.0.113.1';

    public function testSubscriberRunsBeforeTheTokenRequestSubscriberOfTheEndpoint(): void
    {
        // Act
        $subscribedEvents = StorefrontTokenLoginAttemptSubscriber::getSubscribedEvents();

        // Assert
        $this->assertGreaterThan(
            TokenRequestSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1],
            $subscribedEvents[KernelEvents::REQUEST][1],
        );
    }

    public function testPasswordGrantIsCheckedAndReservedForTheCustomerRealm(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $requestEvent = $this->createRequestEvent($this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'secret']));

        // Assert
        $securityBlockerClientMock->expects($this->once())
            ->method('reserveLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $this->isExpectedContext($securityCheckAuthContextTransfer)))
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));

        // Act
        $this->createSubscriber($securityBlockerClientMock)->onKernelRequest($requestEvent);

        // Assert
        $this->assertNull($requestEvent->getResponse());
    }

    /**
     * The endpoint reads the body through `OauthRequestTransfer::fromArray()`, which also accepts alias keys and lets the
     * last key win. A body the subscriber read differently would be authenticated without being throttled, or would
     * have its attempt counted against a decoy.
     *
     * @dataProvider provideBodiesTheEndpointAuthenticatesAsTheVictim
     *
     * @param array<string, string> $body
     */
    public function testTheIdentifierTheEndpointAuthenticatesIsReservedWhateverKeysTheBodyUses(array $body): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $request = $this->createTokenRequest($body);

        // Assert
        $this->assertSame(static::USERNAME, (new OauthRequestTransfer())->fromArray($body, true)->getUsername());
        $securityBlockerClientMock->expects($this->once())
            ->method('reserveLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $this->isExpectedContext($securityCheckAuthContextTransfer)))
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));
        $securityBlockerClientMock->expects($this->once())
            ->method('registerFailedLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $this->isExpectedContext($securityCheckAuthContextTransfer)));

        // Act
        $subscriber->onKernelRequest($this->createRequestEvent($request));
        $subscriber->onKernelResponse($this->createResponseEvent($request, Response::HTTP_BAD_REQUEST));
    }

    /**
     * @return array<string, array<array<string, string>>>
     */
    protected function provideBodiesTheEndpointAuthenticatesAsTheVictim(): array
    {
        return [
            'camel case grant type' => [['grantType' => 'password', 'username' => static::USERNAME, 'password' => 'guess']],
            'pascal case keys' => [['GrantType' => 'password', 'Username' => static::USERNAME, 'password' => 'guess']],
            'decoy username before the alias the endpoint keeps' => [['grant_type' => 'password', 'username' => 'decoy@example.com', 'Username' => static::USERNAME, 'password' => 'guess']],
        ];
    }

    public function testBlockedLoginIsAnsweredWithTooManyRequestsBeforeTheCredentialsAreChecked(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(true, false);
        $requestEvent = $this->createRequestEvent($this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'secret']));

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');

        // Act
        $this->createSubscriber($securityBlockerClientMock)->onKernelRequest($requestEvent);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $requestEvent->getResponse()->getStatusCode());
        $this->assertSame(
            ['error' => 'temporarily_unavailable', 'error_description' => (new SecurityBlockerRestApiConfig())->getTokenEndpointBlockedErrorDescription()],
            json_decode((string)$requestEvent->getResponse()->getContent(), true),
        );
        $this->assertTrue($requestEvent->isPropagationStopped());
    }

    public function testLoginBeyondTheReservedAccountLimitIsAnsweredWithTooManyRequests(): void
    {
        // Arrange
        $requestEvent = $this->createRequestEvent($this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'secret']));

        // Act
        $this->createSubscriber($this->createSecurityBlockerClientMock(false, true))->onKernelRequest($requestEvent);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $requestEvent->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider provideRequestsThatAreNotPasswordGrantLogins
     */
    public function testRequestsThatAreNotPasswordGrantLoginsAreIgnored(Request $request): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $requestEvent = $this->createRequestEvent($request);

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('checkLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');

        // Act
        $this->createSubscriber($securityBlockerClientMock)->onKernelRequest($requestEvent);

        // Assert
        $this->assertNull($requestEvent->getResponse());
    }

    /**
     * @return array<string, array<\Symfony\Component\HttpFoundation\Request>>
     */
    protected function provideRequestsThatAreNotPasswordGrantLogins(): array
    {
        return [
            'refresh token grant' => [$this->createTokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => 'token', 'username' => static::USERNAME])],
            'another path' => [Request::create('/access-tokens', Request::METHOD_POST, ['grant_type' => 'password', 'username' => static::USERNAME])],
            'not a POST' => [Request::create('/token', Request::METHOD_GET, ['grant_type' => 'password', 'username' => static::USERNAME])],
            'username in the query string only' => [Request::create('/token?username=' . static::USERNAME, Request::METHOD_POST, ['grant_type' => 'password'])],
            'username as an array' => [$this->createTokenRequest(['grant_type' => 'password', 'username' => [static::USERNAME]])],
        ];
    }

    public function testSuccessfulTokenResponseResetsTheAccountCounter(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $request = $this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'secret']);
        $subscriber->onKernelRequest($this->createRequestEvent($request));

        // Assert
        $securityBlockerClientMock->expects($this->once())
            ->method('registerSuccessfulLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $this->isExpectedContext($securityCheckAuthContextTransfer)));
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');

        // Act
        $subscriber->onKernelResponse($this->createResponseEvent($request, Response::HTTP_OK));
    }

    public function testFailedTokenResponseRegistersAFailedLoginAttempt(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $request = $this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'wrong']);
        $subscriber->onKernelRequest($this->createRequestEvent($request));

        // Assert
        $securityBlockerClientMock->expects($this->once())->method('registerFailedLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        // Act
        $subscriber->onKernelResponse($this->createResponseEvent($request, Response::HTTP_BAD_REQUEST));
    }

    public function testOtherTokenResponsesAndBlockedRequestsRegisterNothing(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $serverErrorRequest = $this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'secret']);
        $subscriber->onKernelRequest($this->createRequestEvent($serverErrorRequest));
        $refreshRequest = $this->createTokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => 'token', 'username' => static::USERNAME]);
        $subscriber->onKernelRequest($this->createRequestEvent($refreshRequest));

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        // Act
        $subscriber->onKernelResponse($this->createResponseEvent($serverErrorRequest, Response::HTTP_INTERNAL_SERVER_ERROR));
        $subscriber->onKernelResponse($this->createResponseEvent($refreshRequest, Response::HTTP_OK));
    }

    public function testPasswordGrantIsNotThrottledUnderTheIpLoginProtectionStrategy(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(true, true);
        $requestEvent = $this->createRequestEvent($this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'secret']));

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('checkLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');

        // Act
        $this->createSubscriber($securityBlockerClientMock, SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP)->onKernelRequest($requestEvent);

        // Assert
        $this->assertNull($requestEvent->getResponse());
    }

    public function testLoginOutcomeIsNotRegisteredUnderTheIpLoginProtectionStrategy(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $request = $this->createTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME, 'password' => 'secret']);
        $request->attributes->set(static::ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER, static::USERNAME);

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        // Act
        $this->createSubscriber($securityBlockerClientMock, SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP)->onKernelResponse($this->createResponseEvent($request, Response::HTTP_BAD_REQUEST));
        $this->createSubscriber($securityBlockerClientMock, SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP)->onKernelResponse($this->createResponseEvent($request, Response::HTTP_OK));
    }

    protected function isExpectedContext(SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool
    {
        return $securityCheckAuthContextTransfer->getType() === SecurityBlockerRestApiConfig::SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE
            && $securityCheckAuthContextTransfer->getRealm() === SecurityBlockerRestApiConfig::LOGIN_REALM_CUSTOMER
            && $securityCheckAuthContextTransfer->getLoginIdentifier() === static::USERNAME
            && $securityCheckAuthContextTransfer->getAccount() === static::USERNAME
            && $securityCheckAuthContextTransfer->getIp() === static::CLIENT_IP;
    }

    protected function createSecurityBlockerClientMock(bool $isBlockedOnCheck, bool $isBlockedOnReservation): SecurityBlockerClientInterface&MockObject
    {
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('checkLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked($isBlockedOnCheck));
        $securityBlockerClientMock->method('reserveLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked($isBlockedOnReservation));

        return $securityBlockerClientMock;
    }

    protected function createSubscriber(
        SecurityBlockerClientInterface $securityBlockerClient,
        string $loginProtectionStrategy = SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT
    ): StorefrontTokenLoginAttemptSubscriber {
        $securityBlockerRestApiConfigMock = $this->getMockBuilder(SecurityBlockerRestApiConfig::class)
            ->onlyMethods(['getLoginProtectionStrategy'])
            ->getMock();
        $securityBlockerRestApiConfigMock->method('getLoginProtectionStrategy')->willReturn($loginProtectionStrategy);

        return new StorefrontTokenLoginAttemptSubscriber($securityBlockerClient, $securityBlockerRestApiConfigMock);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function createTokenRequest(array $parameters): Request
    {
        return Request::create('/token', Request::METHOD_POST, $parameters, [], [], ['REMOTE_ADDR' => static::CLIENT_IP]);
    }

    protected function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    protected function createResponseEvent(Request $request, int $status): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Response('', $status));
    }
}
