<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class RequestsDisableClearCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $configuration = $this->loadConfiguration();
        if (!$configuration) {
            return false;
        }

        $this->httpRequest(
            "shows/clear-off/" . $configuration->getShowId(),
            'PUT',
            null,
            $configuration->getWebsiteUrl(),
            $configuration->getTokenAsHeader()
        );
    }
}