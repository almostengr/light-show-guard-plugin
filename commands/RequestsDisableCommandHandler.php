<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class RequestsDisableCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $configuration = $this->loadConfiguration();
        if (!$configuration) {
            return false;
        }

        $this->httpRequest(
            "shows/request-off/" . $configuration->getShowId(),
            'PUT',
            null,
            $configuration->getWebsiteUrl(),
            $configuration->getTokenAsHeader()
        );
    }
}