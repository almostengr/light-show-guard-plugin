<?php

namespace App;

use Exception;

final class DaemonStopCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            unlink(self::DAEMON_FILE);
            $this->completed();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        }
    }
}

$command = new DaemonStopCommand();
$command->execute();
