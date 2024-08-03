<?php

namespace App\Commands;

final class ShowPulseConstant
{
    public const FPP_STATUS_IDLE = 0;
    public const GRACEFUL_RESTART = "GRACEFUL RESTART";
    public const GRACEFUL_SHUTDOWN = "GRACEFUL SHUTDOWN";
    public const GRACEFUL_STOP = "GRACEFUL STOP";
    public const HIGH_PRIORITY = 10;
    public const IMMEDIATE_RESTART = "IMMEDIATE RESTART";
    public const IMMEDIATE_SHUTDOWN = "IMMEDIATE SHUTDOWN";
    public const IMMEDIATE_STOP = "IMMEDIATE STOP";
    public const MAX_FAILURES_ALLOWED = 5;
    public const SLEEP_SHORT_VALUE = 5;
    public const DAEMON_FILE = "/home/fpp/media/plugins/show-pulse-fpp/daemon.run";
    public const FPP_URL = "https://127.0.0.1/api";
    public const DELAY_SECONDS = 2;
}