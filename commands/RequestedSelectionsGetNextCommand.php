<?php

namespace App;;

use Exception;

require_once 'BaseCommand.php';

final class RequestedSelectionsGetNextCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $fppStatus = $this->getStatusFromFpp();
            $this->getNextRequestedSelection($fppStatus);
            $this->completed();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        }
    }
}

$command = new RequestedSelectionsGetNextCommand();
$command->execute();
