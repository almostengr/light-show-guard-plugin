#!/bin/sh

echo "Running Show Pulse PostStart Script"

/usr/bin/php /home/fpp/media/plugins/showpulse/app/ShowPulseWorker.php &
