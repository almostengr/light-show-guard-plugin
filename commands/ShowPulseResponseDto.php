<?php

namespace App\Commands;

final class ShowPulseResponseDto
{
    private $success;
    private $data;
    private $message;

    public function __construct(bool $success, $data = null, $message = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
    }

    public function succeeded()
    {
        return $this->success;
    }

    public function failed()
    {
        return !$this->success;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMessage()
    {
        return $this->message;
    }
}