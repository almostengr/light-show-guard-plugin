<?php

namespace App;

use Exception;

require_once 'BaseCommand.php';

final class ShowsAcceptRequestsCommand extends BaseCommand implements ShowPulseCommandInterface
{
    public function execute()
    {
        try {
            $response = $this->getShow();
            $show = $response->getData();
            $show['selection_request_status_id'] = 2;
            $this->updateShow($show);
            $this->completed();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        }
    }
}

$command = new ShowsAcceptRequestsCommand();
$command->execute();
