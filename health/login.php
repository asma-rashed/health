<?php
session_start();
include 'config.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $tables = ['users', 'coaches', 'nutritionists'];
    $user_found = false;

    foreach ($tables as $table) {
        $sql = "SELECT * FROM $table WHERE email = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $stored_hash = $row['password'];

            if (password_verify($password, $stored_hash)) {
                $user_found = true;

                $_SESSION['email'] = $row['email'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['user_type'] = $table;
                $_SESSION['user_info'] = $row;
                $logged_user_email = $_SESSION['email'];
                header('location:index.php');
                exit();
            }
        }

        mysqli_stmt_close($stmt);

        if ($user_found) {
            break;
        }
    }

    if (!$user_found) {
        header('location:login.php');
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>login</title>
</head>

<body>
    <nav>
        <h1>health&care</h1>
        <div>
            <a href="index.php">Homepage</a>
            <?php if (isset($_SESSION['email'])) {?>
                <a href="profile.php">profile</a>
                <a href="logout.php">logout</a>
                <?php } else {
                    ?>
                    <a href="login.php">login</a>
                <?php }?>
        </div>
    </nav>
    

    <div>
        <h1>login</h1>
        <form action="login.php" method="post" class="form">
            <input type="email" name="email" placeholder="email address">
            <input type="password" name="password" placeholder="Password">
            <button type="submit" name="login">login</button>
        </form>
    </div>
    <p class="center">you don't have an account؟ <a href="signin.php">Sign up</a></p>
    
    <footer style="position: fixed; bottom:0; left:0; width:100%;">
        <div>
            <p>riyadh, Saudi Arabia</p>
            <p>+9664239403</p>
            <a href="mailto:healthcare@mail.com">healthcare@mail.com</a>
        </div>
        <p>Designed and Developed by Asma Rashed. 2025</p>
    </footer>
</body>

</html>