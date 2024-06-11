<?php

namespace App;
use Exception;

require_once "ShowPulseBase.php";

final class ShowPulseSettingForm extends ShowPulseBase
{
    public $playlist;
    public $apiKey;
    public $betaApiKey;
    public $environment;

    public function __construct()
    {
        $this->playlist = $this->readSetting("PLAYLIST");
        $this->apiKey = $this->readSetting("API_KEY");
        $this->betaApiKey = $this->readSetting("BETA_API_KEY");
        $this->environment = $this->readSetting("ENVIRONMENT");
    }

    public function save()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return null;
        }

        $this->apiKey = trim($_POST["api_key"]);
        $this->betaApiKey = trim($_POST["test_api_key"]);
        $this->playlist = $_POST["playlist"];
        $this->environment = $_POST["environment"];

        $this->saveSetting('API_KEY', $this->apiKey);
        $this->saveSetting('PLAYLIST', $this->playlist);
        $this->saveSetting("ENVIRONMENT", $this->environment);
        $this->saveSetting("BETA_API_KEY", $this->betaApiKey);

        try {
            $playlistDirectory = GetDirSetting("playlists");
            $playlistJson = file_get_contents($playlistDirectory . "/" . $this->playlist);

            $url = $this->websiteUrl("song_options/playeradd");
            $this->httpRequest($url, "POST", $playlistJson, $this->getWebsiteAuthorizationHeaders());

            return array('success' => true, 'message' => "Settings updated successfully.");
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}
?>
<div class="container mt-5">
    <?php
    $settingForm = new ShowPulseSettingForm();
    $result = $settingForm->save();

    if ($result !== null) {
        $alertClass = $result['success'] ? "alert-success" : "alert-danger";
        ?>
        <div class="alert <?php echo $alertClass; ?>" role="alert">
            <?php echo $result['message']; ?>
        </div>
        <?php
    }
    ?>
    <h1 class="mb-4">Light Show Pulse Settings</h1>
    <div class="row">
        <div class="col-md-6">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="api_key">API Key</label>
                    <input type="text" class="form-control" id="api_key" name="api_key" value="<?= $settingForm->apiKey; ?>" required>
                    <small class="form-text text-muted">
                        Enter your API Key. You can get a key from the
                        <a href="https://showpulse.rhtservices.net" target="_blank">Light Show Pulse website</a>.
                        NOTE: This key <strong>should not</strong> be shared with anyone.
                    </small>
                </div>
                <div class="form-group">
                    <label for="playlist">Jukebox Playlist</label>
                    <select name="playlist" required>
                        <?php
                        $playlists = scandir(GetDirSetting("playlists"));
                        foreach ($playlist as $playlists) {
                            $selected = $settingForm->playlist === $playlist ? "selected" : "";
                            ?>
                            <option value="<?= $playlist ?>" <?= $selected; ?>>
                                <?= $playlist ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <small class="form-text text-muted">
                        Select the playlist that contains the songs that users can choose from for your jukebox.
                    </small>
                </div>
                <div class="form-group">
                    <label for="environment">Environment</label>
                    <select id="environment" name="environment" required>
                        <option value="PRODUCTION" <?php if (!$settingForm->useBetaEnvironment()) {
                            echo "selected";
                        } ?>>Production
                        </option>
                        <option value="BETA" <?php if ($settingForm->useBetaEnvironment()) {
                            echo "selected";
                        } ?>>Beta</option>
                    </select>
                    <small class="form-text text-muted">
                        Enter the environment you want to use. Production is the default. Separate API key is
                        required to test using the Beta environment.
                    </small>
                </div>
                <div class="form-group">
                    <label for="beta_api_key">Beta API Key</label>
                    <input type="text" class="form-control" id="beta_api_key" name="beta_api_key" value="<?= $settingForm->betaApiKey; ?>">
                    <small class="form-text text-muted">
                        Beta API Key can be used to test what will happen with your show. Separate API key is required 
                        for beta environment. You can sign up at 
                        <a href="https://showpulsebeta.rhtservices.net" target="_blank">https://showpulsebeta.rhtservices.net</a>.
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
</div>