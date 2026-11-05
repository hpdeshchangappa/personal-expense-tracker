<?php

session_start();

require_once "config/db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Please enter both email and password.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                // Prevent session fixation after successful login.
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];

                header("Location: dashboard.php");
                exit();

            } else {

                $message = "Invalid email or password.";
                $message_type = "error";
            }

        } else {

            $message = "Invalid email or password.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Expense Tracker</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <div class="brand">
            <h1>💰 Expense Tracker</h1>
            <p>Take control of your finances.</p>
        </div>

        <h2>Welcome Back</h2>

        <?php if (!empty($message)): ?>

            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">

                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>

            <button type="submit" class="btn-primary">
                Login
            </button>

        </form>

        <p class="auth-link">
            Don't have an account?
            <a href="register.php">Create an account</a>
        </p>

    </div>

</div>

</body>

</html>