<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommand.php';

final class RequestedSelectionsGetNextCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $fppStatus = $this->getStatusFromFpp();
            $this->requestedSelectionGetNext($fppStatus);
        } catch (Exception) {
        }
    }
}

$command = new RequestedSelectionsGetNextCommand();
$command->execute();
$command->completed();
