<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: ver_citas.php");
            exit();
        } else {
            $error = "Contraseña inválida.";
        }
    } else {
        $error = "No se encontró un usuario con ese nombre.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css" rel="stylesheet">
    <style>
        .login-container {
            margin-top: 50px; /* Adjust this value as needed */
        }
    </style>
</head>
<body>
<div class="container login-container">
    <div class="columns is-centered">
        <div class="column is-half">
            <h2 class="title is-2 has-text-centered">Login</h2>
            <form action="admin_citas.php" method="POST" class="box">
                <div class="field">
                    <label class="label" for="username">Nombre de usuario:</label>
                    <div class="control">
                        <input type="text" class="input" id="username" name="username" required autocomplete="off">
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="password">Contraseña:</label>
                    <div class="control">
                        <input type="password" class="input" id="password" name="password" required autocomplete="off">
                    </div>
                </div>
                <div class="field">
                    <div class="control">
                        <button type="submit" class="button is-primary is-fullwidth">Iniciar sesión</button>
                    </div>
                </div>
            </form>
            <?php if (isset($error)) { echo "<p class='has-text-danger has-text-centered'>$error</p>"; } ?>
        </div>
    </div>
</div>
</body>
</html>