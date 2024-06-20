<?php

namespace App\Commands;

use App\ShowPulseBase;

require_once '../ShowPulseBase.php';

final class RequestsDisableCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        $apiKey = $this->getWebsiteApiKey();

        if ($apiKey === false) {
            return;
        }

        $this->httpRequest(
            false,
            "shows/request-off",
            'PUT',
            null,
            $this->getWebsiteAuthorizationHeaders()
        );
    }
}

$command = new RequestsDisableCommand();
$command->execute();