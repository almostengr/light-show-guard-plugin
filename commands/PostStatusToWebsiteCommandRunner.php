<?php

namespace App\Commands;

require_once 'PostStatusToWebsiteCommandHandler.php';

$command = new PostStatusToWebsiteCommandHandler();
$command->execute();