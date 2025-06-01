<?php
session_start();
include 'config.php';
$id = $_GET['id'];
$logged_in_email = $_SESSION['email'] ?? null;
$nutr_id = $_GET['id'];
$user_type = $_SESSION['user_type'];
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
        $receiver_sql = "SELECT id FROM users WHERE email = '$receiver_email'";
        $receiver_query = mysqli_query($con, $receiver_sql);

        if ($receiver_query && mysqli_num_rows($receiver_query) > 0) {
            $receiver_row = mysqli_fetch_assoc($receiver_query);
            $receiver_id = $receiver_row['id'];

            header("Location: viewuser.php?id=$receiver_id#redirect1");
            exit;
        }
    } else {
        echo "Error: " . mysqli_error($con);
    }
}
if (isset($_POST['add_diet'])) {
    $user_email = $_POST['user_email'];
    $nutr_email = $_POST['nutr_email'];
    $sunday = $_POST['sunday'];
    $monday = $_POST['monday'];
    $tuesday = $_POST['tuesday'];
    $wednesday = $_POST['wednesday'];
    $thursday = $_POST['thursday'];
    $friday = $_POST['friday'];
    $saturday = $_POST['saturday'];



    $insert = "INSERT INTO diet (sunday, monday, tuesday, wednesday, thursday, friday, saturday, user_email, nutr_email) 
                VALUES ('$sunday', '$monday', '$tuesday', '$wednesday', '$thursday', '$friday', '$saturday','$user_email','$nutr_email')";
    $result = mysqli_query($con, $insert);

    if ($result) {

        header('Location: profile.php');
        exit;
    } else {
        echo "Error: " . mysqli_error($con);
    }
}
if (isset($_POST['add_ex'])) {
    $user_email = $_POST['user_email'];
    $coach_email = $_POST['coach_email'];
    $sunday = $_POST['sunday'];
    $monday = $_POST['monday'];
    $tuesday = $_POST['tuesday'];
    $wednesday = $_POST['wednesday'];
    $thursday = $_POST['thursday'];
    $friday = $_POST['friday'];
    $saturday = $_POST['saturday'];



    $insert = "INSERT INTO exercises (sunday, monday, tuesday, wednesday, thursday, friday, saturday, user_email, coach_email) 
                VALUES ('$sunday', '$monday', '$tuesday', '$wednesday', '$thursday', '$friday', '$saturday','$user_email','$coach_email')";
    $result = mysqli_query($con, $insert);

    if ($result) {
        $receiver_sql = "SELECT id FROM users WHERE email = '$user_email'";
        $receiver_query = mysqli_query($con, $receiver_sql);

        if ($receiver_query && mysqli_num_rows($receiver_query) > 0) {
            $receiver_row = mysqli_fetch_assoc($receiver_query);
            $receiver_id = $receiver_row['id'];

            header("Location: viewuser.php?id=$receiver_id#redirect2");
            exit;
        }
    } else {
        echo "Error: " . mysqli_error($con);
    }

}
if (isset($_POST['edit_table'])) {
    $id = $_POST['id'];
    $sunday = $_POST['sunday'];
    $monday = $_POST['monday'];
    $tuesday = $_POST['tuesday'];
    $wednesday = $_POST['wednesday'];
    $thursday = $_POST['thursday'];
    $friday = $_POST['friday'];
    $saturday = $_POST['saturday'];


    $sql = "UPDATE exercises SET sunday='$sunday', monday='$monday', tuesday='$tuesday',wednesday = '$wednesday', thursday='$thursday', friday='$friday',saturday = '$saturday'  WHERE id='$id'";

    mysqli_query($con, $sql);

    
    header("location: viewuser.php?id=$id");
}
if (isset($_POST['edit_diet'])) {
    $id = $_POST['id'];
    $sunday = $_POST['sunday'];
    $monday = $_POST['monday'];
    $tuesday = $_POST['tuesday'];
    $wednesday = $_POST['wednesday'];
    $thursday = $_POST['thursday'];
    $friday = $_POST['friday'];
    $saturday = $_POST['saturday'];


    $sql = "UPDATE diet SET sunday='$sunday', monday='$monday', tuesday='$tuesday',wednesday = '$wednesday', thursday='$thursday', friday='$friday',saturday = '$saturday'  WHERE id='$id'";

    mysqli_query($con, $sql);

    
    header("location: viewuser.php?id=$id");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <title>View User</title>
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
    $sql = "select * from users WHERE id = '$id'";
    $result = mysqli_query($con, $sql);
    if ($result) {
        while ($row_user = mysqli_fetch_assoc($result)) {
            $birthdate = $row_user['date_of_birth'];
            $birthDateTime = new DateTime($birthdate);
            $currentDate = new DateTime();
            $age = $currentDate->diff($birthDateTime)->y;
            $subscubed_user = $row_user['email'];
            $gender = $row_user['gender'];
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
                    <h5 class="card-title"><?= $row_user['name'] ?></h5>
                    <div style="display: flex ; justify-content:space-evenly">
                        <p class="card-text"> age:<?= $age ?></p>
                        <p class="card-text"> gender:<?= $gender_ar ?></p>
                    </div>
                    <p class="card-text">weight: <?= $row_user['weight'] ?></p>
                    <p class="card-text">height: <?= $row_user['height'] ?></p>
                    <p class="card-text">purpose: <?= $row_user['goal'] ?></p>

                </div>
            </div>

            <?php
            $sql = "SELECT DISTINCT sender_email FROM messages WHERE receiver_email = '$email'";
            $result = mysqli_query($con, $sql);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $sender_email_user = $row['sender_email'];
                    $user_sql = "SELECT * FROM users WHERE email = '$subscubed_user'";
                    $user_result = mysqli_query($con, $user_sql);
                    if ($user_result) {
                        while ($user_information = mysqli_fetch_assoc($user_result)) {
            ?>
                            <div class="messages">
                                <h2 class="center"> messages from user <?= $user_information['name'] ?></h2>


                                <div>
                                    <?php
                                    // Fetch all messages between the logged-in user and the sender
                                    $sql = "SELECT * FROM messages 
            WHERE (sender_email = '$sender_email_user' AND receiver_email = '$email') 
                OR (sender_email = '$email' AND receiver_email = '$sender_email_user') 
            ORDER BY time ASC"; // Sort messages by time in ascending order

                                    $result = mysqli_query($con, $sql);

                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {

                                            if ($row['sender_email'] == $email) {
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

                                <!-- Message Sending Form -->
                                <div class="send_message" id="redirect1">
                                    <form action="viewuser.php" method="post">
                                        <input id="redirect" type="text" name="message" placeholder="write your message here...">
                                        <input type="hidden" name="sender_email" value="<?= $email ?>">
                                        <input type="hidden" name="role" value="<?= $user_type ?>">
                                        <input type="hidden" name="receiver_email" value="<?= $sender_email_user ?>">
                                        <button type="submit" name="send_message" style="border-radius: none;">
                                            <i class="fa fa-send" style="font-size:20px"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
            <?php
                        }
                    }
                }
            }
            ?>
    <?php
        }
    } ?>
    <?php
    if ($user_type == 'nutritionists'):
        
    ?>
    <?php
    $check_sql = "SELECT * FROM diet WHERE user_email = '$sender_email_user'";
        $check_result = mysqli_query($con, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $diet = mysqli_fetch_assoc($check_result);
        ?>
            <div class="diet-table-container">
                <h1>Exercises Table</h1>
                <form action="viewuser.php" method="post">
                    <table class="diet-table">
                            <thead>
                                <tr>
                                    <th>the day</th>
                                    <th>diet</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>sunday</th>
                                    <td><input type="text" name="sunday" value="<?= $diet['sunday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>monday</th>
                                    <td><input type="text" name="monday" value="<?= $diet['monday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>tuesday</th>
                                    <td><input type="text" name="tuesday" value="<?= $diet['tuesday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>wednesday</th>
                                    <td><input type="text" name="wednesday" value="<?= $diet['wednesday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>thursday</th>
                                    <td><input type="text" name="thursday" value="<?= $diet['thursday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>friday</th>
                                    <td><input type="text" name="friday" value="<?= $diet['friday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>saturday</th>
                                    <td><input type="text" name="saturday" value="<?= $diet['saturday'] ?>"></td>
                                </tr>
                            </tbody>
                        

                    </table>
                    <input type="hidden" name="id" value="<?= $diet['id']?>">
                    <button type="submit" class="button" name="edit_diet">update</button>
                </form>
            </div>
        <?php
        } else {
            // If no exercise plan exists, show the form
        ?>
            <div>
                <h1>Add the appropriate diet plan</h1>
                <form action="viewuser.php" method="post" class="form" id="redirect2">
                    <input type="text" name="sunday" placeholder="sunday">
                    <input type="text" name="monday" placeholder=" monday">
                    <input type="text" name="tuesday" placeholder=" tuesday">
                    <input type="text" name="wednesday" placeholder="wednesday">
                    <input type="text" name="thursday" placeholder=" thursday">
                    <input type="text" name="friday" placeholder=" friday">
                    <input type="text" name="saturday" placeholder=" saturday">
                    <input type="hidden" name="user_email" value="<?= $sender_email_user ?>">
                    <input type="hidden" name="nutr_email" value="<?= $email ?>">
                    <input type="submit" name="add_diet">
                </form>
            </div>
    <?php }?>

        <?php
    elseif ($user_type == 'coaches'):
        $check_sql = "SELECT * FROM exercises WHERE user_email = '$sender_email_user'";
        $check_result = mysqli_query($con, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $exercise = mysqli_fetch_assoc($check_result); // Fetch the existing plan
        ?>
            <div class="diet-table-container">
                <h1>Exercises Table</h1>
                <form action="viewuser.php" method="post">
                    <table class="diet-table">
                            <thead>
                                <tr>
                                    <th>the day</th>
                                    <th>exercise</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>sunday</th>
                                    <td><input type="text" name="sunday" value="<?= $exercise['sunday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>monday</th>
                                    <td><input type="text" name="monday" value="<?= $exercise['monday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>tuesday</th>
                                    <td><input type="text" name="tuesday" value="<?= $exercise['tuesday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>wednesday</th>
                                    <td><input type="text" name="wednesday" value="<?= $exercise['wednesday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>thursday</th>
                                    <td><input type="text" name="thursday" value="<?= $exercise['thursday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>friday</th>
                                    <td><input type="text" name="friday" value="<?= $exercise['friday'] ?>"></td>
                                </tr>
                                <tr>
                                    <th>saturday</th>
                                    <td><input type="text" name="saturday" value="<?= $exercise['saturday'] ?>"></td>
                                </tr>
                            </tbody>
                        

                    </table>
                    <input type="hidden" name="id" value="<?= $exercise['id']?>">
                    <button type="submit" class="button" name="edit_table">update</button>
                </form>
            </div>
        <?php
        } else {
            // If no exercise plan exists, show the form
        ?>
            <div>
                <h1>Add Exercises plan</h1>
                <form action="viewuser.php" method="post" class="form" id="redirect2">
                    <input type="text" name="sunday" placeholder="sunday">
                    <input type="text" name="monday" placeholder=" monday">
                    <input type="text" name="tuesday" placeholder=" tuesday">
                    <input type="text" name="wednesday" placeholder=" wednesday">
                    <input type="text" name="thursday" placeholder=" thursday">
                    <input type="text" name="friday" placeholder=" friday">
                    <input type="text" name="saturday" placeholder=" saturday">
                    <input type="hidden" name="user_email" value="<?= $sender_email_user ?>">
                    <input type="hidden" name="coach_email" value="<?= $email ?>">
                    <input type="submit" name="add_ex">
                </form>
            </div>
    <?php }
    endif; ?>
    <script>

    </script>
    <footer>
        <div>
            <p>riyadh, Saudi Arabia</p>
            <p>+9664239403</p>
            <a href="mailto:healthcare@mail.com">healthcare@mail.com</a>
        </div>
        <p> Designed and Developed by Asma Rashed. 2025 </p>
    </footer>
</body>

</html>