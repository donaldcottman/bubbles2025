<?php

    include '../DBConnection.php';

    if (isset($_COOKIE['username'])) {
        $username = $_COOKIE['username'];
    }
    else {
        header('Location: /');
        exit();
    }

    $sessionNum = $_POST['sessionNum'];

    $sql = "SELECT * FROM replaysessions WHERE count = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $sessionNum);
    $stmt->execute();

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo $row["replaysessionjson"];
    }

    $stmt->close();
    $conn->close();

?>