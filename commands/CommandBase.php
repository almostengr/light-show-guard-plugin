<?php

namespace App\Commands;

use App\ShowPulseBase;
use Exception;

require_once "..\ShowPulseBase.php";

interface ShowPulseCommandInterface
{
    public function execute();
}

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
                "shows/add-options/" . $this->getShowUuid(),
                "PUT",
                $sequenceOptions
            );

            return array('success' => true, 'message' => "Jukebox options updated successfully.");
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
            return array('success' => false, 'message' => $exception->getMessage());
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
            "shows/request-on/" . $this->getShowUuid(),
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
            "shows/request-off/" . $this->getShowUuid(),
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
            "shows/clear-off/" . $this->getShowUuid(),
            'PUT',
            null
        );
    }
}