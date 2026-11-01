<?php
session_start();
require_once 'config.php';

// This page is public (anyone can look at a coach), but only logged-in
// normal users can subscribe.
$me_email = $_SESSION['email'] ?? null;
$is_user  = $me_email !== null && ($_SESSION['user_type'] ?? '') === 'users';

// The id in the URL must be a whole number.
$provider_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$provider_id) {
    header('Location: index.php');
    exit;
}

$stmt = mysqli_prepare($con, 'SELECT * FROM coaches WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $provider_id);
mysqli_stmt_execute($stmt);
$provider = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$provider) {
    http_response_code(404);
    exit('Coach not found.');
}

// Does the logged-in user already have a coach? (one at a time)
$already_subscribed = false;
if ($is_user) {
    $stmt = mysqli_prepare($con, 'SELECT 1 FROM coach_subscription WHERE user_email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $me_email);
    mysqli_stmt_execute($stmt);
    $already_subscribed = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
}

// ---------------------------------------------------------------
// Subscribe
// ---------------------------------------------------------------
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid request. Please go back, refresh the page and try again.');
    }
    if (!$is_user) {
        header('Location: login.php');
        exit;
    }

    if ($already_subscribed) {
        $notice = 'You are already subscribed to a coach.';
    } else {
        // The coach's email is read from the database, not from the form.
        $stmt = mysqli_prepare($con, 'INSERT INTO coach_subscription (coach_email, user_email) VALUES (?, ?)');
        mysqli_stmt_bind_param($stmt, 'ss', $provider['email'], $me_email);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            header('Location: profile.php?success=1');
            exit;
        }
        $notice = 'An error occurred! Please try again.';
    }
}

// Age and gender for the card
$age = '-';
if (!empty($provider['dob'])) {
    $age = (new DateTime())->diff(new DateTime($provider['dob']))->y;
}
$gender_ar = ['male' => 'ذكر', 'female' => 'أنثى'][$provider['gender'] ?? ''] ?? '';
?>

<!DOCTYPE html>
<html lang="en"

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>View Trainers</title>
</head>

<body>
    <nav>
        <h1>health&care</h1>
        <div>
            <a href="index.php">Homepage</a>
            <?php if ($me_email): ?>
                <a href="profile.php">profile</a>
                <a href="logout.php">logout</a>
            <?php else: ?>
                <a href="login.php">login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="card" style="width: 18rem;">
        <img class="card-img-top" src="images/user-icon.png" alt="Card image cap">
        <div class="card-body">
            <h5 class="card-title"><?= e($provider['username']) ?></h5>
            <div style="display: flex ; justify-content:space-evenly">
                <p class="card-text"> age: <?= e($age) ?> </p>
                <p class="card-text"> gender: <?= e($gender_ar) ?></p>
            </div>
            <p class="card-text">Years of experience: <?= e($provider['experience']) ?> years</p>
            <p class="card-text">Certificates: <?= e($provider['certifications']) ?></p>
            <p class="card-text">subscription fees: <?= e($provider['hourly_rate']) ?></p>

            <?php if ($notice !== ''): ?>
                <p style="color:#c0392b; font-weight: bold;"><?= e($notice) ?></p>
            <?php endif; ?>

            <?php if ($is_user): ?>
                <?php if ($already_subscribed): ?>
                    <p style="color: green; font-weight: bold;">You have an ongoing subscription with a trainer.</p>
                <?php else: ?>
                    <form action="viewcoaches.php?id=<?= (int) $provider_id ?>" method="post" class="form">
                        <?= csrf_field() ?>
                        <button type="submit" name="submit">subscribe</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div>
            <p>riyadh, Saudi Arabia</p>
            <p>+9664239403</p>
            <a href="mailto:healthcare@mail.com">healthcare@mail.com</a>
        </div>
        <p>Designed and Developed by Asma Rashed. 2025</p>
    </footer>
</body>

</html>
