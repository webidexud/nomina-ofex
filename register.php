<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sign Up Form by Colorlib</title>

    <!-- Font Icon -->
    <link rel="stylesheet" href="fonts/material-icon/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">

    <!-- Main css -->
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
    .form-input textarea {
        width: 100%;
        padding: 6px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        resize: vertical;
        min-height: 100px;
        font-family: inherit;
        font-size: inherit;
    }

    .form-input textarea:focus {
        outline: none;
        border-color: #3498db;
    }

    /* Asegúrate de que todos los inputs tengan el mismo ancho */
    .form-input input,
    .form-input textarea {
        width: 100%;
        box-sizing: border-box;
    }
    .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
    .password-container {
        position: relative;
    }
    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        /* Asegurarse que el icono esté sobre el campo de contraseña */
        z-index: 1;
        /* Añadir padding para evitar que se superponga con el texto */
        padding: 5px;
    }
    /* Ajustar el padding del campo de contraseña para el icono */
    .password-container input[type="password"],
    .password-container input[type="text"] {
        padding-right: 35px;
    }
    /* Estilo para el icono */
    .toggle-password i {
        color: #666;
    }
</style>
<body>

    <div class="main">

        <div class="container">
            <div class="signup-content">
                <div class="signup-img">
                    <img src="images/bg.png" alt="">
                    <div class="signup-img-content">
                        <h2>Registrate</h2>
                        <p>Software desarrollado por el Área de Gestión TIC</p>
                    </div>
                </div>
                <div class="signup-form">
                    <?php
                    
                    if(isset($_SESSION['error'])) {
                        echo '<p class="error-message">' . $_SESSION['error'] . '</p>';
                        unset($_SESSION['error']);
                    }
                    ?>
                    <form method="POST" class="register-form" id="register-form" action="back/register_contratista.php">
                        <div class="form-row">
                            <div class="form-group">
                                <div class="form-input">
                                    <label for="nombre" class="required">Nombres y Apellidos Completos del Constratista</label>
                                    <input type="text" name="nombre" id="nombre" required/>
                                </div>
                                <div class="form-input">
                                    <label for="cc" class="required">Número de Identificación del Constratista</label>
                                    <input type="number" name="cc" id="cc" required/>
                                </div>
                                <div class="form-input password-container">
    <label for="pswd" class="required">Contraseña</label>
    <input type="password" name="pswd" id="pswd" required/>
    <span class="toggle-password" onclick="togglePassword()">
        <i class="zmdi zmdi-eye"></i>
    </span>
</div>
                            </div>
                            <div class="form-group">
                                <div class="form-input">
                                    <label for="correo" class="required">Correo electrónico personal del contratísta</label>
                                    <input type="email" name="correo_personal" id="correo_personal" required/>
                                </div>
                                <div class="form-input">
                                    <label for="telefono" class="required">Número de Teléfono del Contratísta</label>
                                    <input type="phone" name="telefono" id="telefono" required/>
                                </div>
                            </div>
                        </div>
                        <div class="form-submit">
                            <input type="submit" value="Registrarse" class="submit" id="submit" name="register_btn" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/nouislider/nouislider.min.js"></script>
    <script src="vendor/wnumb/wNumb.js"></script>
    <script src="vendor/jquery-validation/dist/jquery.validate.min.js"></script>
    <script src="vendor/jquery-validation/dist/additional-methods.min.js"></script>
    <script src="js/main.js"></script>
<script>
function togglePassword() {
    const passwordField = document.getElementById("pswd");
    const toggleIcon = document.querySelector(".toggle-password i");
    
    if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleIcon.classList.remove("zmdi-eye");
        toggleIcon.classList.add("zmdi-eye-off");
    } else {
        passwordField.type = "password";
        toggleIcon.classList.remove("zmdi-eye-off");
        toggleIcon.classList.add("zmdi-eye");
    }
}
</script>
    <script>
    // Función para convertir el texto a mayúsculas
    function toUpperCase(input) {
        input.value = input.value.toUpperCase();
    }

    // Aplicar la función a los campos de nombre relevantes
    document.getElementById('nombre').addEventListener('input', function() {
        toUpperCase(this);
    });


    document.getElementById('correo_personal').addEventListener('input', function() {
        toUpperCase(this);
    });
    </script>
    <script>
    function togglePassword() {
        var passwordField = document.getElementById("pswd");
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
</body>
</html>
