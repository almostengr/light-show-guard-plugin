<?php

include_once "ShowPulseCommon.php";

final class ShowPulseWorker extends ShowPulseCommon
{
    private $fppStatus;
    private $attempt;
    private $lastSequence;
    private $webStatus;

    public function __construct()     {
        $this->attempt = 0;
        $this->fppStatus = null;
        $this->lastSequence = null;
        $this->webStatus = null;
    }

     function hasApiKey()
    {
        return $this->readSetting("api_key") ;
    }

     function getFppStatus()    {
        $url = $this->fppUrl("api/fppd/status");
        $this->fppStatus = $this->httpRequest($url);
    }

     function isTestingPlaylist()    {
        if (!is_null($this->fppStatus) && 
            (strpos($this->fppStatus->current_playlist, 'test') !== false || 
                strpos($this->fppStatus->current_playlist, 'offline') !== false)) {
            return true;
        }

        return false;
    }
    
     function exponentialBackoffSleep()    {
        $delay = min(pow(2, $this->attempt) * DEFAULT_DELAY, MAX_DELAY);
        sleep($delay);
    }

     function resetAttempt()    {
        $this->attempt = 0;
    }

     function increaseAttempt()    {
        $this->attempt = $this->attempt < 5 ? $this->attempt++ : $this->attempt;
    }

    function sleepWhilePlaying()
    {
        sleep(5);
    }

    function sleepWhileIdle()
    {
        sleep(15);
    }

    public function execute()
    {
        while (true) {
            try {
                $this->getFppStatus();
        
                if ($this->isTestingPlaylist())
                {
                    $this->sleepWhileIdle();
                    continue;
                }

                if ($this->lastSequence === $this->status->current_sequence)
                {
                    $this->sleepWhilePlaying();
                    continue;
                }

                $this->lastSequence = $this->status->current_sequence;
        
                // $statusDto = new StatusRequestDto(
                //     "", count($this->status->warnings), $this->status->sensors->temperature, $this->status->current_playlist, $this->status->status);

                
        
                $this->postShowStatus()
        
                executeFppCommand(INSERT_CURRENT, array($nextSong, "-1", "-1", "false"));
                $this->sleepWhilePlaying();
        
                $this->resetAttempt();
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                $this->exponentialBackoffSleep();
                $this->increaseAttempt();
            }
        }
    }
}