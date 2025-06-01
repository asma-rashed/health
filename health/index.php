<?php
session_start();
include 'config.php';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>
        alert('successfully subscribed!');
    </script>";
}
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

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

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="/script.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <title>Homepage</title>
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
        <h1>health&care</h1> <br>
        <p>Our platform combines physical exercise and healthy nutrition, allowing users to communicate with professional trainers and nutritionists through chat. Personalized workout schedules and customized meal plans are designed based on each user's needs to ensure they achieve their health and fitness goals effectively.</p>
    </header>

    <div class="adv">
        <div>
            <i class="fa fa-id-card" style="font-size:36px"></i>
            <p>Browse trainers and nutritionists: their profiles, certifications, and subscription fees.</p>
            <?php if (isset($_SESSION['email'])) {
                echo '<a href="#left1">learn more</a>';
            } else {
                echo '<a href="login.php">learn more</a>';
            }
            ?>
        </div>
        <div>
            <i class="fa fa-calendar-check-o" style="font-size:36px"></i>
            <p>Receive your personalized diet plan and exercises table from the trainer based on your specific needs.</p>
            <?php if (isset($_SESSION['email'])) {
                echo '<a href="profile.php">learn more</a>';
            } else {
                echo '<a href="login.php">learn more</a>';
            }
            ?>
        </div>
        <div>
            <i class="fa fa-comments" style="font-size:36px"></i>
            <p>Receive customized nutrition advice and a personalized workout schedule from your trainer based on your individual needs.</p>
            <?php if (isset($_SESSION['email'])) {
                echo '<a href="profile.php">learn more</a>';
            } else {
                echo '<a href="login.php">learn more</a>';
            }
            ?>
        </div>
    </div>
    <h1 id="left1" style="text-align: right;">trainers:</h1>
    <div class="wrapper">
        <i id="left" class="fa-solid  fas fa-angle-left"></i>
        <ul class="carousel">
            <?php
            $sql = "select * from coaches";
            $result = mysqli_query($con, $sql);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {

            ?>
                    <li class="card1">
                        <div class="img"><img src="images/user-icon.png"
                                alt="" draggable="false"> </div>
                        <h2>
                            <?= $row['username'] ?>
                        </h2>
                        <span>Years of experience:<?= $row['experience'] ?>
                        </span>
                        <span></span>
                        <a href="viewcoaches.php?id=<?= $row['id'] ?>">View more</a>
                    </li>
            <?php
                }
            } ?>

        </ul>
        <i id="right" class="fa-solid fas fa-angle-right"></i>
    </div>
    <h1 style="text-align: right;">Nutritionists:</h1>
    <div class="wrapper">
        <i id="left" class="fa-solid  fas fa-angle-left"></i>
        <ul class="carousel">
            <?php
            $sql = "select * from nutritionists";
            $result = mysqli_query($con, $sql);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {

            ?>
                    <li class="card1">
                        <div class="img"><img src="images/user-icon.png"
                                alt="" draggable="false"> </div>
                        <h2 style=" font-weight:bold;">
                            <?= $row['username'] ?>
                        </h2>
                        <span> Years of experience:<?= $row['experience'] ?></span>

                        <a href="viewnutr.php?id=<?= $row['id'] ?>">View more</a>
                    </li>
            <?php
                }
            }

            ?>

        </ul>
        <i id="right" class="fa-solid fas fa-angle-right"></i>
    </div>
    <footer>
        <div>
            <p>riyadh, Saudi Arabia</p>
            <p>+9664239403</p>
            <a href="mailto:healthcare@mail.com">healthcare@mail.com</a>
        </div>
        <p>Designed and Developed by Asma Rashed. 2025</p>
    </footer>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const carousel = document.querySelector(".carousel");
            const arrowBtns = document.querySelectorAll(".wrapper i");
            const wrapper = document.querySelector(".wrapper");

            const firstCard = carousel.querySelector(".card1");
            const firstCardWidth = firstCard.offsetWidth;

            let isDragging = false,
                startX,
                startScrollLeft,
                timeoutId;

            const dragStart = (e) => {
                isDragging = true;
                carousel.classList.add("dragging");
                startX = e.pageX;
                startScrollLeft = carousel.scrollLeft;
            };

            const dragging = (e) => {
                if (!isDragging) return;


                const newScrollLeft = startScrollLeft - (e.pageX - startX);

                if (newScrollLeft <= 0 || newScrollLeft >=
                    carousel.scrollWidth - carousel.offsetWidth) {

                    isDragging = false;
                    return;
                }

                carousel.scrollLeft = newScrollLeft;
            };

            const dragStop = () => {
                isDragging = false;
                carousel.classList.remove("dragging");
            };

            const autoPlay = () => {

                if (window.innerWidth < 800) return;

                const totalCardWidth = carousel.scrollWidth;

                const maxScrollLeft = totalCardWidth - carousel.offsetWidth;

                if (carousel.scrollLeft >= maxScrollLeft) return;

                timeoutId = setTimeout(() =>
                    carousel.scrollLeft += firstCardWidth, 2500);
            };

            carousel.addEventListener("mousedown", dragStart);
            carousel.addEventListener("mousemove", dragging);
            document.addEventListener("mouseup", dragStop);
            wrapper.addEventListener("mouseenter", () =>
                clearTimeout(timeoutId));
            wrapper.addEventListener("mouseleave", autoPlay);

            arrowBtns.forEach(btn => {
                btn.addEventListener("click", () => {
                    carousel.scrollLeft += btn.id === "left" ?
                        -firstCardWidth : firstCardWidth;
                });
            });
        });
    </script>
</body>

</html>