<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommand.php';

final class ShowsRejectRequestsCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $this->rejectSelectionRequests();
        } catch (Exception) {
        }
    }
}

$command = new ShowsRejectRequestsCommand();
$command->execute();
$command->completed();
