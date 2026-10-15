
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/function.php';

require_login();

if (!isset($conn) || !($conn instanceof mysqli)) {
    exit('Database connection is not available. Check config/db.php.');
}

$categories = [
    'Food',
    'Travel',
    'Books',
    'Hostel',
    'Entertainment',
    'Education',
    'Other',
    'Salary'
];

$transactions = [];
$filtered = [];
$income = 0.0;
$expenses = 0.0;
$monthIncome = 0.0;
$monthExpense = 0.0;
$error = '';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$month = trim($_GET['month'] ?? date('Y-m'));

$currentMonth = date('Y-m');

try {
    // Discover the actual columns in the transactions table.
    $columns = [];
    $columnResult = $conn->query("SHOW COLUMNS FROM transactions");

    while ($column = $columnResult->fetch_assoc()) {
        $columns[] = $column['Field'];
    }

    foreach (['id', 'amount', 'category', 'type'] as $requiredColumn) {
        if (!in_array($requiredColumn, $columns, true)) {
            throw new RuntimeException(
                "The transactions table is missing the required column: "
                . $requiredColumn
            );
        }
    }

    $dateColumn = null;

    foreach (['date', 'transaction_date', 'created_at'] as $candidate) {
        if (in_array($candidate, $columns, true)) {
            $dateColumn = $candidate;
            break;
        }
    }

    if ($dateColumn === null) {
        throw new RuntimeException(
            'Add a date or transaction_date column to the transactions table.'
        );
    }

    // Resolve the logged-in user's database ID if available.
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId === null && !empty($_SESSION['email'])) {
        $userColumns = [];

        try {
            $userColumnResult = $conn->query("SHOW COLUMNS FROM users");

            while ($userColumn = $userColumnResult->fetch_assoc()) {
                $userColumns[] = $userColumn['Field'];
            }

            if (
                in_array('id', $userColumns, true) &&
                in_array('email', $userColumns, true)
            ) {
                $userQuery = $conn->prepare(
                    'SELECT id FROM users WHERE email = ? LIMIT 1'
                );
                $userQuery->bind_param('s', $_SESSION['email']);
                $userQuery->execute();

                $userRow = $userQuery->get_result()->fetch_assoc();

                if ($userRow) {
                    $userId = (int) $userRow['id'];
                    $_SESSION['user_id'] = $userId;
                }

                $userQuery->close();
            }
        } catch (Throwable $ignored) {
            // A users table may not be available in a single-user setup.
        }
    }

    $hasUserId = in_array('user_id', $columns, true);

    // Load transactions for the logged-in user.
    if ($hasUserId) {
        if ($userId === null) {
            // Do not show another user's transactions.
            $transactions = [];
        } else {
            $query = $conn->prepare(
                'SELECT * FROM transactions WHERE user_id = ?'
            );
            $query->bind_param('i', $userId);
            $query->execute();

            $result = $query->get_result();

            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }

            $query->close();
        }
    } else {
        // Suitable only for a single-user transactions table.
        $result = $conn->query('SELECT * FROM transactions');

        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }

    // Calculate totals and apply the dashboard filters.
    foreach ($transactions as $row) {
        $amount = (float) ($row['amount'] ?? 0);
        $type = strtolower((string) ($row['type'] ?? 'expense'));
        $rowDate = substr((string) ($row[$dateColumn] ?? ''), 0, 10);

        if ($type === 'income') {
            $income += $amount;
        } else {
            $expenses += $amount;
        }

        if (substr($rowDate, 0, 7) === $currentMonth) {
            if ($type === 'income') {
                $monthIncome += $amount;
            } else {
                $monthExpense += $amount;
            }
        }

        $description = (string) ($row['description'] ?? '');
        $rowCategory = (string) ($row['category'] ?? '');

        $matchesSearch = $search === ''
            || stripos($description . ' ' . $rowCategory, $search) !== false;

        $matchesCategory = $category === ''
            || $rowCategory === $category;

        $matchesMonth = $month === ''
            || substr($rowDate, 0, 7) === $month;

        if ($matchesSearch && $matchesCategory && $matchesMonth) {
            $row['_display_date'] = $rowDate;
            $filtered[] = $row;
        }
    }

    // Newest transactions first.
    usort($filtered, function ($a, $b) {
        return strcmp(
            (string) ($b['_display_date'] ?? ''),
            (string) ($a['_display_date'] ?? '')
        );
    });

} catch (Throwable $exception) {
    error_log('Dashboard error: ' . $exception->getMessage());
    $error = $exception->getMessage();
}

$balance = $income - $expenses;

$userName = $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['email']
    ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Student Expense Tracker</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #172b4d;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 5%;
            background: #102a43;
            color: white;
        }

        .brand {
            font-size: 20px;
            font-weight: bold;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 14px;
        }

        .container {
            width: min(1200px, 92%);
            margin: 32px auto;
        }

        .heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        h1 { margin: 0 0 8px; }

        .muted { color: #667085; }

        .button {
            display: inline-block;
            padding: 11px 15px;
            border: 0;
            border-radius: 8px;
            background: #087e8b;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .button.secondary { background: #e7edf4; color: #172b4d; }
        .button.danger { background: #b42318; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .summary-card {
            padding: 22px;
            border-radius: 14px;
            background: white;
            box-shadow: 0 4px 18px rgba(16, 42, 67, .06);
        }

        .summary-label {
            color: #667085;
            font-size: 12px;
            font-weight: bold;
        }

        .summary-value {
            margin-top: 12px;
            font-size: 25px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .panel {
            padding: 22px;
            border-radius: 14px;
            background: white;
            box-shadow: 0 4px 18px rgba(16, 42, 67, .06);
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        input, select {
            min-width: 130px;
            padding: 10px;
            border: 1px solid #d0d5dd;
            border-radius: 7px;
            background: white;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        th, td {
            padding: 14px 10px;
            border-bottom: 1px solid #eaecf0;
            text-align: left;
        }

        th {
            color: #667085;
            font-size: 12px;
        }

        .right { text-align: right; }
        .positive { color: #07845e; font-weight: bold; }
        .negative { color: #b42318; font-weight: bold; }

        .alert {
            padding: 14px;
            margin-bottom: 18px;
            border-radius: 8px;
            background: #fff0f0;
            color: #b42318;
            overflow-wrap: anywhere;
        }

        .empty {
            padding: 30px;
            text-align: center;
            color: #667085;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        .actions form { margin: 0; }

        @media (max-width: 850px) {
            .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 500px) {
            .summary-grid { grid-template-columns: 1fr; }
            .navbar { align-items: flex-start; flex-direction: column; }
            .navbar a { margin: 0 12px 0 0; }
            .container { margin-top: 22px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="brand">₹ Student Expense Tracker</div>

    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="add_transaction.php">Add Transaction</a>
        <a href="report.php">Reports</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<main class="container">

    <section class="heading">
        <div>
            <h1>Welcome, <?= h($userName) ?>!</h1>
            <p class="muted">Here's an overview of your finances.</p>
        </div>

        <div>
            <a class="button" href="add_transaction.php">+ Add Transaction</a>
        </div>
    </section>

    <?php if ($error !== ''): ?>
        <div class="alert">
            Dashboard error: <?= h($error) ?>
        </div>
    <?php endif; ?>

    <section class="summary-grid">
        <article class="summary-card">
            <div class="summary-label">CURRENT BALANCE</div>
            <div class="summary-value">₹<?= money($balance) ?></div>
        </article>

        <article class="summary-card">
            <div class="summary-label">TOTAL INCOME</div>
            <div class="summary-value">₹<?= money($income) ?></div>
        </article>

        <article class="summary-card">
            <div class="summary-label">TOTAL EXPENSES</div>
            <div class="summary-value">₹<?= money($expenses) ?></div>
        </article>

        <article class="summary-card">
            <div class="summary-label">THIS MONTH'S BALANCE</div>
            <div class="summary-value">
                ₹<?= money($monthIncome - $monthExpense) ?>
            </div>
        </article>
    </section>

    <section class="panel">
        <h2>Transactions</h2>
        <p class="muted">Search and filter your transaction history.</p>

        <form class="filters" method="get" action="dashboard.php">
            <input
                type="search"
                name="search"
                placeholder="Search description"
                value="<?= h($search) ?>"
            >

            <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $item): ?>
                    <option
                        value="<?= h($item) ?>"
                        <?= $category === $item ? 'selected' : '' ?>
                    >
                        <?= h($item) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input
                type="month"
                name="month"
                value="<?= h($month) ?>"
            >

            <button class="button" type="submit">Apply Filters</button>
            <a class="button secondary" href="dashboard.php">Reset</a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th class="right">Amount</th>
                        <th class="right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$filtered): ?>
                    <tr>
                        <td colspan="6" class="empty">
                            <?php if ($error !== ''): ?>
                                Fix the dashboard error above to load transactions.
                            <?php elseif (!$transactions): ?>
                                No transactions found for this account yet.
                            <?php else: ?>
                                No transactions match your filters.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filtered as $row): ?>
                        <?php
                        $type = strtolower((string) ($row['type'] ?? 'expense'));
                        $description = $row['description']
                            ?? $row['category']
                            ?? 'Transaction';

                        $rowDate = $row['_display_date'] ?? '';
                        $id = $row['id'];
                        ?>
                        <tr>
                            <td><?= h($description) ?></td>
                            <td><?= h($row['category'] ?? 'Other') ?></td>
                            <td>
                                <?= $rowDate !== ''
                                    ? h(date('d M Y', strtotime($rowDate)))
                                    : '—' ?>
                            </td>
                            <td><?= h(ucfirst($type)) ?></td>
                            <td class="right <?= $type === 'income' ? 'positive' : 'negative' ?>">
                                <?= $type === 'income' ? '+' : '−' ?>
                                ₹<?= money($row['amount'] ?? 0) ?>
                            </td>
                            <td class="right">
                                <div class="actions">
                                    <a
                                        class="button secondary"
                                        href="edit_transaction.php?id=<?= rawurlencode((string) $id) ?>"
                                    >Edit</a>

                                    <form
                                        method="post"
                                        action="delete_transaction.php"
                                        onsubmit="return confirm('Delete this transaction?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= h($id) ?>"
                                        >
                                        <?php if (function_exists('csrf_token')): ?>
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= h(csrf_token()) ?>"
                                            >
                                        <?php endif; ?>
                                        <button class="button danger" type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p class="muted">
        Showing <?= count($filtered) ?> transaction(s).
    </p>

</main>

<script src="script.js"></script>
</body>
</html>
