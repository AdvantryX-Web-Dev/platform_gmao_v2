<?php
// Démarrer la session avant tout output HTML (seulement si une session n'est pas déjà active)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: index.php?route=dashboard");
    exit;
}

// Récupérer les erreurs de connexion
$errors = $_SESSION['login_errors'] ?? [];
// Récupérer les anciennes valeurs
$old_values = $_SESSION['login_old_values'] ?? [];

// Effacer les messages de la session
unset($_SESSION['login_errors'], $_SESSION['login_old_values']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - GMAO System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/public/images/favicon.png" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .login-box {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 15px 0;
            text-align: center;
            width: 100%;
        }
        .login-box h1 {
            color: #0d6efd;
            margin-bottom: 20px;
            text-align: center;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-logo i {
            font-size: 60px;
            color: #0d6efd;
        }
        .input-group-addon {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }
        .form-control.input {
            padding-left: 35px;
        }
        .field-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .form-group {
            position: relative;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">GMAO System</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="login-box">
            <div class="login-logo">
                <i class="fas fa-user-lock"></i>
            </div>
            <h1>Connexion</h1>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form action="/public/index.php?route=login" method="post">
                <div class="form-group">
                    <label for="email" class="col">Adresse e-mail:</label>
                    <span class="input-group-addon ">
                        <i class="fas fa-envelope mt-4"></i>
                    </span>
                    <input type="email" class="form-control input" id="email" name="email" value="<?php echo htmlspecialchars($old_values['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="col">Mot de passe:</label>
                        <span class="input-group-addon ">
                        <i class="fas fa-lock mt-4"></i>
                    </span>
                    <input type="password" class="form-control input" id="password-field" name="password" required>
                    <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password mt-2"></span>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <p>Pas encore de compte? <a href="/public/index.php?route=register">Inscrivez-vous ici</a></p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> GMAO System. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour afficher/masquer le mot de passe -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('.toggle-password');
        const passwordField = document.querySelector('#password-field');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    });
    </script>
</body>
</html> 