
<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    exit("Please enter your email and password.");
}

try {
    $sql = "SELECT `Username`, `ID`, `Email`, `Password`
            FROM `userlogin_db`
            WHERE `Email` = :email
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(["email" => $email]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student && $password === $student["Password"]) {
        session_regenerate_id(true);

        $_SESSION["student_name"] = $student["Username"];
        $_SESSION["student_id"] = $student["ID"];
        $_SESSION["student_email"] = $student["Email"];

        header("Location: home.html");
        exit;
    }

    echo "Invalid email or password. Please try again.";

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo "A database error occurred. Please check your database settings.";
}
?>
