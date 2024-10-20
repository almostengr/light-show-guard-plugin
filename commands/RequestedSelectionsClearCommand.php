<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommand.php';

final class RequestedSelectionsClearCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $this->webHttpRequest(
                "api/requested-selections/clear/" . $this->configuration->getShowId(),
                'PUT'
            );
        } catch (Exception) {
        }
    }
}

$command = new RequestedSelectionsClearCommand();
$command->execute();
$command->completed();
