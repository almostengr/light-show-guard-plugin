<?php

function WriteSettingToFile($settingName, $setting, $plugin = "")
{
    global $settingsFile;
    global $settings;
    $filename = $settingsFile;

    if ($plugin != "") {
        $filename = $settings['configDirectory'] . "/plugin." . $plugin;
    }

    $settingsStr = "";
    if (file_exists($filename)) {
        $tmpSettings = parse_ini_file($filename);
    }
    $tmpSettings[$settingName] = $setting;
    foreach ($tmpSettings as $key => $value) {
        $settingsStr .= $key . " = \"" . $value . "\"\n";
    }
    file_put_contents($filename, $settingsStr, LOCK_EX);
}

function ReadSettingFromFile($settingName, $plugin = "")
{
    global $settingsFile;
    global $settings;
    $filename = $settingsFile;

    if ($plugin != "") {
        $filename = "test" . "/plugin." . $plugin;
        // $filename = $settings["configDirectory"] . "/plugin." . $plugin;
    }
    if (!file_exists($filename)) {
        return false;
    }
    $fd = @fopen($filename, "r");
    $settingsStr = "";
    if ($fd) {
        flock($fd, LOCK_SH);
        $settingsStr = file_get_contents($filename);
        flock($fd, LOCK_UN);
        fclose($fd);
    }
    if (!empty($settingsStr)) {
        if (preg_match("/^" . $settingName . "/m", $settingsStr)) {
            $result = preg_match("/^" . $settingName . "\s*=(\s*\S*\w*)/m", $settingsStr, $output_array);
            if ($result == 0) {
                //        error_log("The setting " . $settingName . " could not be found in " . $filename);
                return false;
            }
            return trim($output_array[1], " \t\n\r\0\x0B\"");
        } else {
            //      error_log("The setting " . $settingName . " could not be found in " . $filename);
            return false;
        }
    } else {
        error_log("The setting file:" . $filename . " could not be found.");
        return false;
    }
}

function GetDirSetting($dir)
{
    return "";
}