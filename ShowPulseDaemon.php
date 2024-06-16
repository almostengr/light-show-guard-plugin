<?php

namespace App;

use Exception;

require_once "ShowPulseWorker.php";

$worker = new ShowPulseWorker();
while (true) {
    $worker->execute();
}