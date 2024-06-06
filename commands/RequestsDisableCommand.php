<?php

namespace App\Commands;

use App\ShowPulseBase;

require_once '../ShowPulseBase.php';

final class RequestsDisableCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        $apiKey = $this->getWebsiteApiKey();

        if (empty($apiKey) || is_null($apiKey)) {
            return;
        }

        $url = $this->websiteUrl("shows/requestoff");
        $response = $this->httpRequest($url, 'PUT', null, $this->getWebsiteAuthorizationHeaders());
    }
}

$command = new RequestsDisableCommand();
$command->execute();