<?php

namespace App;

use Exception;

require_once "ShowPulseBase.php";

final class ShowPulseJukeboxOptions extends ShowPulseBase
{
    public function save()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return null;
        }

        try {
            $playlistDirectory = GetDirSetting("playlists");
            $playlistJson = file_get_contents($playlistDirectory . "/" . $_POST[ShowPulseConstant::PLAYLIST]);

            $loadSuccessful = $this->loadConfiguration();

            if (!$loadSuccessful) {
                throw new Exception("Unable to load configuration file.");
            }

            $this->httpRequest(
                false,
                "shows/add-options",
                "PUT",
                $playlistJson,
                $this->getWebsiteAuthorizationHeaders()
            );

            return array('success' => true, 'message' => "Options updated successfully.");
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
    <h1 class="mb-4">Light Show Pulse Jukebox Options</h1>
    <div class="row">
        <div class="col-md-6">
            <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="<?= ShowPulseConstant::PLAYLIST ?>">Jukebox Playlist</label>
                    <select name="<?= ShowPulseConstant::PLAYLIST ?>" required>
                        <?php foreach ($playlist as $playlists): ?>
                            <option value="<?= $playlist ?>">
                                <?= str_replace(".json", "", $playlist); ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <small class="form-text text-muted">
                        Select the playlist that contains the songs that users can choose from for your jukebox.
                        Only sequences or songs contained in the "Main" portion of the playlist will be displayed
                        on your show's kiosk page.
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
</div>