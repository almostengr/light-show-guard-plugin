<?php

namespace App\Scripts;

require_once '../ShowPulseBase.php';

use App\ShowPulseBase;
use App\ShowPulseConstant;

final class ShowPulseUninstallScript extends ShowPulseBase
{
    public function execute()
    {
        DeleteSettingFromFile(ShowPulseConstant::API_KEY, ShowPulseConstant::PLUGIN_NAME);
        DeleteSettingFromFile(ShowPulseConstant::BETA_API_KEY, ShowPulseConstant::PLUGIN_NAME);
        DeleteSettingFromFile(ShowPulseConstant::ENVIRONMENT, ShowPulseConstant::PLUGIN_NAME);
        DeleteSettingFromFile(ShowPulseConstant::PLAYLIST, ShowPulseConstant::PLUGIN_NAME);
    }
}

$script = new ShowPulseUninstallScript();
$script->execute();
