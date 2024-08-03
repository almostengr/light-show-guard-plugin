<?php

namespace App\Commands;

require_once 'StatusPostToWebsiteCommandHandler.php';

$command = new ShowPulseDaemonCommandHandler();

$configuration = $command->loadConfiguration();
if (!$configuration) {
    return;
}

$fppStatus = $command->getStatusFromFpp();
if (!$fppStatus) {
    return;
}

$statusDto = $command->createStatusDto($fppStatus, $configuration);
$command->postStatusToWebsite($statusDto, $configuration);
