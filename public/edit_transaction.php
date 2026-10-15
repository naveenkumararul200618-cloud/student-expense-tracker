<?php

session_start();

if (!isset($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$id = $_GET["id"];

$result = $conn->query(
    "SELECT * FROM transactions WHERE id = $id"
);

$transaction = $result->fetch_assoc();


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $type = $_POST["type"];
    $category = $_POST["category"];
    $amount = $_POST["amount"];
    $description = $_POST["description"];
    $date = $_POST["transaction_date"];

    $sql = "UPDATE transactions
            SET type=?,
                category=?,
                amount=?,
                description=?,
                transaction_date=?
            WHERE id=?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssdssi",
        $type,
        $category,
        $amount,
        $description,
        $date,
        $id
    );

    $stmt->execute();

    header("Location: report.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Transaction</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="form-container">

    <div class="form-box">

        <p class="small-title">
            UPDATE RECORD
        </p>

        <h1>Edit Transaction</h1>

        <form method="POST">

            <label>Transaction Type</label>

            <select name="type" required>

                <option value="income"
                    <?php
                    if ($transaction["type"] == "income")
                        echo "selected";
                    ?>>

                    Income

                </option>

                <option value="expense"
                    <?php
                    if ($transaction["type"] == "expense")
                        echo "selected";
                    ?>>

                    Expense

                </option>

            </select>


            <label>Category</label>

            <input
                type="text"
                name="category"
                value="<?php
                echo htmlspecialchars(
                    $transaction["category"]
                );
                ?>"
                required
            >


            <label>Amount</label>

            <input
                type="number"
                step="0.01"
                name="amount"
                value="<?php
                echo $transaction["amount"];
                ?>"
                required
            >


            <label>Description</label>

            <textarea
                name="description"
                required><?php
                echo htmlspecialchars(
                    $transaction["description"]
                );
                ?></textarea>


            <label>Date</label>

            <input
                type="date"
                name="transaction_date"
                value="<?php
                echo $transaction["transaction_date"];
                ?>"
                required
            >


            <button type="submit"
                    class="primary-btn full-btn">

                Update Transaction

            </button>

        </form>

        <a href="report.php"
           class="back-home">

            ← Back to Transactions

        </a>

    </div>

</div>

</body>
</html>