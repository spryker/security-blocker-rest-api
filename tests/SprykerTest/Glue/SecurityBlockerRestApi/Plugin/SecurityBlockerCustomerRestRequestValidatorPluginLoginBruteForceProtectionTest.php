<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\SecurityBlockerRestApi\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RestErrorCollectionTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerCustomerRestRequestValidatorPlugin;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Validator\SecurityBlockerValidatorInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group SecurityBlockerRestApi
 * @group Plugin
 * @group SecurityBlockerCustomerRestRequestValidatorPluginLoginBruteForceProtectionTest
 * Add your own group annotations below this line
 */
class SecurityBlockerCustomerRestRequestValidatorPluginLoginBruteForceProtectionTest extends Unit
{
    protected const string UNKNOWN_LOGIN_PROTECTION_STRATEGY = 'unknown';

    /**
     * @var \SprykerTest\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiTester
     */
    protected $tester;

    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerCustomerRestRequestValidatorPlugin
     */
    protected $securityBlockerCustomerRestRequestValidatorPlugin;

    /**
     * @var \Generated\Shared\Transfer\SecurityCheckAuthContextTransfer
     */
    protected $securityCheckAuthContextTransfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityCheckAuthContextTransfer = $this->tester
            ->getLoginSecurityCheckAuthContextTransfer(SecurityBlockerRestApiConfig::SECURITY_BLOCKER_CUSTOMER_ENTITY_TYPE);
    }

    public function testPluginUsesTheIpOnlyFlowForTheIpLoginProtectionStrategy(): void
    {
        // Arrange
        $validator = $this->createMock(SecurityBlockerValidatorInterface::class);
        $securityBlockerRestApiFactoryMock = $this->createFactoryMock();
        $securityBlockerRestApiFactoryMock->method('createSecurityBlockerValidator')->willReturn($validator);
        $securityBlockerCustomerRestRequestValidatorPlugin = new SecurityBlockerCustomerRestRequestValidatorPlugin();
        $securityBlockerCustomerRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $securityBlockerCustomerRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP));

        // Assert
        $securityBlockerRestApiFactoryMock->expects($this->never())->method('createSecurityBlockerLoginValidator');
        $validator->expects($this->once())->method('isAccountBlocked');

        // Act
        $securityBlockerCustomerRestRequestValidatorPlugin->validate(new Request(), $this->createMock(RestRequestInterface::class));
    }

    public function testPluginUsesTheLoginFlowForTheIpAndAccountLoginProtectionStrategy(): void
    {
        // Arrange
        $validator = $this->createMock(SecurityBlockerValidatorInterface::class);
        $securityBlockerRestApiFactoryMock = $this->createFactoryMock();
        $securityBlockerRestApiFactoryMock->method('createSecurityBlockerLoginValidator')->willReturn($validator);
        $securityBlockerCustomerRestRequestValidatorPlugin = new SecurityBlockerCustomerRestRequestValidatorPlugin();
        $securityBlockerCustomerRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $securityBlockerCustomerRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        // Assert
        $securityBlockerRestApiFactoryMock->expects($this->never())->method('createSecurityBlockerValidator');
        $validator->expects($this->once())->method('isAccountBlocked');

        // Act
        $securityBlockerCustomerRestRequestValidatorPlugin->validate(new Request(), $this->createMock(RestRequestInterface::class));
    }

    public function testPluginFailsForALoginProtectionStrategyWithoutARegisteredValidator(): void
    {
        // Arrange
        $securityBlockerCustomerRestRequestValidatorPlugin = new SecurityBlockerCustomerRestRequestValidatorPlugin();
        $securityBlockerCustomerRestRequestValidatorPlugin->setFactory($this->createFactoryMock());
        $securityBlockerCustomerRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(static::UNKNOWN_LOGIN_PROTECTION_STRATEGY));

        // Expect
        $this->expectException(InvalidArgumentException::class);

        // Act
        $securityBlockerCustomerRestRequestValidatorPlugin->validate(new Request(), $this->createMock(RestRequestInterface::class));
    }

    protected function createSecurityBlockerRestApiConfigMock(string $loginProtectionStrategy): SecurityBlockerRestApiConfig
    {
        $securityBlockerRestApiConfigMock = $this->createPartialMock(SecurityBlockerRestApiConfig::class, ['getLoginProtectionStrategy']);
        $securityBlockerRestApiConfigMock->method('getLoginProtectionStrategy')->willReturn($loginProtectionStrategy);

        return $securityBlockerRestApiConfigMock;
    }

    protected function createFactoryMock(): SecurityBlockerRestApiFactory&MockObject
    {
        return $this->createPartialMock(SecurityBlockerRestApiFactory::class, ['createSecurityBlockerValidator', 'createSecurityBlockerLoginValidator']);
    }

    public function testSecurityBlockerCustomerRestRequestValidatorPluginWillCallSecurityBlockerClient(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->expects($this->once())
            ->method('checkLoginAttempt')
            ->with($this->securityCheckAuthContextTransfer)
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true));
        $securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');

        $securityBlockerRestApiFactoryMock = $this->getMockBuilder(SecurityBlockerRestApiFactory::class)->onlyMethods(['getSecurityBlockerClient', 'getGlossaryStorageClient'])->getMock();
        $securityBlockerRestApiFactoryMock->method('getSecurityBlockerClient')->willReturn($securityBlockerClientMock);

        $this->securityBlockerCustomerRestRequestValidatorPlugin = new SecurityBlockerCustomerRestRequestValidatorPlugin();
        $this->securityBlockerCustomerRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerCustomerRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restResourceMock = $this->getRestResourceMock($this->securityCheckAuthContextTransfer);
        $restRequestMock = $this->getRestRequestMock($restResourceMock, $this->securityCheckAuthContextTransfer);

        // Act
        $this->securityBlockerCustomerRestRequestValidatorPlugin->validate(
            $restRequestMock->getHttpRequest(),
            $restRequestMock,
        );
    }

    public function testSecurityBlockerCustomerRestRequestValidatorPluginWillNotCallSecurityBlockerClientOnInvalidRequest(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->expects($this->never())->method('checkLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');

        $securityBlockerRestApiFactoryMock = $this->getMockBuilder(SecurityBlockerRestApiFactory::class)->onlyMethods(['getSecurityBlockerClient', 'getGlossaryStorageClient'])->getMock();
        $securityBlockerRestApiFactoryMock->method('getSecurityBlockerClient')->willReturn($securityBlockerClientMock);

        $this->securityBlockerCustomerRestRequestValidatorPlugin = new SecurityBlockerCustomerRestRequestValidatorPlugin();
        $this->securityBlockerCustomerRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerCustomerRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restResourceMock = $this->getRestResourceMock($this->securityCheckAuthContextTransfer);
        $restRequestMock = $this->getInvalidRestRequestMock($restResourceMock, $this->securityCheckAuthContextTransfer);

        // Act
        $this->securityBlockerCustomerRestRequestValidatorPlugin->validate(
            $restRequestMock->getHttpRequest(),
            $restRequestMock,
        );
    }

    /**
     * The reservation happens before the credentials are checked, so a parallel burst cannot exceed the account limit.
     */
    public function testSecurityBlockerCustomerRestRequestValidatorPluginReservesTheAttemptWhenTheAccountIsOpen(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->method('checkLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));
        $securityBlockerClientMock->expects($this->once())
            ->method('reserveLoginAttempt')
            ->with($this->securityCheckAuthContextTransfer)
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));

        // Act
        $restErrorCollectionTransfer = $this->validate($securityBlockerClientMock);

        // Assert
        $this->assertNull($restErrorCollectionTransfer);
    }

    public function testSecurityBlockerCustomerRestRequestValidatorPluginRejectsTheAttemptWhenTheReservationExceedsTheAccountLimit(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->method('checkLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));
        $securityBlockerClientMock->method('reserveLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true)->setBlockedFor(360));

        // Act
        $restErrorCollectionTransfer = $this->validate($securityBlockerClientMock);

        // Assert
        $this->assertNotNull($restErrorCollectionTransfer);
        $this->assertSame(429, $restErrorCollectionTransfer->getRestErrors()[0]->getStatus());
        $this->assertSame(SecurityBlockerRestApiConfig::ERROR_RESPONSE_CODE_ACCOUNT_BLOCKED, $restErrorCollectionTransfer->getRestErrors()[0]->getCode());
    }

    protected function validate(SecurityBlockerRestApiToSecurityBlockerClientInterface $securityBlockerClientMock): ?RestErrorCollectionTransfer
    {
        $securityBlockerRestApiFactoryMock = $this->getMockBuilder(SecurityBlockerRestApiFactory::class)->onlyMethods(['getSecurityBlockerClient', 'getGlossaryStorageClient'])->getMock();
        $securityBlockerRestApiFactoryMock->method('getSecurityBlockerClient')->willReturn($securityBlockerClientMock);

        $this->securityBlockerCustomerRestRequestValidatorPlugin = new SecurityBlockerCustomerRestRequestValidatorPlugin();
        $this->securityBlockerCustomerRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerCustomerRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restRequestMock = $this->getRestRequestMock($this->getRestResourceMock($this->securityCheckAuthContextTransfer), $this->securityCheckAuthContextTransfer);

        return $this->securityBlockerCustomerRestRequestValidatorPlugin->validate($restRequestMock->getHttpRequest(), $restRequestMock);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface
     */
    protected function getRestResourceMock(
        SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer
    ): RestResourceInterface {
        $restResourceMock = $this->getMockBuilder(RestResourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $restResourceMock->method('getType')->willReturn(SecurityBlockerRestApiConfig::RESOURCE_ACCESS_TOKENS);
        $restResourceMock->method('getAttributes')->willReturn($this->tester->getRestAccessTokensAttributesTransfer($securityCheckAuthContextTransfer));

        return $restResourceMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface
     */
    protected function getRestRequestMock(
        RestResourceInterface $restResourceMock,
        SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer
    ): RestRequestInterface {
        $requestMock = $this->getMockBuilder(Request::class)->getMock();
        $requestMock->method('getMethod')->willReturn(Request::METHOD_POST);
        $requestMock->method('getClientIp')->willReturn($securityCheckAuthContextTransfer->getIp());

        $restRequestMock = $this->getMockBuilder(RestRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $restRequestMock->method('getResource')->willReturn($restResourceMock);
        $restRequestMock->method('getHttpRequest')->willReturn($requestMock);

        return $restRequestMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface
     */
    protected function getInvalidRestRequestMock(
        RestResourceInterface $restResourceMock,
        SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer
    ): RestRequestInterface {
        $requestMock = $this->getMockBuilder(Request::class)->getMock();
        $requestMock->method('getMethod')->willReturn(Request::METHOD_GET);
        $requestMock->method('getClientIp')->willReturn($securityCheckAuthContextTransfer->getIp());

        $restRequestMock = $this->getMockBuilder(RestRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $restRequestMock->method('getResource')->willReturn($restResourceMock);
        $restRequestMock->method('getHttpRequest')->willReturn($requestMock);

        return $restRequestMock;
    }
}
