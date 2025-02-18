<?php

    session_start();
    include '../DBConnection.php';

    function checkCredentials($username, $password, $conn) {
        $sql = "SELECT password FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return password_verify($password, $row["password"]);
        } else {
            return false;
        }
    }


    $username = $_POST['username'];
    $password = $_POST['password'];

    if (checkCredentials($username, $password, $conn)) {
        // echo "Login successful!";
        // header("Login successful! BARTHOLOMEWW");
        setcookie('username', $username, time() + (86400 * 30), '/'); // Cookie for 30 days
        header("Location: /");
    } else {
        $_SESSION['error'] = true;
        header("Location: ./");
    }

    $conn->close();

?>