<?php

namespace App\Commands;

use App\ShowPulseBase;
use Exception;

require_once "..\ShowPulseBase.php";

final class SendSequencesCommand extends ShowPulseBase implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $loadSuccessful = $this->loadConfiguration();
            if (!$loadSuccessful) {
                throw new Exception("Unable to load configuration file. Configuration file can be downloaded from the Light Show Pulse website.");
            }

            $sequenceDirectory = GetDirSetting("sequences");
            $sequenceOptions = scandir($sequenceDirectory);

            $this->httpRequest(
                false,
                "shows/add-options/" . $this->getShowId(),
                "PUT",
                $sequenceOptions
            );

            return array('success' => true, 'message' => "Jukebox options updated successfully.");
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}

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
            "shows/request-on/" . $this->getShowId(),
            'PUT',
            null
        );
    }
}

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