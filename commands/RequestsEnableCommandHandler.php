<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class RequestsEnableCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $configuration = $this->loadConfiguration();
        if (!$configuration) {
            return false;
        }

        $this->httpRequest(
            "shows/requeston/" . $configuration->getShowId(),
            'PUT',
            null,
            $configuration->getWebsiteUrl(),
            $configuration->getTokenAsHeader()
        );
    }
}