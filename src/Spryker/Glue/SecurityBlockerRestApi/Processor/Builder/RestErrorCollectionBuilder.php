<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SecurityBlockerRestApi\Processor\Builder;

use Generated\Shared\Transfer\RestErrorCollectionTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToGlossaryStorageClientInterface;
use Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class RestErrorCollectionBuilder implements RestErrorCollectionBuilderInterface
{
    /**
     * @var \Spryker\Glue\SecurityBlockerRestApi\Dependency\Client\SecurityBlockerRestApiToGlossaryStorageClientInterface
     */
    protected $glossaryStorageClient;

    public function __construct(SecurityBlockerRestApiToGlossaryStorageClientInterface $glossaryStorageClient)
    {
        $this->glossaryStorageClient = $glossaryStorageClient;
    }

    public function createRestErrorCollectionTransfer(
        SecurityCheckAuthResponseTransfer $securityCheckAuthResponseTransfer,
        string $localeName
    ): RestErrorCollectionTransfer {
        $translatedMessage = $this->glossaryStorageClient->translate(
            SecurityBlockerRestApiConfig::ERROR_RESPONSE_DETAIL_ACCOUNT_BLOCKED,
            $localeName,
            ['%minutes%' => $this->convertSecondsToReadableTime($securityCheckAuthResponseTransfer)],
        );

        $restErrorMessageTransfer = (new RestErrorMessageTransfer())
            ->setStatus(Response::HTTP_TOO_MANY_REQUESTS)
            ->setCode(SecurityBlockerRestApiConfig::ERROR_RESPONSE_CODE_ACCOUNT_BLOCKED)
            ->setDetail($translatedMessage);

        return (new RestErrorCollectionTransfer())
            ->addRestError($restErrorMessageTransfer);
    }

    protected function convertSecondsToReadableTime(
        SecurityCheckAuthResponseTransfer $securityCheckAuthResponseTransfer
    ): string {
        $seconds = $securityCheckAuthResponseTransfer->getBlockedFor() ?? 0;

        return (string)ceil($seconds / 60);
    }
}
