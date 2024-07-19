<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class RequestsDisableClearCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();

        if (!$loadSuccessful) {
            return false;
        }

        $this->httpRequest(
            false,
            "shows/clear-off/" . $this->getShowUuid(),
            'PUT',
            null
        );
    }
}