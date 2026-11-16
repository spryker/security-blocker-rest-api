<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\SecurityBlockerRestApi\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerAgentControllerAfterActionPlugin;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Storage\SecurityBlockerAgentStorageInterface;
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
 * @group SecurityBlockerAgentControllerAfterActionPluginLoginBruteForceProtectionTest
 * Add your own group annotations below this line
 */
class SecurityBlockerAgentControllerAfterActionPluginLoginBruteForceProtectionTest extends Unit
{
    /**
     * @var \SprykerTest\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiTester
     */
    protected $tester;

    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerAgentControllerAfterActionPlugin
     */
    protected $securityBlockerAgentControllerAfterActionPlugin;

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
        $storage = $this->createMock(SecurityBlockerAgentStorageInterface::class);
        $securityBlockerRestApiFactoryMock = $this->createFactoryMock();
        $securityBlockerRestApiFactoryMock->method('createSecurityBlockerAgentStorage')->willReturn($storage);
        $securityBlockerAgentControllerAfterActionPlugin = new SecurityBlockerAgentControllerAfterActionPlugin();
        $securityBlockerAgentControllerAfterActionPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $securityBlockerAgentControllerAfterActionPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP));

        // Assert
        $securityBlockerRestApiFactoryMock->expects($this->never())->method('createSecurityBlockerAgentLoginStorage');
        $storage->expects($this->once())->method('incrementLoginAttemptCount');

        // Act
        $securityBlockerAgentControllerAfterActionPlugin->afterAction('post', $this->createMock(RestRequestInterface::class), $this->createMock(RestResponseInterface::class));
    }

    public function testPluginUsesTheLoginFlowForTheIpAndAccountLoginProtectionStrategy(): void
    {
        // Arrange
        $storage = $this->createMock(SecurityBlockerAgentStorageInterface::class);
        $securityBlockerRestApiFactoryMock = $this->createFactoryMock();
        $securityBlockerRestApiFactoryMock->method('createSecurityBlockerAgentLoginStorage')->willReturn($storage);
        $securityBlockerAgentControllerAfterActionPlugin = new SecurityBlockerAgentControllerAfterActionPlugin();
        $securityBlockerAgentControllerAfterActionPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $securityBlockerAgentControllerAfterActionPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        // Assert
        $securityBlockerRestApiFactoryMock->expects($this->never())->method('createSecurityBlockerAgentStorage');
        $storage->expects($this->once())->method('incrementLoginAttemptCount');

        // Act
        $securityBlockerAgentControllerAfterActionPlugin->afterAction('post', $this->createMock(RestRequestInterface::class), $this->createMock(RestResponseInterface::class));
    }

    protected function createSecurityBlockerRestApiConfigMock(string $loginProtectionStrategy): SecurityBlockerRestApiConfig
    {
        $securityBlockerRestApiConfigMock = $this->createPartialMock(SecurityBlockerRestApiConfig::class, ['getLoginProtectionStrategy']);
        $securityBlockerRestApiConfigMock->method('getLoginProtectionStrategy')->willReturn($loginProtectionStrategy);

        return $securityBlockerRestApiConfigMock;
    }

    protected function createFactoryMock(): SecurityBlockerRestApiFactory&MockObject
    {
        return $this->createPartialMock(SecurityBlockerRestApiFactory::class, ['createSecurityBlockerAgentStorage', 'createSecurityBlockerAgentLoginStorage']);
    }

    public function testSecurityBlockerAgentControllerAfterActionPluginWillCallSecurityBlockerClient(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->expects($this->once())
            ->method('registerFailedLoginAttempt')
            ->with($this->securityCheckAuthContextTransfer);
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        $securityBlockerRestApiFactoryMock = $this->getMockBuilder(SecurityBlockerRestApiFactory::class)->onlyMethods(['getSecurityBlockerClient'])->getMock();
        $securityBlockerRestApiFactoryMock->method('getSecurityBlockerClient')->willReturn($securityBlockerClientMock);

        $this->securityBlockerAgentControllerAfterActionPlugin = new SecurityBlockerAgentControllerAfterActionPlugin();
        $this->securityBlockerAgentControllerAfterActionPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerAgentControllerAfterActionPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restResourceMock = $this->getRestResourceMock($this->securityCheckAuthContextTransfer);
        $restRequestMock = $this->getRestRequestMock($restResourceMock, $this->securityCheckAuthContextTransfer);
        $restResponseMock = $this->getRestResponseMock();

        // Act
        $this->securityBlockerAgentControllerAfterActionPlugin->afterAction(
            SecurityBlockerRestApiConfig::RESOURCE_AGENT_ACCESS_TOKENS,
            $restRequestMock,
            $restResponseMock,
        );
    }

    public function testSecurityBlockerAgentControllerAfterActionPluginWillNotCallSecurityBlockerClientOnInvalidRequest(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        $securityBlockerRestApiFactoryMock = $this->getMockBuilder(SecurityBlockerRestApiFactory::class)->onlyMethods(['getSecurityBlockerClient'])->getMock();
        $securityBlockerRestApiFactoryMock->method('getSecurityBlockerClient')->willReturn($securityBlockerClientMock);

        $this->securityBlockerAgentControllerAfterActionPlugin = new SecurityBlockerAgentControllerAfterActionPlugin();
        $this->securityBlockerAgentControllerAfterActionPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerAgentControllerAfterActionPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restResourceMock = $this->getRestResourceMock($this->securityCheckAuthContextTransfer);
        $restRequestMock = $this->getInvalidRestRequestMock($restResourceMock, $this->securityCheckAuthContextTransfer);
        $restResponseMock = $this->getRestResponseMock();

        // Act
        $this->securityBlockerAgentControllerAfterActionPlugin->afterAction(
            SecurityBlockerRestApiConfig::RESOURCE_AGENT_ACCESS_TOKENS,
            $restRequestMock,
            $restResponseMock,
        );
    }

    public function testSecurityBlockerAgentControllerAfterActionPluginResetsTheAccountCounterOnASuccessfulLogin(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->expects($this->once())
            ->method('registerSuccessfulLoginAttempt')
            ->with($this->securityCheckAuthContextTransfer);
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');

        // Act
        $this->executeAfterAction($securityBlockerClientMock, $this->createRestResponseMock(Response::HTTP_CREATED, [], []));
    }

    /**
     * A non-JSON server error parses to "no errors"; it must never reset the counter of the account under attack.
     *
     * @dataProvider provideResponsesWithoutPositiveEvidenceOfSuccess
     *
     * @param array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface> $resources
     */
    public function testSecurityBlockerAgentControllerAfterActionPluginRegistersNothingWithoutPositiveEvidenceOfSuccess(int $status, array $resources): void
    {
        // Arrange
        $securityBlockerClientMock = $this->getMockBuilder(SecurityBlockerRestApiToSecurityBlockerClientInterface::class)->getMock();
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');
        $securityBlockerClientMock->expects($this->never())->method('registerFailedLoginAttempt');

        // Act
        $this->executeAfterAction($securityBlockerClientMock, $this->createRestResponseMock($status, [], $resources));
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function provideResponsesWithoutPositiveEvidenceOfSuccess(): array
    {
        return [
            'server error without parsable errors' => [Response::HTTP_INTERNAL_SERVER_ERROR, []],
            'validation error without parsable errors' => [Response::HTTP_UNPROCESSABLE_ENTITY, []],
            'legacy status without issued token' => [0, []],
        ];
    }

    protected function executeAfterAction(
        SecurityBlockerRestApiToSecurityBlockerClientInterface $securityBlockerClientMock,
        RestResponseInterface $restResponse
    ): void {
        $securityBlockerRestApiFactoryMock = $this->getMockBuilder(SecurityBlockerRestApiFactory::class)->onlyMethods(['getSecurityBlockerClient'])->getMock();
        $securityBlockerRestApiFactoryMock->method('getSecurityBlockerClient')->willReturn($securityBlockerClientMock);

        $this->securityBlockerAgentControllerAfterActionPlugin = new SecurityBlockerAgentControllerAfterActionPlugin();
        $this->securityBlockerAgentControllerAfterActionPlugin->setFactory($securityBlockerRestApiFactoryMock);
        $this->securityBlockerAgentControllerAfterActionPlugin->setConfig($this->createSecurityBlockerRestApiConfigMock(SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT));

        $restRequestMock = $this->getRestRequestMock($this->getRestResourceMock($this->securityCheckAuthContextTransfer), $this->securityCheckAuthContextTransfer);

        $this->securityBlockerAgentControllerAfterActionPlugin->afterAction(SecurityBlockerRestApiConfig::RESOURCE_AGENT_ACCESS_TOKENS, $restRequestMock, $restResponse);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RestErrorMessageTransfer> $errors
     * @param array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface> $resources
     */
    protected function createRestResponseMock(int $status, array $errors, array $resources): RestResponseInterface
    {
        $restResponseMock = $this->getMockBuilder(RestResponseInterface::class)->disableOriginalConstructor()->getMock();
        $restResponseMock->method('getStatus')->willReturn($status);
        $restResponseMock->method('getErrors')->willReturn($errors);
        $restResponseMock->method('getResources')->willReturn($resources);

        return $restResponseMock;
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
