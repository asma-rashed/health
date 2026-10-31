<?php
session_start();
require_once 'config.php';

require_login();                         // everybody who is not logged in goes to login.php
$my_email = $_SESSION['email'];
$my_type  = $_SESSION['user_type'];      // 'users', 'coaches' or 'nutritionists'

/** Run a prepared SELECT and return all rows. */
function fetch_all_rows(mysqli $con, string $sql, string $types, array $params): array
{
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/** Run a prepared SELECT and return the first row (or null). */
function fetch_one_row(mysqli $con, string $sql, string $types, array $params): ?array
{
    $rows = fetch_all_rows($con, $sql, $types, $params);
    return $rows[0] ?? null;
}

function age_from(?string $birth_date)
{
    if (empty($birth_date)) {
        return '-';
    }
    return (new DateTime())->diff(new DateTime($birth_date))->y;
}

function gender_ar(?string $gender): string
{
    return ['male' => 'ذكر', 'female' => 'أنثى'][$gender ?? ''] ?? '';
}

// ---------------------------------------------------------------
// 1) Load the logged-in account fresh from the database

// ---------------------------------------------------------------
$tables = ['users', 'coaches', 'nutritionists'];
if (!in_array($my_type, $tables, true)) {
    header('Location: logout.php');
    exit;
}
$me = fetch_one_row($con, "SELECT * FROM $my_type WHERE email = ?", 's', [$my_email]);
if (!$me) {
    header('Location: logout.php');
    exit;
}

// ---------------------------------------------------------------
// 2) A normal user's coach and nutritionist (from the subscription tables)
// ---------------------------------------------------------------
$coach = $nutr = null;
if ($my_type === 'users') {
    $coach = fetch_one_row(
        $con,
        'SELECT c.* FROM coaches c JOIN coach_subscription s ON s.coach_email = c.email WHERE s.user_email = ? LIMIT 1',
        's',
        [$my_email]
    );
    $nutr = fetch_one_row(
        $con,
        'SELECT n.* FROM nutritionists n JOIN nutr_subscription s ON s.nutr_email = n.email WHERE s.user_email = ? LIMIT 1',
        's',
        [$my_email]
    );
}

// ---------------------------------------------------------------
// 3) Send a chat message (users only)
//    The receiver is found on the server from the subscription, not from the form.
// ---------------------------------------------------------------
$msg_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $my_type === 'users') {

    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid request. Please go back, refresh the page and try again.');
    }

    $to      = $_POST['to'] ?? '';
    $message = (isset($_POST['message']) && is_string($_POST['message'])) ? trim($_POST['message']) : '';
    $receiver = ($to === 'coach' && $coach) ? $coach['email'] : (($to === 'nutr' && $nutr) ? $nutr['email'] : null);

    if (!$receiver) {
        $msg_error = 'You are not subscribed to this specialist.';
    } elseif ($message === '') {
        $msg_error = 'Message cannot be empty.';
    } elseif (mb_strlen($message) > 255) {              // messages.message is varchar(255)
        $msg_error = 'Message is too long (max 255 characters).';
    } else {
        $role   = role_label($my_type);
        $time   = date('Y-m-d H:i:s');
        $unread = 0;
        $stmt = mysqli_prepare(
            $con,
            'INSERT INTO messages (sender_email, receiver_email, role, message, time, is_read) VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'sssssi', $my_email, $receiver, $role, $message, $time, $unread);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: profile.php#redirect');
        exit;
    }
}

// ---------------------------------------------------------------
// 4) Other data for the page
// ---------------------------------------------------------------
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

function load_chat(mysqli $con, string $a, string $b): array
{
    return fetch_all_rows(
        $con,
        'SELECT sender_email, message, time FROM messages
         WHERE (sender_email = ? AND receiver_email = ?) OR (sender_email = ? AND receiver_email = ?)
         ORDER BY time ASC, id ASC',
        'ssss',
        [$a, $b, $b, $a]
    );
}

$diet = $exercise = null;
$coach_chat = $nutr_chat = [];
$subscribers = [];

if ($my_type === 'users') {
    $diet     = fetch_one_row($con, 'SELECT * FROM diet WHERE user_email = ? LIMIT 1', 's', [$my_email]);
    $exercise = fetch_one_row($con, 'SELECT * FROM exercises WHERE user_email = ? LIMIT 1', 's', [$my_email]);
    $coach_chat = $coach ? load_chat($con, $my_email, $coach['email']) : [];
    $nutr_chat  = $nutr ? load_chat($con, $my_email, $nutr['email']) : [];
} elseif ($my_type === 'coaches') {
    $subscribers = fetch_all_rows(
        $con,
        'SELECT DISTINCT u.id, u.name FROM users u JOIN coach_subscription s ON s.user_email = u.email WHERE s.coach_email = ?',
        's',
        [$my_email]
    );
} else {
    $subscribers = fetch_all_rows(
        $con,
        'SELECT DISTINCT u.id, u.name FROM users u JOIN nutr_subscription s ON s.user_email = u.email WHERE s.nutr_email = ?',
        's',
        [$my_email]
    );
}

// ---------------------------------------------------------------
// Small output helpers (they only print, and always escape)
// ---------------------------------------------------------------
function render_provider_card(?array $p, string $empty_text): void
{
    if (!$p) {
        echo "<p style='color: green; font-weight: bold; text-align: center;'>" . e($empty_text) . '</p>';
        return;
    } ?>
    <img src="images/user-icon.png" alt="" width="100">
    <h2 class="card-title"><?= e($p['username']) ?></h2>
    <div style="display: flex ; justify-content:space-evenly">
        <p class="card-text">age: <?= e(age_from($p['dob'])) ?> </p>
        <p class="card-text">gender: <?= e(gender_ar($p['gender'])) ?></p>
    </div>
    <p class="card-text">Years of experience: <?= e($p['experience']) ?> years</p>
    <p class="card-text">Certificates: <?= e($p['certifications']) ?></p>
<?php
}

function render_chat(array $messages, string $my_email, string $to, bool $can_send, string $error): void
{ ?>
    <div class="messages">
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

        <?php if ($can_send): ?>
            <div class="send_message">
                <?php if ($error !== '' && ($_POST['to'] ?? '') === $to): ?>
                    <p style="color:#c0392b;"><?= e($error) ?></p>
                <?php endif; ?>
                <form action="profile.php" method="post">
                    <?= csrf_field() ?>
                    <input type="text" name="message" maxlength="255" required>
                    <input type="hidden" name="to" value="<?= e($to) ?>">
                    <button type="submit" name="send_message" style="border-radius: none;">
                        <i class="fa fa-send" style="font-size:100%"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <p class="center">Subscribe first to start a chat.</p>
        <?php endif; ?>
    </div>
<?php
}

function render_plan(string $heading, string $col_day, string $col_plan, ?array $plan, array $days, string $empty_cell, string $none_text): void
{ ?>
    <div class="diet-table-container">
        <h1><?= e($heading) ?></h1>
        <table class="diet-table">
            <thead>
                <tr>
                    <th><?= e($col_day) ?></th>
                    <th><?= e($col_plan) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($plan): ?>
                    <?php foreach ($days as $day): ?>
                        <tr>
                            <td><?= e($day) ?></td>
                            <td><?= e(($plan[$day] ?? '') !== '' ? $plan[$day] : $empty_cell) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2"><?= e($none_text) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Profile Page</title>
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

    <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
        <script>alert('successfully subscribed!');</script>
    <?php endif; ?>

    <div class="profile-container">
        <h2 class="center">profile page</h2>

        <div id="profile-info">

            <?php if ($my_type === 'users'): ?>
                <div class="center">
                    <p>name: <?= e($me['name']) ?></p>
                    <p>email: <?= e($me['email']) ?></p>
                    <p>phone number: <?= e($me['phone']) ?></p>
                    <p>gender: <?= e(gender_ar($me['gender'])) ?></p>
                    <p>weight: <?= e($me['weight']) ?> kg</p>
                    <p>height: <?= e($me['height']) ?> cm</p>
                    <p>date of birth: <?= e($me['date_of_birth']) ?></p>
                    <p>purpose: <?= e($me['goal']) ?></p>
                </div>
                <hr>

                <div style="display: flex; justify-content:space-evenly;">
                    <div style="border: 1px solid black; padding:4px; text-align:center; border-radius:4px">
                        <h1>Your Trainer</h1>
                        <?php render_provider_card($coach, 'You do not have a subscription with any coach.'); ?>
                    </div>
                    <div>
                        <div style="border: 1px solid black; padding:4px;text-align:center; border-radius:4px">
                            <h1>Your nutritionist</h1>
                            <?php render_provider_card($nutr, 'You do not have a subscription with any nutritionist.'); ?>
                        </div>
                    </div>
                </div>
                <hr>

                <h2 class="center" id="redirect">Contact your trainer or nutritionist</h2>
                <div style="display: flex; width:100%;">
                    <div id="form1" class="form-container" style="width: 50%;">
                        <h2 class="center">Contact your trainer</h2>
                        <?php render_chat($coach_chat, $my_email, 'coach', (bool) $coach, $msg_error); ?>
                    </div>
                    <div id="form2" class="form-container" style="width: 50%;">
                        <h2 class="center">Contact your nutritionist</h2>
                        <?php render_chat($nutr_chat, $my_email, 'nutr', (bool) $nutr, $msg_error); ?>
                    </div>
                </div>

                <?php render_plan('Weekly diet plan', 'the day', 'Diet plan', $diet, $days, 'there is no diet plan', 'No diet plan added yet.'); ?>
                <?php render_plan('Weekly exercises plan', 'Day', 'Exercise', $exercise, $days, 'there is no exercises plan', 'No exercise plan added yet.'); ?>

            <?php else: /* coach or nutritionist */ ?>
                <div class="center">
                    <p>name: <?= e($me['username']) ?></p>
                    <p>email: <a href="mailto:<?= e($me['email']) ?>"><?= e($me['email']) ?></a></p>
                    <p>phone number: <a href="tel:+966<?= e($me['phone']) ?>"><?= e($me['phone']) ?></a></p>
                    <p>gender: <?= e(gender_ar($me['gender'])) ?></p>
                    <p>date of birth: <?= e($me['dob']) ?></p>
                    <p>Years of experience: <?= e($me['experience']) ?> years</p>
                    <p>Certificates: <?= e($me['certifications']) ?></p>
                    <p>subscription fees: <?= e($my_type === 'coaches' ? $me['hourly_rate'] : $me['consultation_rate']) ?> Saudi Riyal</p>
                </div>
                <hr>
                <h1>Subscribers:</h1>
                <div style="display: flex; flex-wrap:wrap; justify-content:space-evenly; margin:8px">
                    <?php if (!$subscribers): ?>
                        <p> No subscribers </p>
                    <?php endif; ?>
                    <?php foreach ($subscribers as $s): ?>
                        <div style="border:1px solid black; padding:5px; text-align:center; width:30%;">
                            <img src="images/user-icon.png" width="200" alt="">
                            <h2><?= e($s['name']) ?></h2>
                            <a href="viewuser.php?id=<?= (int) $s['id'] ?>">view</a>
                        </div>
                    <?php endforeach; ?>
                </div>
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
