<?php
session_start();
require_once 'config.php';

// Only logged-in coaches / nutritionists may open this page.
require_login(['coaches', 'nutritionists']);
$my_email = $_SESSION['email'];       // who is logged in (comes from the session, never from the form)
$my_type  = $_SESSION['user_type'];   // 'coaches' or 'nutritionists'

// ---------------------------------------------------------------
// 1) Load the user being viewed (the id in the URL must be an integer)
// ---------------------------------------------------------------
$view_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$view_id) {
    header('Location: profile.php');
    exit;
}

$stmt = mysqli_prepare($con, 'SELECT id, name, email, gender, weight, height, date_of_birth, goal FROM users WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $view_id);
mysqli_stmt_execute($stmt);
$viewed = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$viewed) {
    http_response_code(404);
    exit('User not found.');
}
$user_email = $viewed['email'];

// A coach / nutritionist can only see users who subscribed to them.
if (!is_subscribed($con, $my_type, $my_email, $user_email)) {
    http_response_code(403);
    exit('You do not have access to this user.');
}

// ---------------------------------------------------------------
// 2) What kind of plan does this provider manage?
//    (these names come from this fixed list, never from user input)
// ---------------------------------------------------------------
$plans = [
    'coaches' => [
        'table'       => 'exercises',
        'owner'       => 'coach_email',
        'heading'     => 'Exercises Table',
        'add_heading' => 'Add Exercises plan',
        'column'      => 'exercise',
    ],
    'nutritionists' => [
        'table'       => 'diet',
        'owner'       => 'nutr_email',
        'heading'     => 'Diet Table',
        'add_heading' => 'Add the appropriate diet plan',
        'column'      => 'diet',
    ],
];
$plan_cfg = $plans[$my_type];
$days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

// ---------------------------------------------------------------
// 3) Handle form submissions
// ---------------------------------------------------------------
$msg_error  = '';
$plan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid request. Please go back, refresh the page and try again.');
    }

    // ---- Send a chat message --------------------------------------
    if (isset($_POST['send_message'])) {
        $message = (isset($_POST['message']) && is_string($_POST['message'])) ? trim($_POST['message']) : '';

        if ($message === '') {
            $msg_error = 'Message cannot be empty.';
        } elseif (mb_strlen($message) > 255) {          // messages.message is varchar(255)
            $msg_error = 'Message is too long (max 255 characters).';
        } else {
            $role   = role_label($my_type);
            $time   = date('Y-m-d H:i:s');
            $unread = 0;

            // sender = the logged-in person, receiver = the viewed user
            $stmt = mysqli_prepare(
                $con,
                'INSERT INTO messages (sender_email, receiver_email, role, message, time, is_read) VALUES (?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'sssssi', $my_email, $user_email, $role, $message, $time, $unread);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: viewuser.php?id=$view_id#redirect1");
            exit;
        }
    }

    // ---- Create or update the plan --------------------------------
    if (isset($_POST['save_plan'])) {
        $values = [];
        foreach ($days as $day) {
            $value = (isset($_POST[$day]) && is_string($_POST[$day])) ? trim($_POST[$day]) : '';
            if (mb_strlen($value) > 500) {              // exercises columns are varchar(500)
                $plan_error = 'Each day can contain at most 500 characters.';
                break;
            }
            $values[] = $value;
        }

        if ($plan_error === '') {
            $table = $plan_cfg['table'];
            $owner = $plan_cfg['owner'];

            // Does this provider already have a plan for this user?
            $stmt = mysqli_prepare($con, "SELECT id FROM $table WHERE user_email = ? AND $owner = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'ss', $user_email, $my_email);
            mysqli_stmt_execute($stmt);
            $exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
            mysqli_stmt_close($stmt);

            if ($exists) {
                // The WHERE clause uses the session email, so a provider can only edit their own plan.
                $set  = implode(', ', array_map(function ($day) { return "$day = ?"; }, $days));
                $stmt = mysqli_prepare($con, "UPDATE $table SET $set WHERE user_email = ? AND $owner = ?");
                $params = array_merge($values, [$user_email, $my_email]);
            } else {
                $cols = implode(', ', $days);
                $marks = implode(', ', array_fill(0, count($days), '?'));
                $stmt = mysqli_prepare($con, "INSERT INTO $table ($cols, user_email, $owner) VALUES ($marks, ?, ?)");
                $params = array_merge($values, [$user_email, $my_email]);
            }
            mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: viewuser.php?id=$view_id#redirect2");
            exit;
        }
    }
}

// ---------------------------------------------------------------
// 4) Data shown on the page
// ---------------------------------------------------------------

// Chat between the logged-in provider and the viewed user
$stmt = mysqli_prepare(
    $con,
    'SELECT sender_email, message, time FROM messages
     WHERE (sender_email = ? AND receiver_email = ?) OR (sender_email = ? AND receiver_email = ?)
     ORDER BY time ASC, id ASC'
);
mysqli_stmt_bind_param($stmt, 'ssss', $my_email, $user_email, $user_email, $my_email);
mysqli_stmt_execute($stmt);
$messages = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Existing plan (if any)
$stmt = mysqli_prepare($con, "SELECT * FROM {$plan_cfg['table']} WHERE user_email = ? AND {$plan_cfg['owner']} = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ss', $user_email, $my_email);
mysqli_stmt_execute($stmt);
$plan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Age and gender label for the card
$age = '-';
if (!empty($viewed['date_of_birth'])) {
    $age = (new DateTime())->diff(new DateTime($viewed['date_of_birth']))->y;
}
$gender_ar = ['male' => 'ذكر', 'female' => 'أنثى'][$viewed['gender'] ?? ''] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

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
            <a href="profile.php">profile</a>
            <a href="logout.php">logout</a>
        </div>
    </nav>

    <!-- User card -->
    <div class="card" style="width: 18rem;">
        <img class="card-img-top" src="images/user-icon.png" alt="User icon">
        <div class="card-body">
            <h5 class="card-title"><?= e($viewed['name']) ?></h5>
            <div style="display: flex ; justify-content:space-evenly">
                <p class="card-text"> age: <?= e($age) ?></p>
                <p class="card-text"> gender: <?= e($viewed['gender']) ?></p>
            </div>
            <p class="card-text">weight: <?= e($viewed['weight']) ?></p>
            <p class="card-text">height: <?= e($viewed['height']) ?></p>
            <p class="card-text">purpose: <?= e($viewed['goal']) ?></p>
        </div>
    </div>

    <!-- Chat with this user -->
    <div class="messages">
        <h2 class="center"> messages from user <?= e($viewed['name']) ?></h2>

        <div>
            <?php foreach ($messages as $row): ?>
                <?php if ($row['sender_email'] === $my_email): ?>
                    <div class="container">
                        <span class="namespan"><?= e($row['sender_email']) ?></span>
                        <p><?= e($row['message']) ?></p>
                        <span class="time-right"><?= e($row['time']) ?></span>
                    </div>
                <?php else: ?>
                    <div class="container darker">
                        <span class="right"><?= e($row['sender_email']) ?></span>
                        <p><?= e($row['message']) ?></p>
                        <span class="time-left"><?= e($row['time']) ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Message sending form -->
        <div class="send_message" id="redirect1">
            <?php if ($msg_error !== ''): ?>
                <p style="color:#c0392b;"><?= e($msg_error) ?></p>
            <?php endif; ?>
            <form action="viewuser.php?id=<?= (int) $view_id ?>" method="post">
                <?= csrf_field() ?>
                <input id="redirect" type="text" name="message" maxlength="255" required placeholder="write your message here...">
                <button type="submit" name="send_message" style="border-radius: none;">
                    <i class="fa fa-send" style="font-size:20px"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Exercise plan (coach) or diet plan (nutritionist) -->
    <div class="diet-table-container" id="redirect2">
        <h1><?= e($plan ? $plan_cfg['heading'] : $plan_cfg['add_heading']) ?></h1>

        <?php if ($plan_error !== ''): ?>
            <p style="color:#c0392b;"><?= e($plan_error) ?></p>
        <?php endif; ?>

        <form action="viewuser.php?id=<?= (int) $view_id ?>" method="post">
            <?= csrf_field() ?>
            <table class="diet-table">
                <thead>
                    <tr>
                        <th>the day</th>
                        <th><?= e($plan_cfg['column']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                        <tr>
                            <th><?= e($day) ?></th>
                            <td><textarea type="text" name="<?= e($day) ?>" maxlength="500"><?= e($plan[$day] ?? '') ?></textarea></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="button" name="save_plan"><?= $plan ? 'update' : 'save' ?></button>
        </form>
    </div>

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
