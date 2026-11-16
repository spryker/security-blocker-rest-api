<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\SecurityBlockerRestApi\Api\Backend\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use Spryker\Glue\OauthBackendApi\Api\Backend\EventSubscriber\TokenRequestSubscriber;
use Spryker\Glue\SecurityBlockerRestApi\Api\Backend\EventSubscriber\BackendTokenLoginAttemptSubscriber;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Spryker\Zed\Authentication\Business\AuthenticationFacadeInterface;
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
 * @group Backend
 * @group EventSubscriber
 * @group BackendTokenLoginAttemptSubscriberTest
 * Add your own group annotations below this line
 */
class BackendTokenLoginAttemptSubscriberTest extends Unit
{
    /**
     * @uses \Spryker\Glue\SecurityBlockerRestApi\EventSubscriber\AbstractTokenLoginAttemptSubscriber::ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER
     */
    protected const string ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER = '_security_blocker_login_identifier';

    protected const string USERNAME_ATTACKER = 'attacker@example.com';

    protected const string USERNAME_VICTIM = 'admin@example.com';

    protected const string CONTENT_TYPE_JSON_API = 'application/vnd.api+json';

    protected const string CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    public function testSubscriberRunsBeforeTheTokenRequestSubscriberOfTheEndpoint(): void
    {
        // Act
        $subscribedEvents = BackendTokenLoginAttemptSubscriber::getSubscribedEvents();

        // Assert
        $this->assertGreaterThan(
            TokenRequestSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1],
            $subscribedEvents[KernelEvents::REQUEST][1],
        );
    }

    public function testJsonApiTokenRequestIsReservedForTheUserRealmAndSuccessResetsTheAccount(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $request = $this->createJsonApiTokenRequest(static::USERNAME_VICTIM);

        // Assert
        $securityBlockerClientMock->expects($this->once())
            ->method('reserveLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $this->isUserContext($securityCheckAuthContextTransfer, static::USERNAME_VICTIM)))
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));
        $securityBlockerClientMock->expects($this->once())->method('registerSuccessfulLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');

        // Act
        $subscriber->onKernelRequest($this->createRequestEvent($request));
        $subscriber->onKernelResponse($this->createResponseEvent($request, Response::HTTP_CREATED));
    }

    public function testJsonApiInvalidCredentialsRegisterAFailedLoginAttemptAndValidationErrorsRegisterNothing(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $invalidCredentialsRequest = $this->createJsonApiTokenRequest(static::USERNAME_VICTIM);
        $validationErrorRequest = $this->createJsonApiTokenRequest(static::USERNAME_VICTIM);

        // Assert
        $securityBlockerClientMock->expects($this->once())->method('registerFailedLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        // Act
        $subscriber->onKernelRequest($this->createRequestEvent($invalidCredentialsRequest));
        $subscriber->onKernelResponse($this->createResponseEvent($invalidCredentialsRequest, Response::HTTP_UNAUTHORIZED));
        $subscriber->onKernelRequest($this->createRequestEvent($validationErrorRequest));
        $subscriber->onKernelResponse($this->createResponseEvent($validationErrorRequest, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    public function testBlockedJsonApiTokenRequestIsAnsweredWithAJsonApiTooManyRequestsError(): void
    {
        // Arrange
        $requestEvent = $this->createRequestEvent($this->createJsonApiTokenRequest(static::USERNAME_VICTIM));

        // Act
        $this->createSubscriber($this->createSecurityBlockerClientMock(true, false))->onKernelRequest($requestEvent);

        // Assert
        $response = $requestEvent->getResponse();
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame(static::CONTENT_TYPE_JSON_API, $response->headers->get('Content-Type'));
        $this->assertSame(SecurityBlockerRestApiConfig::ERROR_RESPONSE_CODE_ACCOUNT_BLOCKED, json_decode((string)$response->getContent(), true)['errors'][0]['code']);
    }

    public function testBlockedLegacyTokenRequestIsAnsweredInTheLegacyErrorFormat(): void
    {
        // Arrange
        $requestEvent = $this->createRequestEvent($this->createLegacyFormTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME_VICTIM]));

        // Act
        $this->createSubscriber($this->createSecurityBlockerClientMock(false, true))->onKernelRequest($requestEvent);

        // Assert
        $response = $requestEvent->getResponse();
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame('temporarily_unavailable', json_decode((string)$response->getContent(), true)[0]['code']);
    }

    /**
     * @dataProvider provideLegacyRequestsThatAreThrottled
     *
     * @param array<string, string> $parameters
     */
    public function testLegacyPasswordGrantAndGrantlessRequestsAreThrottled(array $parameters): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);

        // Assert
        $securityBlockerClientMock->expects($this->once())->method('reserveLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));

        // Act
        $this->createSubscriber($securityBlockerClientMock)->onKernelRequest($this->createRequestEvent($this->createLegacyFormTokenRequest($parameters)));
    }

    /**
     * @return array<string, array<array<string, string>>>
     */
    protected function provideLegacyRequestsThatAreThrottled(): array
    {
        return [
            'grant_type password' => [['grant_type' => 'password', 'username' => static::USERNAME_VICTIM]],
            'grantType password' => [['grantType' => 'password', 'username' => static::USERNAME_VICTIM]],
            'no grant type' => [['username' => static::USERNAME_VICTIM]],
        ];
    }

    public function testLegacyRefreshTokenGrantIsIgnoredEvenWithAUsername(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $request = $this->createLegacyFormTokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => 'token', 'username' => static::USERNAME_VICTIM]);

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        // Act
        $subscriber->onKernelRequest($this->createRequestEvent($request));
        $subscriber->onKernelResponse($this->createResponseEvent($request, Response::HTTP_OK));
    }

    /**
     * JSON text under a form content type: PHP fills the form bag too, and the endpoint authenticates the JSON user.
     * Both candidates must be reserved, but only the authenticated one may have its counter reset — otherwise a
     * valid own login with the victim smuggled into the other encoding would clear the victim's lock.
     */
    public function testEveryCandidateIsReservedButOnlyTheAuthenticatedOneIsReset(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $request = $this->createSmugglingRequest();
        $reservedLoginIdentifiers = [];

        // Assert
        $securityBlockerClientMock->expects($this->exactly(2))
            ->method('reserveLoginAttempt')
            ->willReturnCallback(function (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer) use (&$reservedLoginIdentifiers): SecurityCheckAuthResponseTransfer {
                $reservedLoginIdentifiers[] = $securityCheckAuthContextTransfer->getLoginIdentifier();

                return (new SecurityCheckAuthResponseTransfer())->setIsBlocked(false);
            });
        $securityBlockerClientMock->expects($this->once())
            ->method('registerSuccessfulLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $securityCheckAuthContextTransfer->getLoginIdentifier() === static::USERNAME_ATTACKER));

        // Act
        $subscriber->onKernelRequest($this->createRequestEvent($request));
        $subscriber->onKernelResponse($this->createResponseEvent($request, Response::HTTP_OK));

        // Assert
        $this->assertEqualsCanonicalizing([static::USERNAME_ATTACKER, static::USERNAME_VICTIM], $reservedLoginIdentifiers);
    }

    /**
     * Pins the authenticated identifier to the parser of the endpoint, so the two cannot drift apart unnoticed.
     *
     * @dataProvider provideLegacyBodyEncodings
     */
    public function testAuthenticatedIdentifierMatchesTheUsernameTheEndpointAuthenticates(Request $request): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false, false);
        $subscriber = $this->createSubscriber($securityBlockerClientMock);
        $endpointUsername = $this->createEndpointParserProbe()->createOauthRequestTransferForRequest($request)->getUsername();

        // Assert
        $securityBlockerClientMock->expects($this->once())
            ->method('registerSuccessfulLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $securityCheckAuthContextTransfer->getLoginIdentifier() === $endpointUsername));

        // Act
        $subscriber->onKernelRequest($this->createRequestEvent($request));
        $subscriber->onKernelResponse($this->createResponseEvent($request, Response::HTTP_OK));
    }

    /**
     * @return array<string, array<\Symfony\Component\HttpFoundation\Request>>
     */
    protected function provideLegacyBodyEncodings(): array
    {
        return [
            'form fields' => [$this->createLegacyFormTokenRequest(['grant_type' => 'password', 'username' => static::USERNAME_VICTIM])],
            'json body' => [Request::create('/token', Request::METHOD_POST, [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['grant_type' => 'password', 'username' => static::USERNAME_VICTIM]))],
            'raw urlencoded body' => [Request::create('/token', Request::METHOD_POST, [], [], [], ['CONTENT_TYPE' => 'text/plain'], http_build_query(['grant_type' => 'password', 'username' => static::USERNAME_VICTIM]))],
            'json under form content type' => [$this->createSmugglingRequest()],
        ];
    }

    protected function createSmugglingRequest(): Request
    {
        return Request::create(
            '/token',
            Request::METHOD_POST,
            ['grant_type' => 'password', 'username' => static::USERNAME_VICTIM],
            [],
            [],
            ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM],
            json_encode(['grant_type' => 'password', 'username' => static::USERNAME_ATTACKER]),
        );
    }

    public function testPasswordGrantIsNotThrottledUnderTheIpLoginProtectionStrategy(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(true, true);
        $requestEvent = $this->createRequestEvent($this->createJsonApiTokenRequest(static::USERNAME_VICTIM));

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
        $request = $this->createJsonApiTokenRequest(static::USERNAME_VICTIM);
        $request->attributes->set(static::ATTRIBUTE_AUTHENTICATED_LOGIN_IDENTIFIER, static::USERNAME_VICTIM);

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        // Act
        $this->createSubscriber($securityBlockerClientMock, SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP)->onKernelResponse($this->createResponseEvent($request, Response::HTTP_BAD_REQUEST));
        $this->createSubscriber($securityBlockerClientMock, SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP)->onKernelResponse($this->createResponseEvent($request, Response::HTTP_OK));
    }

    protected function isUserContext(SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer, string $username): bool
    {
        return $securityCheckAuthContextTransfer->getType() === SecurityBlockerRestApiConfig::SECURITY_BLOCKER_BACKEND_API_USER_ENTITY_TYPE
            && $securityCheckAuthContextTransfer->getRealm() === SecurityBlockerRestApiConfig::LOGIN_REALM_USER
            && $securityCheckAuthContextTransfer->getLoginIdentifier() === $username
            && $securityCheckAuthContextTransfer->getAccount() === $username;
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
    ): BackendTokenLoginAttemptSubscriber {
        $securityBlockerRestApiConfigMock = $this->getMockBuilder(SecurityBlockerRestApiConfig::class)
            ->onlyMethods(['getLoginProtectionStrategy'])
            ->getMock();
        $securityBlockerRestApiConfigMock->method('getLoginProtectionStrategy')->willReturn($loginProtectionStrategy);

        return new BackendTokenLoginAttemptSubscriber($securityBlockerClient, $securityBlockerRestApiConfigMock);
    }

    protected function createEndpointParserProbe(): TokenRequestSubscriber
    {
        return new class ($this->createMock(AuthenticationFacadeInterface::class)) extends TokenRequestSubscriber {
            public function createOauthRequestTransferForRequest(Request $request): OauthRequestTransfer
            {
                return $this->createOauthRequestTransfer($request);
            }
        };
    }

    protected function createJsonApiTokenRequest(string $username): Request
    {
        return Request::create(
            '/token',
            Request::METHOD_POST,
            [],
            [],
            [],
            ['CONTENT_TYPE' => static::CONTENT_TYPE_JSON_API],
            json_encode(['data' => ['type' => 'tokens', 'attributes' => ['username' => $username, 'password' => 'secret']]]),
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    protected function createLegacyFormTokenRequest(array $parameters): Request
    {
        return Request::create('/token', Request::METHOD_POST, $parameters, [], [], ['CONTENT_TYPE' => static::CONTENT_TYPE_FORM]);
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
