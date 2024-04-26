<?php

include_once "ShowPulseCommon.php";
include_once "StatusRequestDto.php";
include_once "ShowPulseWorker.php";

define("DEFAULT_DELAY", 2);
define("MAX_DELAY", 15);
define("IDLE_DELAY", 15);
define("INSERT_CURRENT", "Insert Playlist After Current");


$worker = new ShowPulseWorker();
$worker->execute();
