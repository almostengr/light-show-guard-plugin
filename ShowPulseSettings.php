<?php

namespace App;

use Exception;

require_once "ShowPulseBase.php";

final class ShowPulseSettings extends ShowPulseBase
{
    public $playlist;
    public $apiKey;
    public $betaApiKey;
    public $environment;

    public function __construct()
    {
        $this->apiKey = $this->readSetting(ShowPulseConstant::API_KEY);
        $this->betaApiKey = $this->readSetting(ShowPulseConstant::BETA_API_KEY);
        $this->environment = $this->readSetting(ShowPulseConstant::ENVIRONMENT);
        $this->playlist = $this->readSetting(ShowPulseConstant::PLAYLIST);
    }

    public function save()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return null;
        }

        $this->apiKey = trim($_POST[ShowPulseConstant::API_KEY]);
        $this->betaApiKey = trim($_POST[ShowPulseConstant::BETA_API_KEY]);
        $this->environment = $_POST[ShowPulseConstant::ENVIRONMENT];
        $this->playlist = $_POST[ShowPulseConstant::PLAYLIST];

        $this->saveSetting(ShowPulseConstant::API_KEY, $this->apiKey);
        $this->saveSetting(ShowPulseConstant::BETA_API_KEY, $this->betaApiKey);
        $this->saveSetting(ShowPulseConstant::ENVIRONMENT, $this->environment);
        $this->saveSetting(ShowPulseConstant::PLAYLIST, $this->playlist);

        try {
            $playlistDirectory = GetDirSetting("playlists");
            $playlistJson = file_get_contents($playlistDirectory . "/" . $this->playlist);

            $this->httpRequest(
                false,
                "shows/add-options",
                "PUT",
                $playlistJson,
                $this->getWebsiteAuthorizationHeaders()
            );

            return array('success' => true, 'message' => "Settings updated successfully.");
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}
?>
<div class="container mt-5">
    <?php
    $settingForm = new ShowPulseSettings();
    $result = $settingForm->save();
    $playlists = scandir(GetDirSetting("playlists"));

    if (!is_null($result)) {
        $alertClass = $result['success'] ? "alert-success" : "alert-danger";
        ?>
        <div class="alert <?= $alertClass; ?>" role="alert">
            <?= $result['message']; ?>
        </div>
        <?php
    }
    ?>
    <h1 class="mb-4">Light Show Pulse Settings</h1>
    <div class="row">
        <div class="col-md-6">
            <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="api_key">API Key</label>
                    <input type="text" class="form-control" id="api_key" name="<?= ShowPulseConstant::API_KEY; ?>"
                        value="<?= $settingForm->apiKey; ?>" required>
                    <small class="form-text text-muted">
                        Enter your API Key. You can get a key from the
                        <a href="https://showpulse.rhtservices.net" target="_blank">Light Show Pulse website</a>.
                        NOTE: This key <strong>should not</strong> be shared with anyone.
                    </small>
                </div>
                <div class="form-group">
                    <label for="<?= ShowPulseConstant::PLAYLIST ?>">Jukebox Playlist</label>
                    <select name="<?= ShowPulseConstant::PLAYLIST ?>" required>
                        <?php foreach ($playlist as $playlists): ?>
                            <option value="<?= $playlist ?>" <?= $settingForm->playlist === $playlist ? "selected" : ""; ?>>
                                <?= str_replace(".json", "", $playlist); ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <small class="form-text text-muted">
                        Select the playlist that contains the songs that users can choose from for your jukebox.
                    </small>
                </div>
                <div class="form-group">
                    <label for="<?= ShowPulseConstant::ENVIRONMENT ?>">Environment</label>
                    <select id="<?= ShowPulseConstant::ENVIRONMENT ?>" name="<?= ShowPulseConstant::ENVIRONMENT ?>" required>
                        <option value="<?= ShowPulseConstant::PRODUCTION_ENVIRONMENT ?>" <?php if (!$settingForm->useBetaEnvironment()) {
                              echo "selected";
                          } ?>>Production
                        </option>
                        <option value="<?= ShowPulseConstant::BETA_ENVIRONMENT ?>" <?php if ($settingForm->useBetaEnvironment()) {
                              echo "selected";
                          } ?>>Beta (Testing)</option>
                    </select>
                    <small class="form-text text-muted">
                        Enter the environment you want to use. Production is the default. Separate API key is
                        required to test using the Beta environment.
                    </small>
                </div>
                <div class="form-group">
                    <label for="<?= ShowPulseConstant::BETA_API_KEY ?>">Beta API Key</label>
                    <input type="text" class="form-control" id="<?= ShowPulseConstant::BETA_API_KEY ?>" name="<?= ShowPulseConstant::BETA_API_KEY ?>"
                        value="<?= $settingForm->betaApiKey; ?>">
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