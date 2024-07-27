#!/bin/bash

echo "Starting Light Show Pulse daemon"

if [ -f /home/fpp/media/plugins/show-pulse-fpp/daemon.run]; then
    echo "Light Show Pulse daemon already running"
else 
    touch /home/fpp/media/plugins/show-pulse-fpp/daemon.run
    
    /usr/bin/php /home/fpp/media/plugins/show-pulse-fpp/ShowPulseDaemon.php &
fi

echo "Done"