<?php
session_start();
include 'config.php';


if (isset($_POST['sign_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $dob = $_POST['dob'];
    $goal = $_POST['goal'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $insert = "INSERT INTO users (name, email, phone, gender, weight, height, date_of_birth, goal, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $insert);
    mysqli_stmt_bind_param($stmt, "sssssssss", $name, $email, $phone, $gender, $weight, $height, $dob, $goal, $password);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['email'] = $row['email'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['user_type'] = 'users';
            $_SESSION['user_info'] = $row;

            header('Location: index.php');
            exit;
        } else {
            echo "Error: " . mysqli_error($con);
        }
    }
}

if (isset($_POST['sign_coach'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $experience = $_POST['experience'];
    $certifications = $_POST['certifications'];
    $hourly_rate = $_POST['hourly_rate'];

    $insert = "INSERT INTO coaches (username, email, phone, dob, gender, experience, certifications, hourly_rate, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($con, $insert);
    mysqli_stmt_bind_param($stmt, "sssssssss", $username, $email, $phone, $dob, $gender, $experience, $certifications, $hourly_rate, $password);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        // Fetch user info from database after inserting
        $sql = "SELECT * FROM coaches WHERE email = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['email'] = $row['email'];
            $_SESSION['name'] = $row['username'];
            $_SESSION['user_type'] = 'coaches';
            $_SESSION['user_info'] = $row;

            header('Location: index.php');
            exit();
        }
    } else {
        echo "Error: " . mysqli_error($con);
    }
}


if (isset($_POST['sign_nutr'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $experience = $_POST['experience'];
    $consultation_rate = $_POST['consultation_rate'];
    $certifications = $_POST['certifications'];

    $insert = "INSERT INTO nutritionists (username, email, phone, dob, gender, experience, consultation_rate, certifications, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($con, $insert);
    mysqli_stmt_bind_param($stmt, "sssssssss", $username, $email, $phone, $dob, $gender, $experience, $consultation_rate, $certifications, $password);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        // Fetch user info from database after inserting
        $sql = "SELECT * FROM nutritionists WHERE email = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['email'] = $row['email'];
            $_SESSION['name'] = $row['username'];
            $_SESSION['user_type'] = 'nutritionists';
            $_SESSION['user_info'] = $row;

            header('Location: index.php');
            exit();
        }
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
    <link rel="stylesheet" href="style.css">
    <title>Sign up</title>
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
    <header>
        <h1>Sign up</h1>
    </header>
    <h1>Create your account on our website to enjoy all the features</h1>

    <div class="options">
        <div class="option" onclick="showForm('form1')">User</div>
        <div class="option" onclick="showForm('form2')">trainer</div>
        <div class="option" onclick="showForm('form3')">Nutritionist</div>
    </div>

    <div id="form1" class="form-container" style="display: block;">
        <h3 class="center">User</h3>
        <form action="signin.php" method="post" class="form">
            <label for="name">name:</label>
            <input type="text" id="name" name="name">

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email">

            <label for="phone">phone number:</label>
            <input type="text" id="phone" name="phone">

            <div style="display: flex;">
                <label for="gender">gender:</label>
                <input type="radio" id="male" name="gender" value="male"><label for="male">male</label>
                <input type="radio" id="female" name="gender" value="female"><label for="female">female</label>
            </div>

            <label for="weight">weight:</label>
            <input type="number" id="weight" name="weight">

            <label for="height">height:</label>
            <input type="number" id="height" name="height">

            <label for="dob">date of birth:</label>
            <input type="date" id="dob" name="dob">

            <label for="goal">Purpose (weight loss, muscle building, etc.):</label>
            <textarea id="goal" name="goal" cols="30" rows="10"></textarea>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password">

            <button type="submit" name="sign_user">Sign</button>
        </form>
    </div>

    <div id="form2" class="form-container" style="display: none;">
        <h3 class="center">trainer</h3>
        <form action="signin.php" method="post" class="form">
            <label for="username">Name:</label>
            <input type="text" id="username" name="username">

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email">

            <label for="phone">phone number:</label>
            <input type="text" id="phone" name="phone">

            <label for="dob">date of birth:</label>
            <input type="date" id="dob" name="dob">

            <div style="display: flex;">
                <label for="gender">gender:</label>
                <input type="radio" id="male" name="gender" value="male"><label for="male">male</label>
                <input type="radio" id="female" name="gender" value="female"><label for="female">female</label>
            </div>

            <label for="experience">Years of experience:</label>
            <input type="number" id="experience" name="experience">

            <label for="certifications">Certificates:</label>
            <textarea id="certifications" name="certifications"></textarea>

            <label for="hourly_rate">Subscription fee:</label>
            <input type="text" id="hourly_rate" name="hourly_rate">

            <label for="password">Password:</label>
            <input type="password" id="password" name="password">

            <button type="submit" name="sign_coach">Sign</button>
        </form>
    </div>

    <div id="form3" class="form-container" style="display: none;">
        <h3 class="center">Nutritionist</h3>
        <form action="signin.php" method="post" class="form">
            <label for="username">Name:</label>
            <input type="text" id="username" name="username">

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email">

            <label for="phone">phone number:</label>
            <input type="text" id="phone" name="phone">

            <label for="dob">date of birth:</label>
            <input type="date" id="dob" name="dob">

            <div style="display: flex;">
                <label for="gender">gender:</label>
                <input type="radio" id="male" name="gender" value="male"><label for="male">male</label>
                <input type="radio" id="female" name="gender" value="female"><label for="female">female</label>
            </div>

            <label for="experience">Years of experience:</label>
            <input type="number" id="experience" name="experience">

            <label for="consultation_rate">Subscription fee:</label>
            <input type="text" id="consultation_rate" name="consultation_rate">

            <label for="certifications">Certificates:</label>
            <textarea id="certifications" name="certifications"></textarea>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password">

            <button type="submit" name="sign_nutr">Sign</button>
        </form>
    </div>

    <p class="center">you already have account? <a href="login.php">login</a></p>
    <script>
        function showForm(formId) {
            // Hide all forms
            const forms = document.querySelectorAll('.form-container');
            forms.forEach(form => form.style.display = 'none');

            // Show the selected form
            document.getElementById(formId).style.display = 'block';
        }
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