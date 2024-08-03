<?php

namespace App\Commands;

require_once "DaemonStopCommandHandler.php";

$command = new DaemonStopCommandHandler();
$command->execute();
