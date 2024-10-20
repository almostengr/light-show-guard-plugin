<?php

namespace App\Commands;

final class DaemonStopCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        unlink(self::DAEMON_FILE);
    }
}

$command = new DaemonStopCommand();
$command->execute();
$command->completed();