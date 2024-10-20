#!/bin/bash

echo "Starting Light Show Pulse daemon"

/usr/bin/php /home/fpp/media/plugins/show-pulse-fpp/DaemonCommand.php &

echo "Done"