<?php

namespace App;

require_once "ShowPulseWorker.php";

$worker = new ShowPulseWorker();
while (true) {
    $worker->execute();
}