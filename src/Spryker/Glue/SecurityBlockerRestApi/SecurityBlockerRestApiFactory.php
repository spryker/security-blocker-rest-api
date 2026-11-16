<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi;

use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToGlossaryStorageClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToSecurityBlockerClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Storage\SecurityBlockerAgentLoginStorage;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Storage\SecurityBlockerAgentStorage;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Storage\SecurityBlockerAgentStorageInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Validator\SecurityBlockerAgentLoginValidator;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Validator\SecurityBlockerAgentValidator;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Validator\SecurityBlockerAgentValidatorInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\RestErrorCollectionBuilder;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\RestErrorCollectionBuilderInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\SecurityCheckAuthContextBuilder;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Builder\SecurityCheckAuthContextBuilderInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationChecker;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Checker\AuthenticationCheckerInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Storage\SecurityBlockerLoginStorage;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Storage\SecurityBlockerStorage;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Storage\SecurityBlockerStorageInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Validator\SecurityBlockerLoginValidator;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Validator\SecurityBlockerValidator;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Validator\SecurityBlockerValidatorInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Registrar\LoginAttemptRegistrar;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Registrar\LoginAttemptRegistrarInterface;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Reserver\LoginAttemptReserver;
use Spryker\Glue\SecurityBlockerRestApi\Processor\Reserver\LoginAttemptReserverInterface;
use Spryker\Shared\Kernel\StrategyResolver;
use Spryker\Shared\Kernel\StrategyResolverInterface;

class SecurityBlockerRestApiFactory extends AbstractFactory
{
    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Validator\SecurityBlockerValidatorInterface>
     */
    public function createLoginProtectionSecurityBlockerValidatorStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver([
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP => fn (): SecurityBlockerValidatorInterface => $this->createSecurityBlockerValidator(),
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT => fn (): SecurityBlockerValidatorInterface => $this->createSecurityBlockerLoginValidator(),
        ]);
    }

    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Glue\SecurityBlockerRestApi\Processor\Customer\Storage\SecurityBlockerStorageInterface>
     */
    public function createLoginProtectionSecurityBlockerStorageStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver([
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP => fn (): SecurityBlockerStorageInterface => $this->createSecurityBlockerStorage(),
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT => fn (): SecurityBlockerStorageInterface => $this->createSecurityBlockerLoginStorage(),
        ]);
    }

    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Validator\SecurityBlockerAgentValidatorInterface>
     */
    public function createLoginProtectionSecurityBlockerAgentValidatorStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver([
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP => fn (): SecurityBlockerAgentValidatorInterface => $this->createSecurityBlockerAgentValidator(),
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT => fn (): SecurityBlockerAgentValidatorInterface => $this->createSecurityBlockerAgentLoginValidator(),
        ]);
    }

    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Glue\SecurityBlockerRestApi\Processor\Agent\Storage\SecurityBlockerAgentStorageInterface>
     */
    public function createLoginProtectionSecurityBlockerAgentStorageStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver([
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP => fn (): SecurityBlockerAgentStorageInterface => $this->createSecurityBlockerAgentStorage(),
            SecurityBlockerRestApiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT => fn (): SecurityBlockerAgentStorageInterface => $this->createSecurityBlockerAgentLoginStorage(),
        ]);
    }

    public function createSecurityBlockerStorage(): SecurityBlockerStorageInterface
    {
        return new SecurityBlockerStorage(
            $this->getSecurityBlockerClient(),
            $this->createAuthenticationChecker(),
        );
    }

    public function createSecurityBlockerValidator(): SecurityBlockerValidatorInterface
    {
        return new SecurityBlockerValidator(
            $this->getSecurityBlockerClient(),
            $this->createAuthenticationChecker(),
            $this->createRestErrorCollectionBuilder(),
        );
    }

    public function createSecurityBlockerAgentStorage(): SecurityBlockerAgentStorageInterface
    {
        return new SecurityBlockerAgentStorage(
            $this->getSecurityBlockerClient(),
            $this->createAuthenticationChecker(),
        );
    }

    public function createSecurityBlockerAgentValidator(): SecurityBlockerAgentValidatorInterface
    {
        return new SecurityBlockerAgentValidator(
            $this->getSecurityBlockerClient(),
            $this->createAuthenticationChecker(),
            $this->createRestErrorCollectionBuilder(),
        );
    }

    public function createSecurityBlockerLoginStorage(): SecurityBlockerStorageInterface
    {
        return new SecurityBlockerLoginStorage(
            $this->createAuthenticationChecker(),
            $this->createSecurityCheckAuthContextBuilder(),
            $this->createLoginAttemptRegistrar(),
        );
    }

    public function createSecurityBlockerLoginValidator(): SecurityBlockerValidatorInterface
    {
        return new SecurityBlockerLoginValidator(
            $this->createAuthenticationChecker(),
            $this->createRestErrorCollectionBuilder(),
            $this->createSecurityCheckAuthContextBuilder(),
            $this->createLoginAttemptReserver(),
        );
    }

    public function createSecurityBlockerAgentLoginStorage(): SecurityBlockerAgentStorageInterface
    {
        return new SecurityBlockerAgentLoginStorage(
            $this->createAuthenticationChecker(),
            $this->createSecurityCheckAuthContextBuilder(),
            $this->createLoginAttemptRegistrar(),
        );
    }

    public function createSecurityBlockerAgentLoginValidator(): SecurityBlockerAgentValidatorInterface
    {
        return new SecurityBlockerAgentLoginValidator(
            $this->createAuthenticationChecker(),
            $this->createRestErrorCollectionBuilder(),
            $this->createSecurityCheckAuthContextBuilder(),
            $this->createLoginAttemptReserver(),
        );
    }

    public function createLoginAttemptReserver(): LoginAttemptReserverInterface
    {
        return new LoginAttemptReserver($this->getSecurityBlockerClient());
    }

    public function createLoginAttemptRegistrar(): LoginAttemptRegistrarInterface
    {
        return new LoginAttemptRegistrar($this->getSecurityBlockerClient(), $this->createAuthenticationChecker());
    }

    public function createSecurityCheckAuthContextBuilder(): SecurityCheckAuthContextBuilderInterface
    {
        return new SecurityCheckAuthContextBuilder();
    }

    public function createRestErrorCollectionBuilder(): RestErrorCollectionBuilderInterface
    {
        return new RestErrorCollectionBuilder($this->getGlossaryStorageClient());
    }

    public function createAuthenticationChecker(): AuthenticationCheckerInterface
    {
        return new AuthenticationChecker();
    }

    public function getSecurityBlockerClient(): SecurityBlockerRestApiToSecurityBlockerClientInterface
    {
        return $this->getProvidedDependency(SecurityBlockerRestApiDependencyProvider::CLIENT_SECURITY_BLOCKER);
    }

    public function getGlossaryStorageClient(): SecurityBlockerRestApiToGlossaryStorageClientInterface
    {
        return $this->getProvidedDependency(SecurityBlockerRestApiDependencyProvider::CLIENT_GLOSSARY_STORAGE);
    }
}
