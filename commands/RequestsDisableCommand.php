<?php

namespace App\Commands;

use App\ShowPulseBase;

require_once '../ShowPulseBase.php';

final class RequestsDisableCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();

        if (!$loadSuccessful) {
            return;
        }

        $this->httpRequest(
            false,
            "shows/request-off/" . $this->getShowId(),
            'PUT',
            null
        );
    }
}

$command = new RequestsDisableCommand();
$command->execute();