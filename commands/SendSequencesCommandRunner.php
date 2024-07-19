<?php

namespace App\Commands;

require_once 'SendSequencesCommandHandler.php';

$command = new SendSequencesCommandHandler();
$command->execute();
