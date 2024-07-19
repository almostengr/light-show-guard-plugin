<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class RequestsEnableCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();

        if (!$loadSuccessful) {
            return false;
        }

        $this->httpRequest(
            false,
            "shows/request-on/" . $this->getShowUuid(),
            'PUT',
            null
        );
    }
}