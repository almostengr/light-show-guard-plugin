#!/bin/sh

echo "Stopping Show Pulse Daemon"

/bin/php /home/fpp/media/plugins/show-pulse-fpp/commands/DaemonStopCommandRunner.php

echo "Done"