<?php
session_start();
include 'config.php';
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>
        alert('successfully subscribed!');
    </script>";
}

// Get the user type and data from the session
$user_type = $_SESSION['user_type'];
$user_info = $_SESSION['user_info'];

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    //-------------------------------------------------
    $tables = ['users', 'coaches', 'nutritionists'];
    $user_found = false;

    foreach ($tables as $table) {
        $sql = "SELECT * FROM $table WHERE email = '$email'";
        $query = mysqli_query($con, $sql);

        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            $id = $row['id'];
            $user_email = $row['email'];

            $_SESSION['user_type'] = $table;

            $user_found = true;
            break;
        }
    }

    if (!$user_found) {
        echo "User not found.";
    }
}
if (isset($_POST['send_message'])) {
    $sender_email = $_POST['sender_email'];
    $receiver_email = $_POST['receiver_email'];
    $role = $_POST['role'];
    $message = $_POST['message'];
    $time = date('Y-m-d H:i:s');

    $insert = "INSERT INTO messages (sender_email, receiver_email, role, message, time) 
                VALUES ('$sender_email', '$receiver_email', '$role', '$message', '$time')";
    $result = mysqli_query($con, $insert);
    if ($result) {
        header('location:profile.php#redirect');
    } else {
        echo "Error: " . mysqli_error($con);
        
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- <style>
        <?php include "style.css" ?>
    </style> -->
    <script src="/script.js" defer></script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" /> -->
    <title>Profile Page</title>
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
    <div class="profile-container">
        <h2 class="center">profile page</h2>

        <div id="profile-info">
            <!-- Dynamically display the profile data -->
            <?php if ($user_type == 'users'):
                $gender = $user_info['gender'];
                if ($gender === "male") {
                    $gender_ar = "ذكر";
                } elseif ($gender === "female") {
                    $gender_ar = "أنثى";
                } else {
                } ?>
                <div class="center">
                    <p>name: <?php echo $user_info['name']; ?></p>
                    <p>email: <?php echo $user_info['email']; ?></p>
                    <p>phone number: <?php echo $user_info['phone']; ?></p>
                    <p>gender: <?php echo $gender_ar ?></p>
                    <p>weight: <?php echo $user_info['weight']; ?> kg</p>
                    <p>height: <?php echo $user_info['height']; ?> cm</p>
                    <p>date of birth: <?php echo $user_info['date_of_birth']; ?></p>
                    <p>purpose: <?php echo $user_info['goal']; ?></p>

                </div>
                <hr>
                <div style="display: flex; justify-content:space-evenly;">
                    <div style="border: 1px solid black; padding:4px; text-align:center; border-radius:4px">
                        <h1>Your Trainer</h1>
                    <?php
                    $sql = "SELECT coach_email FROM coach_subscription WHERE user_email = ?";
                    $stmt = mysqli_prepare($con, $sql);

                    $coach_email = null; // Initialize to avoid undefined variable issues

                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "s", $email);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);
                            $coach_email = $row['coach_email']; // Assign coach email if found
                        }
                    }

                    // Check if a coach email was found before running the second query
                    if ($coach_email) {
                        $sql = "SELECT * FROM coaches WHERE email = ?";
                        $stmt = mysqli_prepare($con, $sql);

                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "s", $coach_email);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            if ($row = mysqli_fetch_assoc($result)) {
                                $birthdate = $row['dob'];
                                $birthDateTime = new DateTime($birthdate);
                                $currentDate = new DateTime();
                                $age = $currentDate->diff($birthDateTime)->y;

                                $gender = $row['gender'];
                                $gender_ar = ($gender === "male") ? "ذكر" : (($gender === "female") ? "أنثى" : "");

                    ?>
                                    <img src="images/user-icon.png" alt="" width="100">
                                    <h2 class="card-title"><?= htmlspecialchars($row['username']) ?></h2>
                                    <div style="display: flex ; justify-content:space-evenly">
                                        <p class="card-text">age: <?= $age ?> </p>
                                        <p class="card-text">gender: <?= htmlspecialchars($gender_ar) ?></p>
                                    </div>
                                    <p class="card-text">Years of experience: <?= htmlspecialchars($row['experience']) ?> years</p>
                                    <p class="card-text">Certificates: <?= htmlspecialchars($row['certifications']) ?></p>
                                    <?php
                            }
                        }
                    } else {
                        // If no subscription, show message
                        echo "<p style='color: green; font-weight: bold; text-align: center;'> You do not have a subscription with any coach.</p>";
                    }
                    ?>
                    </div>

<div>
    <div style="border: 1px solid black; padding:4px;text-align:center; border-radius:4px">
        <h1>Your nutritionist</h1>
                    <?php
                    $sql = "SELECT nutr_email FROM nutr_subscription WHERE user_email = ?";
                    $stmt = mysqli_prepare($con, $sql);

                    $nutr_email = null; // Initialize to avoid undefined variable issues

                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "s", $email);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if ($row = mysqli_fetch_assoc($result)) {
                            $nutr_email = $row['nutr_email']; // Assign nutritionist email if found
                        }
                    }

                    // Check if a nutritionist subscription exists before running the second query
                    if ($nutr_email) {
                        $sql = "SELECT * FROM nutritionists WHERE email = ?";
                        $stmt = mysqli_prepare($con, $sql);

                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "s", $nutr_email);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            if ($row = mysqli_fetch_assoc($result)) {
                                $birthdate = $row['dob'];
                                $birthDateTime = new DateTime($birthdate);
                                $currentDate = new DateTime();
                                $age = $currentDate->diff($birthDateTime)->y;

                                $gender = $row['gender'];
                                $gender_ar = ($gender === "male") ? "ذكر" : (($gender === "female") ? "أنثى" : "");

                    ?>
                                        <img src="images/user-icon.png" alt="" width="100">
                                        <h2 class="card-title"><?= htmlspecialchars($row['username']) ?></h2>
                                        <div style="display: flex ; justify-content:space-evenly">
                                            <p class="card-text">age: <?= $age ?> </p>
                                            <p class="card-text">gender: <?= htmlspecialchars($gender_ar) ?></p>
                                        </div>
                                        <p class="card-text">Years of experience: <?= htmlspecialchars($row['experience']) ?> years</p>
                                        <p class="card-text">Certificates: <?= htmlspecialchars($row['certifications']) ?></p>
                                        <?php
                            }
                        }
                    } else {
                        // If no subscription, show message
                        echo "<p style='color: green; font-weight: bold; text-align: center;'> You do not have a subscription with any nutritionist.</p>";
                    }
                    ?>
                    </div>
                </div>

                </div>
                <hr>
                <h2 class="center" id="redirect">
                    Contact your trainer or nutritionist
                </h2>
                <!-- <div class="options">
                    <div class="option" onclick="showForm('form1')">الtrainer</div>
                    <div class="option" onclick="showForm('form2')">اخصائي التغذية</div>
                </div> -->
                <div style="display: flex; width:100%;">
                
                <div id="form1" class="form-container" style="width: 50%;">
                    <h2 class="center">Contact your trainer</h2>
                    <div class="messages">
                        <div>
                            <?php
                            // Fetch all messages between the logged-in user and the coach
                            $sql = "SELECT * FROM messages 
                                        WHERE (sender_email = '$user_email' AND receiver_email = '$coach_email') 
                                            OR (sender_email = '$coach_email' AND receiver_email = '$user_email') 
                                        ORDER BY time ASC"; // Sort messages by time in ascending order
                            $result = mysqli_query($con, $sql);

                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    // Check if the message is sent by the logged-in user
                                    if ($row['sender_email'] == $user_email) {
                            ?>
                                        <div class="container">
                                            <span class="namespan"><?= $row['sender_email'] ?></span>
                                            <p><?= $row['message'] ?></p>
                                            <span class="time-right"><?= $row['time'] ?></span>
                                        </div>
                                    <?php
                                    } else {
                                        // Message is received from the coach
                                    ?>
                                        <div class="container darker">
                                            <span class="right"><?= $row['sender_email'] ?></span>
                                            <p><?= $row['message'] ?></p>
                                            <span class="time-left"><?= $row['time'] ?></span>
                                        </div>
                            <?php
                                    }
                                }
                            }
                            ?>
                        </div>

                        <!-- Message Sending Form -->
                        <div class="send_message">
                            <form action="profile.php" method="post">
                                <input  type="text" name="message">
                                <input type="hidden" name="sender_email" value="<?= $user_email ?>">
                                <input type="hidden" name="role" value="<?= $user_type ?>">
                                <input type="hidden" name="receiver_email" value="<?= $coach_email ?>">
                                <button type="submit" name="send_message" style="border-radius: none;">
                                    <i class="fa fa-send" style="font-size:100%"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="form2" class="form-container" style="width: 50%;">
                    <h2 class="center">Contact your nutritionist</h2>
                    <div class="messages">
                        <div>
                            <?php
                            $sql = "SELECT * FROM messages 
                                WHERE (sender_email = '$user_email' AND receiver_email = '$nutr_email') 
                                    OR (sender_email = '$nutr_email' AND receiver_email = '$user_email') 
                                ORDER BY time ASC"; 
                            $result = mysqli_query($con, $sql);

                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    if ($row['sender_email'] == $user_email) {
                            ?>
                                        <div class="container">
                                            <span class="namespan"><?= $row['sender_email'] ?></span>
                                            <p><?= $row['message'] ?></p>
                                            <span class="time-right"><?= $row['time'] ?></span>
                                        </div>
                                    <?php
                                    } else {
                                        
                                    ?>
                                        <div class="container darker">
                                            <span class="right"><?= $row['sender_email'] ?></span>
                                            <p><?= $row['message'] ?></p>
                                            <span class="time-left"><?= $row['time'] ?></span>
                                        </div>
                            <?php
                                    }
                                }
                            }
                            ?>
                        </div>

                        
                        <div class="send_message">
                            <form action="profile.php" method="post">
                                <input type="text" name="message">
                                <input type="hidden" name="sender_email" value="<?= $user_email ?>">
                                <input type="hidden" name="role" value="<?= $user_type ?>">
                                <input type="hidden" name="receiver_email" value="<?= $nutr_email ?>">
                                <button type="submit" name="send_message" style="border-radius: none;">
                                    <i class="fa fa-send" style="font-size:100%"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                </div>
                <div class="diet-table-container">
                    <h1>Weekly diet plan</h1>
                    <table class="diet-table">
                        <thead>
                            <tr>
                                <th>the day</th>
                                <th>Diet plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include 'config.php';
                            $user_email = $_SESSION['email'] ?? null; 
                            $sql = "SELECT * FROM diet WHERE user_email = '$user_email'";
                            $result = mysqli_query($con, $sql);

                            if ($result && mysqli_num_rows($result) > 0) {
                                $row = mysqli_fetch_assoc($result);

                                $days = [
                                    "monday" => "monday",
                                    "tuesday" => "tuesday",
                                    "wednesday" => "wednesday",
                                    "thursday" => "thursday",
                                    "friday" => "friday",
                                    "saturday" => "saturday",
                                    "sunday" => "sunday"
                                ];

                                foreach ($days as $key => $day_name) {
                                    echo "<tr>
                                <td>$day_name</td>
                                <td>" . ($row[$key] ?: "there is no diet plan") . "</td>
                                </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='2'>No diet plan added yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="diet-table-container">
                    <h1>Weekly exercises plan</h1>
                    <table class="diet-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Exercise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include 'config.php';
                            $user_email = $_SESSION['email'] ?? null;
                            $sql = "SELECT * FROM exercises WHERE user_email = '$user_email'";
                            $result = mysqli_query($con, $sql);

                            if ($result && mysqli_num_rows($result) > 0) {
                                $row = mysqli_fetch_assoc($result);

                                $days = [
                                    "monday" => "monday",
                                    "tuesday" => "tuesday",
                                    "wednesday" => "wednesday",
                                    "thursday" => "thursday",
                                    "friday" => "friday",
                                    "saturday" => "saturday",
                                    "sunday" => "sunday"
                                ];

                                foreach ($days as $key => $day_name) {
                                    echo "<tr>
                                <td>$day_name</td>
                                <td>" . ($row[$key] ?: "there is no exercises plan") . "</td>
                              </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='2'>No exercise plan added yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($user_type == 'coaches'):
                $gender = $user_info['gender'];
                if ($gender === "male") {
                    $gender_ar = "ذكر";
                } elseif ($gender === "female") {
                    $gender_ar = "أنثى";
                } else {
                } ?>
                <div class="center">

                    <p>name: <?php echo $user_info['username']; ?></p>
                    <p>email: <?php echo $user_info['email']; ?></p>
                    <p>phone number: <?php echo $user_info['phone']; ?></p>
                    <p>gender: <?php echo $gender_ar ?></p>
                    <p>date of birth: <?php echo $user_info['dob']; ?></p>
                    <p>Years of experience: <?php echo $user_info['experience']; ?> years</p>
                    <p>Certificates: <?php echo $user_info['certifications']; ?></p>
                    <p>subscription fees: <?php echo $user_info['hourly_rate']; ?> Saudi Riyal </p>
                </div>
                <hr>
                <h1>Subscribers:</h1>
                <div style="display: flex; flex-wrap:wrap; justify-content:space-evenly; margin:8px">
                    <?php
                    $sql = "SELECT DISTINCT user_email FROM coach_subscription WHERE coach_email = '$user_email'";
                    $result = mysqli_query($con, $sql);
                    if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $user_coach_email = $row['user_email'];
                            $user_sql = "SELECT * FROM users WHERE email = '$user_coach_email'";
                            $user_result = mysqli_query($con, $user_sql);
                            if ($user_result) {
                                while ($user_information = mysqli_fetch_assoc($user_result)) {
                    ?>
                                    <div style="border:1px solid black; padding:5px; text-align:center; width:30%;">
                                        <img src="images/user-icon.png" width="200" alt="">
                                        <h2><?= $user_information['name'] ?></h2>
                                        <a href="viewuser.php?id=<?= $user_information['id'] ?>">view</a>
                                    </div>
                    <?php }
                            }
                        }
                    } else{
                        echo '<p> No subscribers </p> ';
                    } ?>
                </div>
                <div>
                    <!--  -->
                </div>

            <?php elseif ($user_type == 'nutritionists'):
                $gender = $user_info['gender'];
                if ($gender === "male") {
                    $gender_ar = "ذكر";
                } elseif ($gender === "female") {
                    $gender_ar = "أنثى";
                } else {
                } ?>
                <div class="center">
                    <p>name: <?php echo $user_info['username']; ?></p>
                    <p>email: <a href="mailto:<?php echo $user_info['email']; ?>"><?php echo $user_info['email']; ?></a> </p>
                    <p>phone number: <a href="tel:+966<?php echo $user_info['phone']; ?>"><?php echo $user_info['phone']; ?></a></p>
                    <p>gender: <?php echo $gender_ar ?></p>
                    <p>date of birth: <?php echo $user_info['dob']; ?></p>
                    <p>Years of experience: <?php echo $user_info['experience']; ?> years</p>
                    <p>Certificates: <?php echo $user_info['certifications']; ?></p>
                    <p>Subscription fees: $<?php echo $user_info['consultation_rate']; ?> Saudi Riyal</p>

                </div>
                <hr>
                <h1>Subscribers:</h1>
                <div style="display: flex; flex-wrap:wrap; justify-content:space-evenly; margin:8px">
                    <?php
                    $sql = "SELECT DISTINCT user_email FROM nutr_subscription WHERE nutr_email = '$user_email'";
                    $result = mysqli_query($con, $sql);
                    if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $user_nutr_email = $row['user_email'];
                            $user_sql = "SELECT * FROM users WHERE email = '$user_nutr_email'";
                            $user_result = mysqli_query($con, $user_sql);
                            if ($user_result) {
                                while ($user_information = mysqli_fetch_assoc($user_result)) {
                    ?>
                                    <div style="border:1px solid black; padding:5px; text-align:center; width:30%;">
                                        <img src="images/user-icon.png" width="200" alt="">
                                        <h2><?= $user_information['name'] ?></h2>
                                        <a href="viewuser.php?id=<?= $user_information['id'] ?>">view</a>
                                    </div>
                    <?php }
                            }
                        }
                    } ?>
                </div>
                <div>
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