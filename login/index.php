
<?php
    session_start();

    if (isset($_GET['logout']) && isset($_COOKIE['username'])) {
        setcookie('username', '', time() - 3600, '/');
        $current_url_without_query = strtok($_SERVER["REQUEST_URI"], '?');
        header('Location: ' . $current_url_without_query);
        exit();
    } 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/d3/6.7.0/d3.js"
    integrity="sha512-+4O1tTAf2Ku73pJ0uXuoTFbXM8agSnDhmqlMylH37E1JvyLu+ZoX2Cr/E16Xljt9R9WD1tzgRXGLQPb2YT1m1A=="
    crossorigin="anonymous"
    ></script>
    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"
    integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ=="
    crossorigin="anonymous"
    ></script>
    <title>Bubbles: Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/@popperjs/core@2" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../style.css" />
</head>
<body style="height: initial !important; background-color: #060606 !important;">
    <form action="login.php" method="POST">
        <div>
            <div class="modal-dialog" role="document">
                <div class="modal-content" style="border: 3px solid #00b2e7 !important;">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: #00b2e7 !important;">Bubbles Login</h5>
                    <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button> -->
                </div>
                <div class="modal-body">
                    <div class="modalLoginInputs">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                        <br>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <?php
                        if (isset($_SESSION['error'])) {
                            unset($_SESSION['error']);
                            echo '<p style="color: red; text-align: center;">Invalid username or password.</p>';
                        }
                    ?>
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn-info" value="Login">
                </div>
                </div>
            </div>
        </div>
    </form>
    <!-- <form action="login.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" value="Login">
    </form> -->
</body>
</html>