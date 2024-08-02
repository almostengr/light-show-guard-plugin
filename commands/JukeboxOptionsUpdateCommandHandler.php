<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class JukeboxOptionsUpdateCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $configuration = $this->loadConfiguration();
        if (!$configuration) {
            return false;
        }

        $sequenceDirectory = GetDirSetting("sequences");
        $sequenceOptions = scandir($sequenceDirectory);

        $this->httpRequest(
            "jukebox-options/add/" . $configuration->getShowId(),
            "PUT",
            $sequenceOptions,
            $configuration->getWebsiteUrl(),
            $configuration->getTokenAsHeader()
        );
    }
}