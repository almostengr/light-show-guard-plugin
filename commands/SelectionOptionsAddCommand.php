<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommand.php';

final class SelectionOptionsAddCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $selectionOptions = scandir("/home/fpp/media/sequences");
            $playlistOptions = scandir("/home/fpp/media/playlists");

            array_push($selectionOptions, $playlistOptions);

            $this->webHttpRequest(
                "api/selection-options/add/" . $this->configuration->getShowId(),
                "PUT",
                $selectionOptions
            );
        } catch (Exception) {
        }
    }
}

$command  = new SelectionOptionsAddCommand();
$command->execute();
$command->completed();
