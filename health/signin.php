<?php
session_start();
require_once 'config.php';

// Already logged in? Nothing to do here.
if (!empty($_SESSION['email'])) {
    header('Location: profile.php');
    exit;
}

// ---------------------------------------------------------------
// One description for each type of account.
//   table   : where the account is stored (fixed list, safe to put in SQL)
//   form    : which tab is shown on the page
//   fields  : database column => [label, input type, required kind]
//   session : value stored in $_SESSION['user_type']
// The order of the fields is also the order of the INSERT columns.
// ---------------------------------------------------------------
$roles = [
    'sign_user' => [
        'table' => 'users', 'form' => 'form1', 'title' => 'User',
        'fields' => [
            'name'          => ['name:', 'text', 'name'],
            'email'         => ['Email Address:', 'email', 'email'],
            'phone'         => ['phone number:', 'text', 'phone'],
            'gender'        => ['gender:', 'radio', 'gender'],
            'weight'        => ['weight:', 'number', 'positive'],
            'height'        => ['height:', 'number', 'positive'],
            'date_of_birth' => ['date of birth:', 'date', 'date'],
            'goal'          => ['Purpose (weight loss, muscle building, etc.):', 'textarea', 'goal'],
        ],
    ],
    'sign_coach' => [
        'table' => 'coaches', 'form' => 'form2', 'title' => 'trainer',
        'fields' => [
            'username'       => ['Name:', 'text', 'name'],
            'email'          => ['Email Address:', 'email', 'email'],
            'phone'          => ['phone number:', 'text', 'phone'],
            'dob'            => ['date of birth:', 'date', 'date'],
            'gender'         => ['gender:', 'radio', 'gender'],
            'experience'     => ['Years of experience:', 'number', 'years'],
            'certifications' => ['Certificates:', 'textarea', 'text'],
            'hourly_rate'    => ['Subscription fee:', 'text', 'fee'],
        ],
    ],
    'sign_nutr' => [
        'table' => 'nutritionists', 'form' => 'form3', 'title' => 'Nutritionist',
        'fields' => [
            'username'          => ['Name:', 'text', 'name'],
            'email'             => ['Email Address:', 'email', 'email'],
            'phone'             => ['phone number:', 'text', 'phone'],
            'dob'               => ['date of birth:', 'date', 'date'],
            'gender'            => ['gender:', 'radio', 'gender'],
            'experience'        => ['Years of experience:', 'number', 'years'],
            'consultation_rate' => ['Subscription fee:', 'text', 'fee'],
            'certifications'    => ['Certificates:', 'textarea', 'text'],
        ],
    ],
];

/** Check one value. Returns an error message, or '' when the value is fine. */
function check_field(string $kind, string $value): string
{
    switch ($kind) {
        case 'name':
            return (mb_strlen($value) >= 2 && mb_strlen($value) <= 100) ? '' : 'Name must be 2 to 100 characters.';
        case 'email':
            return (filter_var($value, FILTER_VALIDATE_EMAIL) && strlen($value) <= 255) ? '' : 'Please enter a valid email address.';
        case 'phone':
            return preg_match('/^\+?[0-9]{9,14}$/', $value) ? '' : 'Phone number must contain 9 to 14 digits.';
        case 'gender':
            return in_array($value, ['male', 'female'], true) ? '' : 'Please choose a gender.';
        case 'positive':
            return (is_numeric($value) && $value > 0 && $value < 1000) ? '' : 'Weight and height must be positive numbers.';
        case 'date':
            $d = DateTime::createFromFormat('Y-m-d', $value);
            $valid = $d && $d->format('Y-m-d') === $value && $d <= new DateTime('today') && $d >= new DateTime('1900-01-01');
            return $valid ? '' : 'Please enter a valid date of birth.';
        case 'years':
            return (ctype_digit($value) && (int) $value <= 60) ? '' : 'Years of experience must be a whole number from 0 to 60.';
        case 'fee':
            return (is_numeric($value) && $value >= 0 && $value < 100000) ? '' : 'Subscription fee must be a number.';
        case 'goal':
            return ($value !== '' && mb_strlen($value) <= 255) ? '' : 'Purpose is required (max 255 characters).';
        case 'text':
            return ($value !== '' && mb_strlen($value) <= 2000) ? '' : 'Certificates are required.';
    }
    return '';
}

/** Is this email already used by any kind of account? */
function email_taken(mysqli $con, string $email): bool
{
    foreach (['users', 'coaches', 'nutritionists'] as $table) {
        $stmt = mysqli_prepare($con, "SELECT 1 FROM $table WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $found = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);
        if ($found) {
            return true;
        }
    }
    return false;
}

// ---------------------------------------------------------------
// Handle the submitted form
// ---------------------------------------------------------------
$errors      = [];
$active_form = 'form1';
$old         = [];            // values typed by the visitor (so they do not have to retype)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($roles as $button => $role) {
        if (!isset($_POST[$button])) {
            continue;
        }
        $active_form = $role['form'];

        if (!csrf_valid()) {
            http_response_code(400);
            exit('Invalid request. Please go back, refresh the page and try again.');
        }

        // Read every field as a plain trimmed string.
        $data = [];
        foreach ($role['fields'] as $column => [$label, $type, $kind]) {
            $raw = $_POST[$column] ?? '';
            $data[$column] = is_string($raw) ? trim($raw) : '';
        }
        $password = (isset($_POST['password']) && is_string($_POST['password'])) ? $_POST['password'] : '';
        $old = $data;

        // Validate
        foreach ($role['fields'] as $column => [$label, $type, $kind]) {
            $message = check_field($kind, $data[$column]);
            if ($message !== '') {
                $errors[$message] = true;          // keys = no duplicate messages
            }
        }
        if (strlen($password) < 8) {
            $errors['Password must be at least 8 characters.'] = true;
        }
        if (!$errors && email_taken($con, $data['email'])) {
            $errors['This email is already registered. Please log in instead.'] = true;
        }

        // Save
        if (!$errors) {
            $table   = $role['table'];
            $columns = array_keys($role['fields']);
            $columns[] = 'password';
            $values  = array_values($data);
            $values[] = password_hash($password, PASSWORD_BCRYPT);

            $marks = implode(', ', array_fill(0, count($columns), '?'));
            $stmt  = mysqli_prepare($con, "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($marks)");
            mysqli_stmt_bind_param($stmt, str_repeat('s', count($values)), ...$values);

            try {
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Log the new account in.
                session_regenerate_id(true);
                $_SESSION['email']     = $data['email'];
                $_SESSION['name']      = $data['name'] ?? $data['username'];
                $_SESSION['user_type'] = $table;

                header('Location: index.php');
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log('Sign-up failed: ' . $e->getMessage());
                $errors['Something went wrong. Please try again.'] = true;
            }
        }
        break;
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
            <a href="login.php">login</a>
        </div>
    </nav>
    <header>
        <h1>Sign up</h1>
    </header>
    <h1>Create your account on our website to enjoy all the features</h1>

    <?php if ($errors): ?>
        <div class="center" style="color:#c0392b;">
            <?php foreach (array_keys($errors) as $message): ?>
                <p><?= e($message) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="options">
        <?php foreach ($roles as $role): ?>
            <div class="option" onclick="showForm('<?= e($role['form']) ?>')"><?= e($role['title']) ?></div>
        <?php endforeach; ?>
    </div>

    <?php foreach ($roles as $button => $role): ?>
        <?php $prefix = $role['form']; /* makes every id unique, e.g. form2_email */ ?>
        <div id="<?= e($role['form']) ?>" class="form-container" style="display: <?= $role['form'] === $active_form ? 'block' : 'none' ?>;">
            <h3 class="center"><?= e($role['title']) ?></h3>
            <form action="signin.php" method="post" class="form">
                <?= csrf_field() ?>

                <?php foreach ($role['fields'] as $column => [$label, $type, $kind]): ?>
                    <?php
                    $id  = $prefix . '_' . $column;
                    $val = ($role['form'] === $active_form) ? ($old[$column] ?? '') : '';
                    ?>
                    <?php if ($type === 'radio'): ?>
                        <div style="display: flex;">
                            <label><?= e($label) ?></label>
                            <input type="radio" id="<?= e($id) ?>_male" name="<?= e($column) ?>" value="male" <?= $val === 'male' ? 'checked' : '' ?>>
                            <label for="<?= e($id) ?>_male">male</label>
                            <input type="radio" id="<?= e($id) ?>_female" name="<?= e($column) ?>" value="female" <?= $val === 'female' ? 'checked' : '' ?>>
                            <label for="<?= e($id) ?>_female">female</label>
                        </div>
                    <?php elseif ($type === 'textarea'): ?>
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <textarea id="<?= e($id) ?>" name="<?= e($column) ?>" maxlength="2000" cols="30" rows="10"><?= e($val) ?></textarea>
                    <?php else: ?>
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <input type="<?= e($type) ?>" id="<?= e($id) ?>" name="<?= e($column) ?>" value="<?= e($val) ?>" <?= $type === 'number' ? 'min="0" step="any"' : '' ?>>
                    <?php endif; ?>
                <?php endforeach; ?>

                <label for="<?= e($prefix) ?>_password">Password (at least 8 characters):</label>
                <input type="password" id="<?= e($prefix) ?>_password" name="password" minlength="8">

                <button type="submit" name="<?= e($button) ?>">Sign</button>
            </form>
        </div>
    <?php endforeach; ?>

    <p class="center">you already have account? <a href="login.php">login</a></p>
    <script>
        function showForm(formId) {
            // Hide all forms
            document.querySelectorAll('.form-container').forEach(form => form.style.display = 'none');
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
