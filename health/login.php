<?php
session_start();
require_once 'config.php';

// Already logged in? Go to the profile page.
if (!empty($_SESSION['email'])) {
    header('Location: profile.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = (isset($_POST['email']) && is_string($_POST['email'])) ? trim($_POST['email']) : '';
    $password = (isset($_POST['password']) && is_string($_POST['password'])) ? $_POST['password'] : '';

    // The same email may belong to a user, a coach or a nutritionist: check each table.
    $tables = ['users', 'coaches', 'nutritionists'];

    foreach ($tables as $table) {
        $stmt = mysqli_prepare($con, "SELECT * FROM $table WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($row && password_verify($password, $row['password'])) {
            // New session id after login (prevents session fixation).
            session_regenerate_id(true);

            
            $_SESSION['email']     = $row['email'];
            $_SESSION['name']      = $row['name'] ?? $row['username'];   // users have "name", coaches have "username"
            $_SESSION['user_type'] = $table;

            header('Location: index.php');
            exit;
        }
    }

    // One message for both "unknown email" and "wrong password".
    $error = 'Incorrect email or password.';
}
?>

<!DOCTYPE html>
<html lang="en"

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
        <?php if ($error !== ''): ?>
            <p class="center" style="color:#c0392b;"><?= e($error) ?></p>
        <?php endif; ?>
        <form action="login.php" method="post" class="form">
            <input type="email" name="email" placeholder="email address" required value="<?= e($_POST['email'] ?? '') ?>">
            <input type="password" name="password" placeholder="Password" required>
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