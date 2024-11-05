<?php

namespace App;

use Exception;

require_once 'BaseCommand.php';

final class SelectionOptionsAddCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $selectionOptions = $this->getPlayablePlaylists();

            $this->webHttpRequest(
                "api/selection-options/add/" . $this->configuration->getUserId(),
                "PUT",
                $selectionOptions
            );
            $this->completed();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        }
    }

    private function getPlayablePlaylists()
    {
        $playlists  = $this->fppHttpRequest("api/playlists/playable");
        return $playlists;
    }
}

$command  = new SelectionOptionsAddCommand();
$command->execute();
