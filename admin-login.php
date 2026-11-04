<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$username = trim($_POST["admin_username"] ?? "");
$password = $_POST["admin_password"] ?? "";

if ($username === "" || $password === "") {
    exit("Please enter your username and password.");
}

try {
    $sql = "SELECT `Username`, `Password`
            FROM `adminlogin_db`
            WHERE `Username` = :username
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(["username" => $username]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin["Password"]) {
        session_regenerate_id(true);

        $_SESSION["admin_username"] = $admin["Username"];

        header("Location: admin-dashboard.php");
        exit;
    }

    echo "Invalid admin username or password.";

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit("Unable to process login. Please check your admin database.");
}
?>