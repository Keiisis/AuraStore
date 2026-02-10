<?php
/**
 * AuraStore — Store Setup Wizard
 * Full-height scrollable form · Lucide Icons · CSRF Protected
 */
require_once 'includes/security.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
initSecureSession();
setSecurityHeaders();
requireLogin();

$user = getCurrentUser();
if ($user['store_id']) {
    header("Location: dashboard.php");
    exit();
}

$categories = getThemes();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    rateLimit('setup_store', 5, 300);

    $storeName = trim($_POST['store_name'] ?? '');
    $category = $_POST['category'] ?? 'streetwear';
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $currency = $_POST['currency'] ?? 'XAF';

    if (empty($storeName) || empty($whatsapp)) {
        $error = 'Le nom et le numéro WhatsApp sont obligatoires.';
    } elseif (!preg_match('/^\+?[0-9\s\-]{8,20}$/', $whatsapp)) {
        $error = 'Numéro WhatsApp invalide.';
    } else {
        $slug = slugify($storeName);
        $db = getDB();

        $logoUrl = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $validation = validateFileUpload($_FILES['logo']);
            if (isset($validation['error'])) {
                $error = $validation['error'];
            } else {
                $upload = uploadImage($_FILES['logo'], 'logos');
                if (isset($upload['url'])) $logoUrl = $upload['url'];
            }
        }

        if (empty($error)) {
            $stmt = $db->prepare("INSERT INTO stores (user_id, store_name, store_slug, category, description, logo_url, whatsapp_number, currency) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['id'],
                sanitizeInput($storeName),
                $slug,
                sanitizeInput($category),
                sanitizeInput($description),
                $logoUrl,
                sanitizeInput($whatsapp),
                sanitizeInput($currency)
            ]);
            header("Location: dashboard.php");
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
    <title>Configurez votre boutique | AuraStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <style>
        :root {
            --yellow: #FFE946;
            --orange: #FE7501;
            --red: #B4160B;
            --green: #00FF94;
            --white: #FAFAFA;
            --white-60: rgba(255,255,255,0.6);
            --white-40: rgba(255,255,255,0.4);
            --white-20: rgba(255,255,255,0.2);
            --white-10: rgba(255,255,255,0.1);
            --white-05: rgba(255,255,255,0.05);
            --surface: #050505;
            --surface-l: #0D0D0D;
            --font-display: 'Sora', sans-serif;
            --font-body: 'DM Sans', sans-serif;
            --radius: 16px;
            --radius-sm: 12px;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background: #050505;
            background: 
                radial-gradient(circle at 10% 10%, rgba(0, 209, 255, 0.12) 0%, transparent 35%),
                radial-gradient(ellipse at 85% 95%, #FE7501 0%, #CA4300 18%, #B4160B 32%, #5a1208 48%, #1a0804 65%, #050505 82%);
            background-attachment: fixed;
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 20px 80px;
            overflow-x: hidden;
        }

        /* Setup Card */
        .setup-wrapper {
            position: relative; z-index: 1;
            width: 100%; max-width: 600px;
        }

        .setup-card {
            background: rgba(15, 12, 10, 0.75);
            backdrop-filter: blur(24px) saturate(1.3);
            -webkit-backdrop-filter: blur(24px) saturate(1.3);
            border: 1px solid rgba(254,117,1,0.08);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
        }

        .setup-header { text-align: center; margin-bottom: 36px; }
        .setup-logo {
            font-family: var(--font-display);
            font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;
            margin-bottom: 8px;
        }
        .setup-logo span { color: var(--orange); }
        .setup-header h2 {
            font-family: var(--font-display);
            font-size: 1.2rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .setup-header h2 .icon { color: var(--orange); }
        .setup-header p { font-size: 0.85rem; color: var(--white-40); margin-top: 6px; }

        /* Error */
        .error-msg {
            padding: 14px 18px;
            background: rgba(180,22,11,0.1);
            border: 1px solid rgba(180,22,11,0.3);
            border-radius: var(--radius-sm);
            font-size: 0.85rem; color: #FF6B6B;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 24px;
        }
        .error-msg .icon { width: 20px; height: 20px; flex-shrink: 0; }

        /* Form Groups */
        .input-group { margin-bottom: 22px; }
        .input-group label {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            color: var(--white-40);
            margin-bottom: 8px;
        }
        .input-group label .icon { width: 16px; height: 16px; color: var(--orange); }

        .input-group input[type="text"],
        .input-group input[type="tel"] {
            width: 100%; padding: 14px 16px;
            background: rgba(254,117,1,0.03);
            border: 1px solid rgba(254,117,1,0.08);
            border-radius: var(--radius-sm);
            color: var(--white);
            font-family: var(--font-body); font-size: 0.92rem;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .input-group input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(254,117,1,0.1);
            outline: none;
        }
        .input-group input::placeholder { color: rgba(255,255,255,0.18); }

        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .cat-option {
            padding: 14px;
            border: 1px solid rgba(254,117,1,0.06);
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-align: center;
            transition: border-color var(--transition), background var(--transition), transform var(--transition);
        }
        .cat-option:hover { border-color: rgba(254,117,1,0.15); transform: translateY(-1px); }
        .cat-option.selected { border-color: var(--orange); background: rgba(254,117,1,0.06); box-shadow: 0 4px 16px rgba(254,117,1,0.08); }
        .cat-option input[type="radio"] { display: none; }
        .cat-name { font-family: var(--font-display); font-weight: 700; font-size: 0.82rem; }
        .cat-vibe { font-size: 0.68rem; color: var(--white-20); margin-top: 4px; }

        /* Select */
        .currency-select {
            width: 100%; padding: 14px 16px;
            background: rgba(254,117,1,0.03);
            border: 1px solid rgba(254,117,1,0.08);
            border-radius: var(--radius-sm);
            color: var(--white);
            font-family: var(--font-body); font-size: 0.92rem;
            cursor: pointer;
            transition: border-color var(--transition);
        }
        .currency-select:focus { border-color: var(--orange); outline: none; }
        .currency-select option { background: #111; }

        /* Textarea */
        textarea {
            width: 100%; padding: 14px 16px;
            background: rgba(254,117,1,0.03);
            border: 1px solid rgba(254,117,1,0.08);
            border-radius: var(--radius-sm);
            color: var(--white);
            font-family: var(--font-body); font-size: 0.92rem;
            min-height: 80px; resize: vertical;
            transition: border-color var(--transition);
        }
        textarea:focus { border-color: var(--orange); outline: none; }
        textarea::placeholder { color: rgba(255,255,255,0.18); }

        /* File Upload */
        .file-upload {
            padding: 24px;
            border: 2px dashed rgba(254,117,1,0.12);
            border-radius: var(--radius-sm);
            text-align: center;
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition);
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            background: rgba(254,117,1,0.02);
        }
        .file-upload:hover { border-color: var(--orange); background: rgba(254,117,1,0.05); }
        .file-upload .icon { width: 28px; height: 28px; color: var(--white-20); }
        .file-upload p { font-size: 0.85rem; color: var(--white-40); }
        .file-upload .browse { color: var(--orange); font-weight: 600; }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--red), var(--orange));
            color: var(--white);
            font-family: var(--font-display);
            font-weight: 700; font-size: 0.95rem;
            border: none; border-radius: var(--radius-sm);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: transform var(--transition), box-shadow var(--transition);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(180,22,11,0.3);
        }
        .btn-submit .icon { width: 20px; height: 20px; }

        /* Step indicator */
        .step-indicator {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-bottom: 28px;
        }
        .step-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--white-10);
        }
        .step-dot.active { background: var(--orange); width: 24px; border-radius: 4px; }
        .step-dot.done { background: var(--green); }

        *:focus-visible { outline: 2px solid var(--orange); outline-offset: 3px; border-radius: 4px; }

        @media (max-width: 500px) {
            body { padding: 20px 14px 60px; }
            .setup-card { padding: 28px 22px; }
            .category-grid { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>

    <div class="setup-wrapper">
        <div class="setup-card">
            <div class="setup-header">
                <div class="setup-logo">Aura<span>Store</span></div>
                <h2><i data-lucide="rocket" class="icon"></i> Configurez votre boutique</h2>
                <p>Remplissez les informations pour lancer votre boutique en ligne</p>
            </div>

            <div class="step-indicator" aria-hidden="true">
                <div class="step-dot done"></div>
                <div class="step-dot active"></div>
                <div class="step-dot"></div>
            </div>

            <?php if ($error): ?>
                <div class="error-msg" role="alert">
                    <i data-lucide="alert-circle" class="icon"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" novalidate>
                <?php echo csrfField(); ?>

                <div class="input-group">
                    <label for="store_name">
                        <i data-lucide="store" class="icon"></i> Nom de votre boutique
                    </label>
                    <input type="text" id="store_name" name="store_name" required
                        placeholder="Ma Super Boutique" autocomplete="organization">
                </div>

                <div class="input-group">
                    <label><i data-lucide="layout-grid" class="icon"></i> Catégorie</label>
                    <div class="category-grid">
                        <?php foreach ($categories as $key => $cat): ?>
                            <label class="cat-option<?php echo $key === 'streetwear' ? ' selected' : ''; ?>"
                                onclick="this.querySelector('input').checked = true; document.querySelectorAll('.cat-option').forEach(c=>c.classList.remove('selected')); this.classList.add('selected');">
                                <input type="radio" name="category" value="<?php echo htmlspecialchars($key); ?>"
                                    <?php echo $key === 'streetwear' ? 'checked' : ''; ?>>
                                <div class="cat-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                                <div class="cat-vibe"><?php echo htmlspecialchars($cat['vibe']); ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="input-group">
                    <label for="whatsapp">
                        <i data-lucide="phone" class="icon"></i> Numéro WhatsApp (avec indicatif)
                    </label>
                    <input type="tel" id="whatsapp" name="whatsapp" required
                        placeholder="+237 6XX XX XX XX" autocomplete="tel">
                </div>

                <div class="input-group">
                    <label for="currency">
                        <i data-lucide="banknote" class="icon"></i> Devise
                    </label>
                    <select name="currency" id="currency" class="currency-select">
                        <option value="XAF">Franc CFA (XAF)</option>
                        <option value="EUR">Euro (EUR)</option>
                        <option value="USD">Dollar (USD)</option>
                        <option value="XOF">Franc CFA BCEAO (XOF)</option>
                        <option value="GHS">Cedi (GHS)</option>
                        <option value="NGN">Naira (NGN)</option>
                        <option value="MAD">Dirham (MAD)</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="description">
                        <i data-lucide="file-text" class="icon"></i> Description de la boutique
                    </label>
                    <textarea name="description" id="description"
                        placeholder="Décrivez votre boutique en quelques mots..."></textarea>
                </div>

                <div class="input-group">
                    <label>
                        <i data-lucide="image" class="icon"></i> Logo (optionnel)
                    </label>
                    <div class="file-upload" onclick="document.getElementById('logoFile').click();">
                        <i data-lucide="upload-cloud" class="icon"></i>
                        <p>Cliquez pour uploader votre logo ou <span class="browse">parcourez</span></p>
                        <input type="file" name="logo" id="logoFile" accept="image/jpeg,image/png,image/webp" style="display:none;">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i data-lucide="sparkles" class="icon"></i>
                    Lancer ma boutique
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Init Lucide
            if (window.lucide) lucide.createIcons();

            // GSAP entrance
            if (window.gsap) {
                gsap.from('.setup-card', { y: 40, opacity: 0, duration: 1.2, ease: 'power4.out' });
            }

            // File name preview
            const fileInput = document.getElementById('logoFile');
            const uploadZone = fileInput?.closest('.file-upload');
            if (fileInput && uploadZone) {
                fileInput.addEventListener('change', () => {
                    if (fileInput.files[0]) {
                        const p = uploadZone.querySelector('p');
                        if (p) p.textContent = '✓ ' + fileInput.files[0].name;
                    }
                });
            }
        });
    </script>
</body>
</html>