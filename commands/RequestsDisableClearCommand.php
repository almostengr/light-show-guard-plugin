<?php

namespace App\Commands;

use App\ShowPulseBase;

require_once '../ShowPulseBase.php';

final class RequestsDisableClearCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();

        if (!$loadSuccessful) {
            return;
        }

        $this->httpRequest(
            false,
            "shows/clear-off/" . $this->getShowId(),
            'PUT',
            null
        );
    }
}

$command = new RequestsDisableClearCommand();
$command->execute();