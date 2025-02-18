<?php

    include '../DBConnection.php';

    if (isset($_COOKIE['username'])) {
        $username = $_COOKIE['username'];
    } 
    else {
        header('Location: /');
        exit();
    }

    $sessionContentJSON = file_get_contents('../data/bubblesReplaySession.json');

    $stmt = mysqli_prepare($conn, "INSERT INTO replaysessions (username, replaysessionjson, timestamp) VALUES (?,?, NOW())");
    mysqli_stmt_bind_param($stmt, "ss", $username, $sessionContentJSON);


    if (mysqli_stmt_execute($stmt)) {
        echo "Text uploaded and inserted into database successfully!";
    } else {
        echo "Error inserting text into database: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

?>