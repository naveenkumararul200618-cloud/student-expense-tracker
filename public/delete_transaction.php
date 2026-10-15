<?php

session_start();

if (!isset($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

if (isset($_GET["id"])) {

    $id = $_GET["id"];

    $stmt = $conn->prepare(
        "DELETE FROM transactions WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    $stmt->execute();
}

header("Location: report.php");
exit;

?>