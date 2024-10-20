<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommand.php';

final class ShowStatusesAddCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $fppStatus = $this->getStatusFromFpp();
            $this->postStatusToWebsite($fppStatus);
        } catch (Exception) {
        }
    }
}

$command = new ShowStatusesAddCommand();
$command->execute();
$command->completed();
