<?php

include_once "common.php";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve form data
    $apiKey = $_POST["api_key"];
    $checkDelay = $_POST["check_delay"];
    
    // Save settings locally
    saveSetting("LSG_API_KEY", $apiKey);
    saveSetting("CHECK_DELAY", $checkDelay);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugin Settings</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <?php
        // Display warning message if latitude or longitude is missing
        $latitude = $_POST["latitude"] ?? '';
        $longitude = $_POST["longitude"] ?? '';
        if (empty($latitude) || empty($longitude)) {
            echo '<div class="alert alert-warning" role="alert">';
            echo "Latitude and longitude are required. Please provide this information on the appropriate screen within Falcon Pi Player.";
            echo '</div>';
        }
        ?>
        
        <h1 class="mb-4">Plugin Settings</h1>
        <div class="row">
            <div class="col-md-6">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="api_key">API Key:</label>
                        <input type="text" class="form-control" id="api_key" name="api_key">
                    </div>
                    <div class="form-group">
                        <label for="check_delay">Check Delay (seconds before end of song):</label>
                        <input type="number" class="form-control" id="check_delay" name="check_delay" min="1" max="15" value="5">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
