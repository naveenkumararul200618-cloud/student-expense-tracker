
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check whether the student is logged in.
 */
function require_login(): void
{
    if (empty($_SESSION['logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Safely display text in HTML.
 */
function h(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Format money values.
 */
function money(int|float|string $amount): string
{
    return number_format((float) $amount, 2);
}

/**
 * Get the current user's name.
 */
function current_user(): array
{
    return [
        'name' => $_SESSION['name']
            ?? $_SESSION['user_name']
            ?? $_SESSION['email']
            ?? 'Student'
    ];
}

/**
 * Create a CSRF security token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify the submitted CSRF token.
 */
function verify_csrf_token(): bool
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($submittedToken)
        && is_string($sessionToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $submittedToken);
}
