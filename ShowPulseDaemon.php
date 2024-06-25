<?php

namespace App;

require_once "ShowPulseWorker.php";

$worker = new ShowPulseWorker();
$loadResult = $worker->loadConfiguration();

while ($loadResult) {
    $worker->execute();
}