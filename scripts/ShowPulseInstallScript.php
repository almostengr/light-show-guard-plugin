<?php

namespace App\Scripts;

require_once '../ShowPulseBase.php';

use App\ShowPulseBase;
use App\ShowPulseConstant;

final class ShowPulseInstallScript extends ShowPulseBase
{
    public function execute()
    {
        $this->saveSetting(ShowPulseConstant::API_KEY, null);
        $this->saveSetting(ShowPulseConstant::BETA_API_KEY, null);
        $this->saveSetting(ShowPulseConstant::ENVIRONMENT, ShowPulseConstant::PRODUCTION_ENVIRONMENT);
        $this->saveSetting(ShowPulseConstant::PLAYLIST, null);
    }
}

$script = new ShowPulseInstallScript();
$script->execute();
