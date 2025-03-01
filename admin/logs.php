<?php
include 'header.php';
?>

<h1>logs</h1>

<div style="font-size:10px;">
<?php
    $stmt = $conn->prepare("SELECT LogID, UserID, LogData FROM omdb.logs ORDER BY LogID DESC;");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }

    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }

    $stmt->bind_result($logID, $userID, $logData);

    while ($stmt->fetch()) {
        echo "Log ID: $logID<br>";
        echo "User ID: $userID<br>";

        $jsonData = json_decode($logData, true);
        if ($jsonData === null) {
            echo "JSON decoding failed: " . json_last_error_msg() . "<br>";
        } else {
            echo "Log Data:<br>";
            displayTree($jsonData, 1);
            echo "<hr>";
        }
    }
    
    $stmt->close();
?>

<?php
function displayTree($data, $indent = 0) {
    foreach ($data as $key => $value) {
        echo str_repeat("&nbsp;", $indent * 4) . "$key: ";
        if (is_array($value)) {
            echo "<br>";
            displayTree($value, $indent + 1);
        } else {
            echo "$value<br>";
        }
    }
}
?>

</div>