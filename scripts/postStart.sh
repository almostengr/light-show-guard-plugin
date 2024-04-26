#!/bin/sh

echo "Running fpp-plugin-Template PostStart Script"

/usr/bin/php /home/fpp/media/plugins/showpulse/worker.php &
