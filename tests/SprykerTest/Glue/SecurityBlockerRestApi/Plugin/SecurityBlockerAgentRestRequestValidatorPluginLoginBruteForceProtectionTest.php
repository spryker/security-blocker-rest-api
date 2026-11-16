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
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerAgentRestRequestValidatorPlugin;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Validator\SecurityBlockerAgentValidatorInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group SecurityBlockerRestApi
 * @group Plugin
 * @group SecurityBlockerAgentRestRequestValidatorPluginLoginBruteForceProtectionTest
 * Add your own group annotations below this line
 */
class SecurityBlockerAgentRestRequestValidatorPluginLoginBruteForceProtectionTest extends Unit
{
    /**
     * @var \SprykerTest\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiTester
     */
    protected $tester;

    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerAgentRestRequestValidatorPlugin
     */
    protected $securityBlockerAgentRestRequestValidatorPlugin;

    /**
     * @var \Generated\Shared\Transfer\SecurityCheckAuthContextTransfer
     */
    protected $securityCheckAuthContextTransfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityCheckAuthContextTransfer = $this->tester
            ->getLoginSecurityCheckAuthContextTransfer(SecurityBlockerRestApiConfig::SECURITY_BLOCKER_AGENT_ENTITY_TYPE);
    }

    public function testPluginUsesTheIpOnlyFlowForTheIpLoginProtectionStrategy(): void
    {
        // Arrange
        $validator = $this->createMock(SecurityBlockerAgentValidatorInterface::class);
        $securityBlockerRestApiFactoryMock = $this->createFactoryMock();
        $securityBlockerRestApiFactoryMock->method('createSecurityBlockerAgentValidator')->willReturn($validator);
        $securityBlockerAgentRestRequestValidatorPlugin = new SecurityBlockerAgentRestRequestValidatorPlugin();
        $securityBlockerAgentRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $securityBlockerAgentRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP));

        // Assert
        $securityBlockerRestApiFactoryMock->expects($this->never())->method('createSecurityBlockerAgentLoginValidator');
        $validator->expects($this->once())->method('isAccountBlocked');

        // Act
        $securityBlockerAgentRestRequestValidatorPlugin->validate(new Request(), $this->createMock(RestRequestInterface::class));
    }

    public function testPluginUsesTheLoginFlowForTheIpAndAccountLoginProtectionStrategy(): void
    {
        // Arrange
        $validator = $this->createMock(SecurityBlockerAgentValidatorInterface::class);
        $securityBlockerRestApiFactoryMock = $this->createFactoryMock();
        $securityBlockerRestApiFactoryMock->method('createSecurityBlockerAgentLoginValidator')->willReturn($validator);
        $securityBlockerAgentRestRequestValidatorPlugin = new SecurityBlockerAgentRestRequestValidatorPlugin();
        $securityBlockerAgentRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $securityBlockerAgentRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        // Assert
        $securityBlockerRestApiFactoryMock->expects($this->never())->method('createSecurityBlockerAgentValidator');
        $validator->expects($this->once())->method('isAccountBlocked');

        // Act
        $securityBlockerAgentRestRequestValidatorPlugin->validate(new Request(), $this->createMock(RestRequestInterface::class));
    }

    protected function createSecurityBlockerRestApiConfigMock(string $loginProtectionStrategy): SecurityBlockerRestApiConfig
    {
        $securityBlockerRestApiConfigMock = $this->createPartialMock(SecurityBlockerRestApiConfig::class, ['getLoginProtectionStrategy']);
        $securityBlockerRestApiConfigMock->method('getLoginProtectionStrategy')->willReturn($loginProtectionStrategy);

        return $securityBlockerRestApiConfigMock;
    }

    protected function createFactoryMock(): SecurityBlockerRestApiFactory&MockObject
    {
        return $this->createPartialMock(SecurityBlockerRestApiFactory::class, ['createSecurityBlockerAgentValidator', 'createSecurityBlockerAgentLoginValidator']);
    }

    public function testSecurityBlockerAgentRestRequestValidatorPluginWillCallSecurityBlockerClient(): void
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

        $this->securityBlockerAgentRestRequestValidatorPlugin = new SecurityBlockerAgentRestRequestValidatorPlugin();
        $this->securityBlockerAgentRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerAgentRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restResourceMock = $this->getRestResourceMock($this->securityCheckAuthContextTransfer);
        $restRequestMock = $this->getRestRequestMock($restResourceMock, $this->securityCheckAuthContextTransfer);

        // Act
        $this->securityBlockerAgentRestRequestValidatorPlugin->validate(
            $restRequestMock->getHttpRequest(),
            $restRequestMock,
        );
    }

    public function testSecurityBlockerAgentRestRequestValidatorPluginWillNotCallSecurityBlockerClientOnInvalidRequest(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->expects($this->never())->method('checkLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');

        $securityBlockerRestApiFactoryMock = $this->getMockBuilder(SecurityBlockerRestApiFactory::class)->onlyMethods(['getSecurityBlockerClient', 'getGlossaryStorageClient'])->getMock();
        $securityBlockerRestApiFactoryMock->method('getSecurityBlockerClient')->willReturn($securityBlockerClientMock);

        $this->securityBlockerAgentRestRequestValidatorPlugin = new SecurityBlockerAgentRestRequestValidatorPlugin();
        $this->securityBlockerAgentRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerAgentRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restResourceMock = $this->getRestResourceMock($this->securityCheckAuthContextTransfer);
        $restRequestMock = $this->getInvalidRestRequestMock($restResourceMock, $this->securityCheckAuthContextTransfer);

        // Act
        $this->securityBlockerAgentRestRequestValidatorPlugin->validate(
            $restRequestMock->getHttpRequest(),
            $restRequestMock,
        );
    }

    /**
     * The reservation happens before the credentials are checked, so a parallel burst cannot exceed the account limit.
     */
    public function testSecurityBlockerAgentRestRequestValidatorPluginReservesTheAttemptWhenTheAccountIsOpen(): void
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

    public function testSecurityBlockerAgentRestRequestValidatorPluginRejectsTheAttemptWhenTheReservationExceedsTheAccountLimit(): void
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

        $this->securityBlockerAgentRestRequestValidatorPlugin = new SecurityBlockerAgentRestRequestValidatorPlugin();
        $this->securityBlockerAgentRestRequestValidatorPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerAgentRestRequestValidatorPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restRequestMock = $this->getRestRequestMock($this->getRestResourceMock($this->securityCheckAuthContextTransfer), $this->securityCheckAuthContextTransfer);

        return $this->securityBlockerAgentRestRequestValidatorPlugin->validate($restRequestMock->getHttpRequest(), $restRequestMock);
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
        $restResourceMock->method('getType')->willReturn(SecurityBlockerRestApiConfig::RESOURCE_AGENT_ACCESS_TOKENS);
        $restResourceMock->method('getAttributes')->willReturn($this->tester->getRestAgentAccessTokensRequestAttributesTransfer($securityCheckAuthContextTransfer));

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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    protected function getRestResponseMock(): RestResponseInterface
    {
        $restResponseMock = $this->getMockBuilder(RestResponseInterface::class)->disableOriginalConstructor()->getMock();
        $restResponseMock->method('getStatus')->willReturn(Response::HTTP_UNAUTHORIZED);
        $restResponseMock->method('getErrors')->willReturn([$this->tester->getAgentRestErrorMessageTransfer()]);

        return $restResponseMock;
    }
}
