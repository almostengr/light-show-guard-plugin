<?php

// namespace App;
namespace Almostengr\Showpulsefpp;

include_once "ShowPulseBase.php";

define("SHORT_DELAY", 5);
define("LONG_DELAY", 15);

final class StatusDto
{
    private $showId;
    private $hasErrors;
    private $cpuTemperature;
    private $sequence;
    private $isPlaying;
    private $title;
    private $artist;

    public function __construct($showId, $hasErrors, $cpuTemperature, $sequence, $isPlaying)
    {
        $this->showId = $showId;
        $this->hasErrors = $hasErrors;
        $this->cpuTemperature = $cpuTemperature;
        $this->sequence = $sequence;
        $this->isPlaying = $isPlaying;
    }

    public function assignMedia($title = null, $artist = null)
    {
        if (!is_null($title))
        {
            $this->title = $title;
        }

        if (!is_null($artist))
        {
            $this->artist = $artist;
        }
    }
}

final class ShowPulseWorker extends ShowPulseBase
{
    private $fppStatus;
    private $attemptCount;
    private $lastSequence;
    private $webStatus;

    public function __construct()
    {
        $this->attemptCount = 0;
        $this->fppStatus = null;
        $this->lastSequence = null;
        $this->webStatus = null;
    }

    private function getFppStatus()
    {
        $url = $this->fppUrl("api/fppd/status");
        $this->fppStatus = $this->httpRequest($url);

        if ($this->fppStatus === null) {
            throw new Exception("Unable to get latest from FPP");
        }
    }

    private function getMediaMeta($filename = null)
    {
        if (is_null($filename)) {
            return;
        }

        $url = $this->fppUrl("api/media/$filename/meta");
        return $this->httpRequest($url);
    }

    private function isTestingOrOfflinePlaylist()
    {
        if (
            !is_null($this->fppStatus) &&
            (strpos($this->fppStatus->current_playlist->playlist, 'test') !== false ||
                strpos($this->fppStatus->current_playlist->playlist, 'offline') !== false)
        ) {
            return true;
        }

        return false;
    }

    private function exponentialBackoffSleep()
    {
        $defaultDelay = 2;
        $maxDelay = 15;
        $delay = min(pow(2, $this->attemptCount) * $defaultDelay, $maxDelay);
        sleep($delay);
    }

    private function resetAttemptCount()
    {
        $this->attemptCount = 0;
    }

    private function increaseAttemptCount()
    {
        $this->attemptCount = $this->attemptCount < 5 ? $this->attemptCount++ : $this->attemptCount;
    }

    private function sleepShortValue()
    {
        return SHORT_DELAY;
    }

    private function sleepLongValue()
    {
        return LONG_DELAY;
    }

    private function sleepShort()
    {
        sleep($this->sleepShortValue());
    }

    private function sleepLong()
    {
        sleep($this->sleepLongValue());
    }

    private function getNextRequest()
    {
        $url = $this->webUrl("shows/nextrequest");
        $headers = $this->getWebsiteAuthorizationHeaders();
        $this->webStatus = $this->httpRequest($url, "GET", null, $headers);

        if ($this->webStatus === null) {
            throw new Exception("Unable to get latest request from server.");
        }
    }

    private function postShowStatus()
    {
        $headers = $this->getWebsiteAuthorizationHeaders();

        $hasErrors = count($this->fppStatus->warnings);
        $isPlaying = $this->fppStatus->statue_name === "playing";
        $statusDto = new StatusDto("", $hasErrors, null, $this->fppStatus->current_sequence, $isPlaying);

        $mediaMeta = new stdClass();
        if (!empty($this->fppStatus->current_song)) {
            $mediaMeta = $this->getMediaMeta($this->fppStatus->current_song);

            if (!is_null($mediaMeta)) {
                $statusDto->assignMedia($mediaMeta->title, $mediaMeta->artist);
            }
        }

        $webUrl = $this->webUrl("statuses/add");
        $result = $this->httpRequest($webUrl, "POST", $statusDto, $headers);

        if ($result === false) {
            throw new Exception("Unable to update show status");
        }
    }

    public function execute()
    {
        try {
            $this->getApiKey();
            $this->getFppStatus();

            if ($this->isTestingOrOfflinePlaylist()) {
                $this->sleepLong();
                return;
            }

            if ($this->lastSequence !== $this->fppStatus->current_sequence) {
                $this->postShowStatus();
            }

            $secondsRemaining = intval($this->fppStatus->seconds_remaining);
            if ($secondsRemaining > $this->sleepShortValue()) {
                $this->sleepShort();
                return;
            }

            $this->getNextRequest();

            $this->executeFppCommand(
                "Insert Playlist After Current",
                array($this->webStatus->sequence, "-1", "-1", "false")
            );

            $this->sleepShort();
            $this->resetAttemptCount();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->exponentialBackoffSleep();
            $this->increaseAttemptCount();
        }
    }
}

$worker = new ShowPulseWorker();
while (true) {
    $worker->execute();
}