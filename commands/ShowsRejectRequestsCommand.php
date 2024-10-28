<?php

namespace App;;

use Exception;

require_once 'BaseCommand.php';

final class ShowsRejectRequestsCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $this->rejectSelectionRequests();
            $this->completed();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        }
    }
}

$command = new ShowsRejectRequestsCommand();
$command->execute();
