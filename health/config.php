<?php
/**
 * Database connection + small helpers shared by every page.
 *
 * Real credentials are NOT written in this file. They come from:
 *   1) environment variables (DB_HOST, DB_USER, DB_PASS, DB_NAME), or
 *   2) a local file called config.local.php (copy config.local.example.php).
 * config.local.php is listed in .gitignore, so it never reaches GitHub.
 */

$db = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'name' => getenv('DB_NAME') ?: 'health',
];

if (is_file(__DIR__ . '/config.local.php')) {
    $db = array_merge($db, require __DIR__ . '/config.local.php');
}

try {
    // "@" hides the PHP warning (it would print the host/user on the page).
    $con = @mysqli_connect($db['host'], $db['user'], $db['pass'], $db['name']);
} catch (mysqli_sql_exception $e) {      // PHP 8.1+ throws instead of returning false
    $con = false;
    error_log('DB connection failed: ' . $e->getMessage());
}

if (!$con) {
    http_response_code(500);
    exit('Sorry, the service is temporarily unavailable.');
}

mysqli_set_charset($con, 'utf8mb4');    // correct Arabic + emoji handling
unset($db);                              // do not keep credentials in memory


/* ---------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------- */

/** Escape a value before printing it in HTML (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** CSRF token stored in the session. Call after session_start(). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Hidden input to put inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** True when the submitted form carries the right CSRF token. */
function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/**
 * Send the visitor to login.php if they are not logged in.
 * Pass allowed types (e.g. ['coaches', 'nutritionists']) to also restrict
 * the page by role; other roles are sent to profile.php.
 */
function require_login(array $allowed_types = []): void
{
    if (empty($_SESSION['email']) || empty($_SESSION['user_type'])) {
        header('Location: login.php');
        exit;
    }
    if ($allowed_types && !in_array($_SESSION['user_type'], $allowed_types, true)) {
        header('Location: profile.php');
        exit;
    }
}

/** Convert the session user type to the values allowed by messages.role. */
function role_label(string $user_type): string
{
    $map = ['users' => 'user', 'coaches' => 'coach', 'nutritionists' => 'nutr'];
    return $map[$user_type] ?? 'user';
}

/** Has this user subscribed to this coach / nutritionist? */
function is_subscribed(mysqli $con, string $provider_type, string $provider_email, string $user_email): bool
{
    // Table and column names come from this fixed list, never from user input.
    $tables = [
        'coaches'       => ['coach_subscription', 'coach_email'],
        'nutritionists' => ['nutr_subscription',  'nutr_email'],
    ];
    if (!isset($tables[$provider_type])) {
        return false;
    }
    [$table, $column] = $tables[$provider_type];

    $stmt = mysqli_prepare($con, "SELECT 1 FROM $table WHERE $column = ? AND user_email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $provider_email, $user_email);
    mysqli_stmt_execute($stmt);
    $found = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);

    return $found;
}
