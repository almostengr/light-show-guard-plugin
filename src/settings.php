<div class="container mt-5">
    <?php
    include_once "ShowPulseSettingForm.php";

    $settingForm = new ShowPulseSettingForm();
    $result = $settingForm->save();

    if ($result !== null) {
        if ($result['success']) {
            ?>
            <div class="alert alert-success" role="alert">
                Settings updated successfully.
            </div>
            <?php
        } else {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $result['message']; ?>
            </div>
            <?php
        }
    }
    ?>

    <h1 class="mb-4">Plugin Settings</h1>
    <div class="row">
        <div class="col-md-6">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="api_key">API Key</label>
                    <input type="text" class="form-control" id="api_key" name="api_key" value="<?= $settingForm->getApiKey(); ?>" required>
                    <small class="form-text text-muted">
                        Enter your API Key. You can get a key from the
                        <a href="https://guard.rhtservices.net" target="_blank">Light Show Guard website</a>.
                        NOTE: This key <strong>should not</strong> be shared with anyone.
                    </small>
                </div>
                <div class="form-group">
                    <label for="check_delay">Check Delay (seconds before end of song)</label>
                    <input type="number" class="form-control" id="check_delay" name="check_delay" min="1" max="15" value="5"
                        value="<?= $checkDelay; ?>" required>
                    <small class="form-text text-muted">
                        Enter the number of seconds before the end of the song, that the plugin will check for the next request.
                        Slow internet connections should be set to a higher value.
                    </small>
                </div>
                <div class="form-group">
                    <label for="playlist">Jukebox Playlist</label>
                    <select name="playlist" required>
                        <?php
                        $playlists = scandir(GetDirSetting("playlists"));
                        foreach ($playlist as $playlists) {
                            $selected = $settingForm->getPlaylist() === $playlist ? "selected" : "";
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
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
</div>