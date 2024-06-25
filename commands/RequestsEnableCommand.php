<?php

namespace App\Commands;

use App\ShowPulseBase;

require_once '../ShowPulseBase.php';

final class RequestsEnableCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();

        if (!$loadSuccessful) {
            return;
        }

        $this->httpRequest(
            false,
            "shows/request-on",
            'PUT',
            null,
            $this->getWebsiteAuthorizationHeaders()
        );
    }
}

$command = new RequestsEnableCommand();
$command->execute();