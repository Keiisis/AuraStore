<?php
/**
 * AuraStore - Seller Registration
 */
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $result = registerUser($name, $email, $password, $phone);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            header("Location: setup-store.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription | AuraStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="public/css/auth.css">
</head>

<body>
    <div class="auth-page">
        <div class="auth-wrapper" style="max-width:480px;">
            <div class="lava-blob" aria-hidden="true"></div>
            <div class="login-card glass">
                <a href="index.html" class="logo" aria-label="Retour à l'accueil">Aura<span>Store</span></a>
                <h2>Créez votre compte vendeur</h2>

                <?php if ($error): ?>
                    <div class="error-msg" role="alert">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <div class="input-group">
                        <label for="name">Nom complet <span class="required">*</span></label>
                        <input type="text" id="name" name="name" placeholder="Kevin CHACHA" required autocomplete="name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="two-col">
                        <div class="input-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="vous@exemple.com" required
                                autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="input-group">
                            <label for="phone">WhatsApp</label>
                            <input type="tel" id="phone" name="phone" placeholder="+237 6XX XXX XXX" autocomplete="tel"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Mot de passe <span class="required">*</span></label>
                        <input type="password" id="password" name="password" placeholder="Minimum 6 caractères" required
                            autocomplete="new-password" minlength="6">
                        <div class="password-strength" id="strengthBar" aria-label="Force du mot de passe">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirmer <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Retapez le mot de passe" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-login">
                        Créer mon compte
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password Strength Indicator
        const pw = document.getElementById('password');
        const fill = document.getElementById('strengthFill');
        if (pw && fill) {
            pw.addEventListener('input', () => {
                const v = pw.value;
                let s = 0;
                if (v.length >= 6) s++;
                if (v.length >= 10) s++;
                if (/[A-Z]/.test(v)) s++;
                if (/[0-9]/.test(v)) s++;
                if (/[^A-Za-z0-9]/.test(v)) s++;

                const pct = Math.min(s / 5 * 100, 100);
                fill.style.width = pct + '%';
                fill.style.background =
                    s <= 1 ? '#FF4444' :
                        s <= 2 ? '#FFA500' :
                            s <= 3 ? '#FFE946' :
                                '#00FF94';
            });
        }
    </script>
</body>

</html>