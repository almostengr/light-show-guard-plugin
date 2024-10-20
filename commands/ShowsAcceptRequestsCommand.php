<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommand.php';

final class ShowsAcceptRequestsCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $this->webHttpRequest(
                "api/shows/accept-requests/" . $this->configuration->getShowId(),
                'PUT'
            );
        } catch (Exception) {
        }
    }
}

$command = new ShowsAcceptRequestsCommand();
$command->execute();
$command->completed();
