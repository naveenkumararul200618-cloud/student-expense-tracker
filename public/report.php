<?php

session_start();

if (!isset($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$result = $conn->query(
    "SELECT *
     FROM transactions
     ORDER BY transaction_date DESC, id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Transactions - Student Expense Tracker</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<nav class="navbar">

    <div class="logo">
        Student Expense Tracker
    </div>

    <div class="nav-links">

        <a href="dashboard.php">Dashboard</a>

        <a href="report.php">Transactions</a>

        <a href="login.php">Logout</a>

    </div>

</nav>


<div class="table-container">

    <div class="welcome-section">

        <div>

            <p class="small-title">
                FINANCIAL HISTORY
            </p>

            <h1>Transactions</h1>

        </div>

        <a href="add_transaction.php"
           class="primary-btn">

            + Add Transaction

        </a>

    </div>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

            <?php

            if ($result && $result->num_rows > 0) {

                while ($row = $result->fetch_assoc()) {

            ?>

                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row["transaction_date"]
                        );
                        ?>
                    </td>

                    <td>

                        <span class="type-badge
                            <?php echo $row["type"]; ?>">

                            <?php
                            echo ucfirst($row["type"]);
                            ?>

                        </span>

                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row["category"]
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row["description"]
                        );
                        ?>
                    </td>

                    <td class="amount">

                        ₹<?php
                        echo number_format(
                            $row["amount"],
                            2
                        );
                        ?>

                    </td>

                    <td>

                        <a class="edit-btn"
                           href="edit_transaction.php?id=<?php echo $row["id"]; ?>">

                            Edit

                        </a>

                        <a class="delete-btn"
                           href="delete_transaction.php?id=<?php echo $row["id"]; ?>"
                           onclick="return confirm('Are you sure you want to delete this transaction?');">

                            Delete

                        </a>

                    </td>

                </tr>

            <?php

                }

            } else {

            ?>

                <tr>

                    <td colspan="6"
                        class="no-data">

                        No transactions found.

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>