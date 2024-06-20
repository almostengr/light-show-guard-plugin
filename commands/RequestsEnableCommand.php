<?php

namespace App\Commands;

use App\ShowPulseBase;

require_once '../ShowPulseBase.php';

final class RequestsEnableCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        $apiKey = $this->getWebsiteApiKey();

        if ($apiKey === false) {
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