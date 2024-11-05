<?php

namespace App;

use Exception;

require_once 'BaseCommand.php';

final class RequestedSelectionsClearCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $this->webHttpRequest(
                "api/requested-selections/clear/" . $this->configuration->getUserId(),
                'PUT'
            );
            $this->completed();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        }
    }
}

$command = new RequestedSelectionsClearCommand();
$command->execute();
