<?php
session_start();
include 'config.php';
$id = $_GET['id'];

$logged_in_email = $_SESSION['email'] ?? null;
$coach_id = $_GET['id'];
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $query = mysqli_query($con, $sql);

    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $id = $row['id'];
        $user_email = $row['email'];
    } else {
    }
}
if (isset($_POST['submit'])) {
    $coach_email = $_POST['coach_email'];
    $user_email = $user_email;
    $coach_id = $_GET['id'];


    $check_query = "SELECT * FROM coach_subscription WHERE coach_email = '$coach_email' AND user_email = '$user_email'";
    $check_result = mysqli_query($con, $check_query);

    if (mysqli_num_rows($check_result) > 0) {

        echo "<script>
                alert('You are already subscribed to this coach.');
                window.location.href = 'viewcoaches.php?id=$coach_id';
                </script>";
    } else {

        $insert = "INSERT INTO coach_subscription (coach_email, user_email) VALUES ('$coach_email', '$user_email')";
        $result = mysqli_query($con, $insert);

        if ($result) {
            header("Location: profile.php?success=1");
            exit;
        } else {
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

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
            <?php if (isset($_SESSION['email'])) { ?>
                <a href="profile.php">profile</a>
                <a href="logout.php">logout</a>
            <?php } else {
            ?>
                <a href="login.php">login</a>
            <?php } ?>
        </div>
    </nav>
    <?php
    $sql = "select * from coaches where id = '$coach_id'";
    $result = mysqli_query($con, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $birthdate = $row['dob'];
            $birthDateTime = new DateTime($birthdate);
            $currentDate = new DateTime();
            $age = $currentDate->diff($birthDateTime)->y;

            $gender = $row['gender'];
            if ($gender === "male") {
                $gender_ar = "ذكر";
            } elseif ($gender === "female") {
                $gender_ar = "أنثى";
            } else {
            }
    ?>
            <div class="card" style="width: 18rem;">
                <img class="card-img-top" src="images/user-icon.png" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['username'] ?></h5>
                    <div style="display: flex ; justify-content:space-evenly">
                        <p class="card-text"> age:<?= $age ?> </p>
                        <p class="card-text"> gender:<?= $gender_ar ?></p>
                    </div>
                    <p class="card-text">Years of experience: <?= $row['experience'] ?> years</p>
                    <p class="card-text">Certificates: <?= $row['certifications'] ?></p>
                    <p class="card-text">subscription fees: <?= $row['hourly_rate'] ?></p>
                    <?php if ($logged_in_email) {
                        // Check if the logged-in user exists in the 'users' table
                        $user_check_query = "SELECT * FROM users WHERE email = '$logged_in_email'";
                        $user_check_result = mysqli_query($con, $user_check_query);

                        if (mysqli_num_rows($user_check_result) > 0) {
                            // The logged-in user is a regular user, check for an existing subscription
                            $coach_email = $row['email']; // Get the coachitionist's email
                            $subscription_check_query = "SELECT * FROM coach_subscription WHERE user_email = '$logged_in_email'";
                            $subscription_check_result = mysqli_query($con, $subscription_check_query);

                            if (mysqli_num_rows($subscription_check_result) > 0) {
                                // If subscription exists, show message instead of button
                                echo "<p style='color: green; font-weight: bold;'>You have an ongoing subscription with a trainer.</p>";
                            } else {
                                // If no subscription, show the subscribe button
                    ?>
                                <form action="viewcoaches.php" method="post" class="form">
                                    <input type='hidden' value="<?= $row['email'] ?>" name="coach_email">
                                    <button type="submit" name="submit">subscripe</button>
                                </form>
                    <?php
                            }
                        }
                    } ?>

                    <!--  -->
                </div>
            </div>
    <?php
        }
    } ?>
    <script>

    </script>
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