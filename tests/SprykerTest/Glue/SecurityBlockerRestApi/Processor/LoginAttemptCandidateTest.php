<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\SecurityBlockerRestApi\Processor;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RestAccessTokensAttributesTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponse;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\SecurityCheckAuthContextBuilder;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationChecker;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Registrar\LoginAttemptRegistrar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The legacy Glue controller authenticates the attributes transfer, whose `fromArray()` also maps `Username` and keeps
 * the last key, while the API Platform processor authenticates the exact `username` attribute. Both identifiers are
 * attempts against an account.
 *
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group SecurityBlockerRestApi
 * @group Processor
 * @group LoginAttemptCandidateTest
 * Add your own group annotations below this line
 */
class LoginAttemptCandidateTest extends Unit
{
    protected const string VICTIM = 'victim@example.com';

    protected const string DECOY = 'decoy@example.com';

    /**
     * @uses \Spryker\Glue\AuthRestApi\AuthRestApiConfig::RESPONSE_INVALID_LOGIN
     */
    protected const string RESPONSE_INVALID_LOGIN = '003';

    public function testTheTransferUsernameAndTheExactBodyUsernameAreBothCandidates(): void
    {
        // Arrange
        $restRequest = $this->createRestRequest(static::DECOY, ['username' => static::VICTIM, 'Username' => static::DECOY]);

        // Act
        $securityCheckAuthContextTransfers = (new SecurityCheckAuthContextBuilder())->createCustomerSecurityCheckAuthContextTransfers($restRequest);

        // Assert
        $this->assertSame(
            [static::DECOY, static::VICTIM],
            array_map(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): ?string => $securityCheckAuthContextTransfer->getLoginIdentifier(), $securityCheckAuthContextTransfers),
        );
    }

    public function testAnUnambiguousUsernameIsASingleCandidate(): void
    {
        // Arrange
        $restRequest = $this->createRestRequest(static::VICTIM, ['username' => static::VICTIM]);

        // Act
        $securityCheckAuthContextTransfers = (new SecurityCheckAuthContextBuilder())->createCustomerSecurityCheckAuthContextTransfers($restRequest);

        // Assert
        $this->assertCount(1, $securityCheckAuthContextTransfers);
        $this->assertSame(static::VICTIM, $securityCheckAuthContextTransfers[0]->getLoginIdentifier());
    }

    public function testAFailureIsRegisteredAgainstEveryCandidate(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerRestApiToSecurityBlockerClientInterface::class);
        $restResponse = (new RestResponse())->addError(
            (new RestErrorMessageTransfer())->setStatus(Response::HTTP_UNAUTHORIZED)->setCode(static::RESPONSE_INVALID_LOGIN),
        );

        // Assert
        $securityBlockerClientMock->expects($this->exactly(2))->method('registerFailedLoginAttempt');

        // Act
        (new LoginAttemptRegistrar($securityBlockerClientMock, new AuthenticationChecker()))
            ->registerLoginAttemptOutcome($this->createCandidates(), $restResponse, static::RESPONSE_INVALID_LOGIN);
    }

    /**
     * Which of several candidates the endpoint authenticated is unknown, so a success must not reset any account counter.
     */
    public function testASuccessWithSeveralCandidatesResetsNoAccountCounter(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerRestApiToSecurityBlockerClientInterface::class);

        // Assert
        $securityBlockerClientMock->expects($this->never())->method('registerSuccessfulLoginAttempt');

        // Act
        (new LoginAttemptRegistrar($securityBlockerClientMock, new AuthenticationChecker()))
            ->registerLoginAttemptOutcome($this->createCandidates(), (new RestResponse())->setStatus(Response::HTTP_CREATED), static::RESPONSE_INVALID_LOGIN);
    }

    /**
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    protected function createCandidates(): array
    {
        return [
            (new SecurityCheckAuthContextTransfer())->setLoginIdentifier(static::DECOY),
            (new SecurityCheckAuthContextTransfer())->setLoginIdentifier(static::VICTIM),
        ];
    }

    /**
     * @param array<string, string> $bodyAttributes
     */
    protected function createRestRequest(string $transferUsername, array $bodyAttributes): RestRequestInterface
    {
        $restResourceMock = $this->createMock(RestResourceInterface::class);
        $restResourceMock->method('getAttributes')->willReturn((new RestAccessTokensAttributesTransfer())->setUsername($transferUsername));

        $restRequestMock = $this->createMock(RestRequestInterface::class);
        $restRequestMock->method('getResource')->willReturn($restResourceMock);
        $restRequestMock->method('getHttpRequest')->willReturn(Request::create(
            '/access-tokens',
            Request::METHOD_POST,
            [],
            [],
            [],
            ['REMOTE_ADDR' => '203.0.113.1'],
            (string)json_encode(['data' => ['type' => 'access-tokens', 'attributes' => $bodyAttributes + ['password' => 'guess']]]),
        ));

        return $restRequestMock;
    }
}
