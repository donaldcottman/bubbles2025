
<?php

    $serverName = "localhost";
    $DBUsername = "root";
    $DBPassword = "OkrthSNaK5qk";
    $DBname = "bubbles";

    $conn = new mysqli($serverName, $DBUsername, $DBPassword, $DBname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

?>
