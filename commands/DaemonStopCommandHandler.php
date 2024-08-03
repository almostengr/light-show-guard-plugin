<?php

namespace App\Commands;

final class DaemonStopCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        unlink(ShowPulseConstant::DAEMON_FILE);
    }
}