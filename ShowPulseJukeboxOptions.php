<?php

namespace App;

use App\Commands\SendSequencesCommand;

require_once "commands\CommandBase.php";
?>
<div class="container mt-5">
    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $command = new SendSequencesCommand();
        $result = $command->execute();
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
                    <div class="form-text">
                        Synchronize all of the sequences that you have available with Light Show
                        Pulse website.
                        Visit the Light Show Pulse website, to enable or disable the sequences that
                        viewers can select from the kiosk page.
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Sync Sequences</button>
            </form>
        </div>
    </div>
</div>