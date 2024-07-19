<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class RequestsDisableCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();

        if (!$loadSuccessful) {
            return false;
        }

        $this->httpRequest(
            false,
            "shows/request-off/" . $this->getShowUuid(),
            'PUT',
            null
        );
    }
}