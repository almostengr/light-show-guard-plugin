<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class JukeboxOptionsUpdateCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();
        if (!$loadSuccessful) {
            return false;
        }

        $sequenceDirectory = GetDirSetting("sequences");
        $sequenceOptions = scandir($sequenceDirectory);

        $this->httpRequest(
            false,
            "jukebox-options/add/" . $this->getShowUuid(),
            "PUT",
            $sequenceOptions
        );
    }
}