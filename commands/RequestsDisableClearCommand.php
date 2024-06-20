<?php

namespace App\Commands;

use App\ShowPulseBase;

require_once '../ShowPulseBase.php';

final class RequestsDisableClearCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        $apiKey = $this->getWebsiteApiKey();

        if ($apiKey === false) {
            return;
        }

        $this->httpRequest(
            false,
            "shows/clear-off",
            'PUT',
            null,
            $this->getWebsiteAuthorizationHeaders()
        );
    }
}

$command = new RequestsDisableClearCommand();
$command->execute();