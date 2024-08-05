<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class SequenceUpdateCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $configuration = $this->loadConfiguration();
        if (!$configuration) {
            return false;
        }

        $sequenceDirectory = "/home/fpp/media/sequences";
        $sequenceOptions = scandir($sequenceDirectory);

        $this->httpRequest(
            "sequences/add/" . $configuration->getShowId(),
            "PUT",
            $sequenceOptions,
            $configuration->getWebsiteUrl(),
            $configuration->getTokenAsHeader()
        );
    }
}