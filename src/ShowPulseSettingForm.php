<?php

// namespace App;
namespace Almostengr\Showpulsefpp;

final class ShowPulseSettingForm extends ShowPulseBase
{
    public function save()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return null;
        }

        $apiKey = $_POST["api_key"];
        $selectedPlaylist = $_POST["playlist"];

        $this->saveSetting('API_KEY', $apiKey);
        $this->saveSetting('PLAYLIST', $selectedPlaylist);

        try {
            $playlistDirectory = GetDirSetting("playlists");
            $playlistJson = file_get_contents($playlistDirectory . "/" . $selectedPlaylist);

            $url = $this->webUrl("song_options/playeradd");
            $headers = $this->getWebsiteAuthorizationHeaders();
            $this->httpRequest($url, "POST", $playlistJson, $headers);

            return array('success' => true, 'message' => null);
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    public function getPlaylist()
    {
        return $this->readSetting("PLAYLIST");
    }
}
