<?php
/**
 * AuraStore - Admin Panel (Platform Owner)
 */
require_once 'includes/security.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/ai_assistant.php'; // Add this
initSecureSession();
setSecurityHeaders();
requireAdmin();

$db = getDB();

// Fetch all platform stats
try {
    $totalUsers = $db->query("SELECT COUNT(*) as c FROM users WHERE role='seller'")->fetch()['c'] ?? 0;
    $totalStores = $db->query("SELECT COUNT(*) as c FROM stores")->fetch()['c'] ?? 0;
    $totalProducts = $db->query("SELECT COUNT(*) as c FROM products")->fetch()['c'] ?? 0;

    // Check if table exists (Postgres compatible)
    $totalTryons = 0;
    $checkTable = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'tryon_sessions')")->fetchColumn();
    if ($checkTable) {
        $totalTryons = $db->query("SELECT COUNT(*) as c FROM tryon_sessions")->fetch()['c'] ?? 0;
    }
} catch (Exception $e) {
    $totalUsers = $totalStores = $totalProducts = $totalTryons = 0;
}

// All sellers with their stores
try {
    $sellers = $db->query("SELECT u.id, u.full_name, u.email, u.created_at, s.store_name, s.store_slug, s.category, s.is_active, s.total_views,
                            (SELECT COUNT(*) FROM products WHERE store_id=s.id) as product_count
                            FROM users u 
                            LEFT JOIN stores s ON u.id = s.user_id 
                            WHERE u.role='seller' ORDER BY u.created_at DESC")->fetchAll();

    // Fetch AI settings
    $settingsRaw = $db->query("SELECT * FROM platform_settings")->fetchAll();
    $settings = [];
    foreach ($settingsRaw as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Fetch Landing Settings (CMS)
    $cmsRaw = $db->query("SELECT * FROM landing_settings")->fetchAll();
    $cms = [];
    foreach ($cmsRaw as $row) {
        $cms[$row['setting_key']] = $row['setting_value'];
    }

    // Fetch Pricing Plans
    $plans = $db->query("SELECT * FROM pricing_plans ORDER BY price_xaf ASC")->fetchAll();
} catch (Exception $e) {
    $sellers = $settings = $cms = $plans = [];
}

// Handle AI Configuration Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_config'])) {
    validateCSRF();
    foreach (['vto_provider', 'fal_api_key', 'hf_space_url', 'hf_token', 'groq_api_key'] as $key) {
        $val = $_POST[$key] ?? '';
        $db->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?) 
                      ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value")
            ->execute([$key, $val]);
    }
    header("Location: admin.php?success=1");
    exit();
}

// Handle Landing Page Management (CMS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cms'])) {
    validateCSRF();
    foreach (['hero_title', 'hero_subtitle', 'site_logo_main', 'primary_color'] as $key) {
        if (isset($_POST[$key])) {
            $db->prepare("UPDATE landing_settings SET setting_value = ? WHERE setting_key = ?")
                ->execute([$_POST[$key], $key]);
        }
    }
    header("Location: admin.php?success=1");
    exit();
}

// Handle Pricing Plans (Save/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    validateCSRF();
    $id = $_POST['plan_id'] ?? null;
    $name = $_POST['name'] ?? 'Plan';
    $price = $_POST['price'] ?? 0;
    $features = json_encode($_POST['features'] ?? []);
    $featured = isset($_POST['is_featured']) ? 1 : 0;

    if ($id) {
        $stmt = $db->prepare("UPDATE pricing_plans SET name=?, price_xaf=?, features=?, is_featured=? WHERE id=?");
        $stmt->execute([$name, $price, $features, $featured, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO pricing_plans (name, price_xaf, features, is_featured) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $features, $featured]);
    }
    header("Location: admin.php?success=1");
    exit();
}

// Handle Platform Payment Gates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_platform_payments'])) {
    validateCSRF();
    foreach (['stripe_public', 'stripe_secret', 'flutterwave_public', 'flutterwave_secret'] as $key) {
        $val = $_POST[$key] ?? '';
        $db->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?) 
                      ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value")
            ->execute([$key, $val]);
    }
    header("Location: admin.php?success=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | AuraStore Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-badge {
            background: linear-gradient(90deg, #B4160B, #FE7501);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 1px;
            margin-left: 10px;
        }

        .seller-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            align-items: center;
            font-size: 0.9rem;
        }

        .seller-row:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .seller-header {
            opacity: 0.4;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .status-active {
            color: #00FF94;
            font-weight: 700;
        }

        .status-inactive {
            color: #FF4D4D;
            font-weight: 700;
        }

        .category-tag {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.06);
        }

        /* Panel visibility fix */
        .panel {
            display: none;
        }

        .panel.active {
            display: block !important;
        }
    </style>
</head>

<body class="dashboard-root">

    <aside class="sidebar glass">
        <div class="logo">Aura<span>Store</span> <span class="admin-badge">ADMIN</span></div>
        <nav class="sidebar-nav">
            <a href="admin.php" class="nav-link active" data-panel="admin-overview">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="9"></rect>
                    <rect x="14" y="3" width="7" height="5"></rect>
                    <rect x="14" y="12" width="7" height="9"></rect>
                    <rect x="3" y="16" width="7" height="5"></rect>
                </svg>
                Vue Globale
            </a>
            <a href="dashboard.php" class="nav-link">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z" />
                </svg>
                Mon Dashboard
            </a>
            <a href="#" class="nav-link" data-panel="admin-sellers">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Vendeurs
            </a>
            <a href="#" class="nav-link" data-panel="admin-plans">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                Plans & Tarifs
            </a>
            <a href="#" class="nav-link" data-panel="admin-cms">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                Contenu Site (CMS)
            </a>
            <a href="#" class="nav-link" data-panel="admin-payments">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Paiements Plateforme
            </a>
            <a href="#" class="nav-link" data-panel="admin-ai">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 2a10 10 0 1 0 10 10H12V2z"></path>
                    <path d="M12 12 2.1 2.1"></path>
                    <path d="M12 12l8.9-8.9"></path>
                </svg>
                Configuration IA
            </a>
            <a href="index.html" class="nav-link">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Home AuraStore
            </a>
        </nav>
        <div class="user-profile">
            <div class="avatar" style="background: #B4160B;">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
            </div>
            <div class="info">
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <p style="font-size: 0.6rem; opacity: 0.5;">Super Administrateur</p>
                <a href="logout.php" style="color: #ff4d4d; font-size: 0.7rem; text-decoration: none;">Déconnexion</a>
            </div>
        </div>
    </aside>

    <main class="content">

        <!-- ADMIN OVERVIEW -->
        <div class="panel active" id="panel-admin-overview">
            <header class="content-header">
                <h2>🛡️ Panneau d'Administration</h2>
            </header>

            <section class="stats-grid">
                <div class="stat-card glass">
                    <span class="label">Vendeurs inscrits</span>
                    <div class="value">
                        <?php echo $totalUsers; ?>
                    </div>
                </div>
                <div class="stat-card glass">
                    <span class="label">Boutiques actives</span>
                    <div class="value">
                        <?php echo $totalStores; ?>
                    </div>
                </div>
                <div class="stat-card glass">
                    <span class="label">Produits publiés</span>
                    <div class="value">
                        <?php echo $totalProducts; ?>
                    </div>
                </div>
                <div class="stat-card glass">
                    <span class="label">Essayages IA totaux</span>
                    <div class="value">
                        <?php echo number_format($totalTryons); ?>
                    </div>
                </div>
            </section>

            <section class="charts-area" style="grid-template-columns: 1fr;">
                <div class="chart-container glass">
                    <h3>📈 Croissance de la plateforme</h3>
                    <canvas id="adminChart"></canvas>
                </div>
            </section>
        </div>

        <!-- SELLERS LIST -->
        <div class="panel" id="panel-admin-sellers">
            <header class="content-header">
                <h2>👥 Tous les Vendeurs</h2>
            </header>

            <div class="glass" style="padding: 20px; overflow-x: auto;">
                <div class="seller-row seller-header">
                    <span>Vendeur</span>
                    <span>Boutique</span>
                    <span>Catégorie</span>
                    <span>Produits</span>
                    <span>Vues</span>
                    <span>Statut</span>
                </div>

                <?php foreach ($sellers as $s): ?>
                    <div class="seller-row">
                        <div>
                            <strong>
                                <?php echo htmlspecialchars($s['full_name']); ?>
                            </strong>
                            <br><span style="opacity:0.4; font-size: 0.8rem;">
                                <?php echo $s['email']; ?>
                            </span>
                        </div>
                        <div>
                            <?php if ($s['store_name']): ?>
                                <a href="store.php?s=<?php echo $s['store_slug']; ?>" target="_blank"
                                    style="color: var(--dash-p); text-decoration: none;">
                                    <?php echo htmlspecialchars($s['store_name']); ?>
                                </a>
                            <?php else: ?>
                                <span style="opacity:0.3;">Non configurée</span>
                            <?php endif; ?>
                        </div>
                        <div><span class="category-tag">
                                <?php echo $s['category'] ?? '-'; ?>
                            </span></div>
                        <div>
                            <?php echo $s['product_count'] ?? 0; ?>
                        </div>
                        <div>
                            <?php echo number_format($s['total_views'] ?? 0); ?>
                        </div>
                        <div class="<?php echo ($s['is_active'] ?? 0) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo ($s['is_active'] ?? 0) ? '● Actif' : '○ Inactif'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($sellers)): ?>
                    <div style="text-align: center; padding: 60px; opacity: 0.3;">Aucun vendeur inscrit pour le moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        </div>
        </div>

        <!-- CMS PANEL -->
        <div class="panel" id="panel-admin-cms">
            <header class="content-header">
                <h2>🌐 Gestion du Contenu (Landing Page)</h2>
            </header>
            <div class="glass" style="padding: 30px; max-width: 800px;">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="save_cms" value="1">

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:10px; opacity:0.6;">Logo du site (HTML
                            autorisé)</label>
                        <input type="text" name="site_logo_main"
                            value="<?php echo htmlspecialchars($cms['site_logo_main'] ?? ''); ?>"
                            style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); padding:12px; border-radius:8px; color:white;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:10px; opacity:0.6;">Titre Hero</label>
                        <textarea name="hero_title" rows="3"
                            style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); padding:12px; border-radius:8px; color:white;"><?php echo htmlspecialchars($cms['hero_title'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:10px; opacity:0.6;">Sous-titre Hero</label>
                        <textarea name="hero_subtitle" rows="3"
                            style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); padding:12px; border-radius:8px; color:white;"><?php echo htmlspecialchars($cms['hero_subtitle'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom:10px; opacity:0.6;">Couleur Primaire</label>
                        <input type="color" name="primary_color"
                            value="<?php echo $cms['primary_color'] ?? '#FE7501'; ?>"
                            style="width:100px; height:40px; border:none; background:none; cursor:pointer;">
                    </div>

                    <button type="submit" class="btn-primary"
                        style="padding:15px 30px; border-radius:8px; background:#FE7501; color:white; border:none; font-weight:800; cursor:pointer;">Mettre
                        à jour l'accueil</button>
                </form>
            </div>
        </div>

        <!-- PRICING PLANS PANEL -->
        <div class="panel" id="panel-admin-plans">
            <header class="content-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2>🏷️ Plans & Offres</h2>
                <button class="btn-primary" onclick="openPlanModal()"
                    style="padding:10px 20px; font-size:0.8rem; border-radius:8px; background:#00FF94; color:#000; border:none; font-weight:800; cursor:pointer;">+
                    Nouveau Plan</button>
            </header>

            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
                <?php foreach ($plans as $p):
                    $f = json_decode($p['features'], true) ?: [];
                    ?>
                    <div class="glass"
                        style="padding:25px; border: 1px solid <?php echo $p['is_featured'] ? '#FE7501' : 'rgba(255,255,255,0.1)'; ?>;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                            <h3 style="margin:0;"><?php echo htmlspecialchars($p['name']); ?></h3>
                            <?php if ($p['is_featured']): ?>
                                <span
                                    style="font-size:0.6rem; background:#FE7501; color:white; padding:2px 8px; border-radius:4px;">POPULAIRE</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:1.5rem; font-weight:800; margin-bottom:20px;">
                            <?php echo number_format($p['price_xaf']); ?> <small style="font-size:0.8rem; opacity:0.5;">XAF
                                / mois</small>
                        </div>

                        <div style="font-size:0.8rem; opacity:0.6; margin-bottom:15px;">
                            <div>📦 Produits : <strong><?php echo $f['max_products'] ?? 0; ?></strong></div>
                            <div>🏪 Boutiques : <strong><?php echo $f['max_stores'] ?? 0; ?></strong></div>
                            <div>✨ VTO Mensuel : <strong><?php echo $f['vto_monthly'] ?? 0; ?></strong></div>
                        </div>

                        <button onclick='editPlan(<?php echo json_encode($p); ?>)'
                            style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:white; border-radius:8px; cursor:pointer;">Modifier
                            le Plan</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PLATFORM PAYMENTS PANEL -->
        <div class="panel" id="panel-admin-payments">
            <header class="content-header">
                <h2>💳 Passerelles de Paiement (Plateforme)</h2>
                <p style="opacity:0.5; font-size:0.9rem;">Configurez les clés API globales pour les abonnements des
                    vendeurs.</p>
            </header>
            <div class="glass" style="padding: 30px; max-width: 600px;">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="save_platform_payments" value="1">

                    <h3
                        style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px; color:#6772e5;">
                        Stripe (Global)</h3>
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; opacity:0.6;">Public Key
                            (Test/Live)</label>
                        <input type="text" name="stripe_public"
                            value="<?php echo htmlspecialchars($settings['stripe_public'] ?? ''); ?>"
                            placeholder="pk_test_..."
                            style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:6px; color:white;">
                    </div>
                    <div style="margin-bottom:25px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; opacity:0.6;">Secret
                            Key</label>
                        <input type="password" name="stripe_secret"
                            value="<?php echo htmlspecialchars($settings['stripe_secret'] ?? ''); ?>"
                            placeholder="sk_test_..."
                            style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:6px; color:white;">
                    </div>

                    <h3 style="border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px; color:#f5a623;">
                        Flutterwave (Afrique)</h3>
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; opacity:0.6;">Public
                            Key</label>
                        <input type="text" name="flutterwave_public"
                            value="<?php echo htmlspecialchars($settings['flutterwave_public'] ?? ''); ?>"
                            placeholder="FLWPUBK_TEST-..."
                            style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:6px; color:white;">
                    </div>
                    <div style="margin-bottom:25px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; opacity:0.6;">Secret
                            Key</label>
                        <input type="password" name="flutterwave_secret"
                            value="<?php echo htmlspecialchars($settings['flutterwave_secret'] ?? ''); ?>"
                            placeholder="FLWSECK_TEST-..."
                            style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:6px; color:white;">
                    </div>

                    <button type="submit" class="btn-primary"
                        style="width:100%; padding:15px; border-radius:8px; background:linear-gradient(45deg, #6772e5, #f5a623); color:white; border:none; font-weight:800; cursor:pointer;">Enregistrer
                        les API</button>
                </form>
            </div>
        </div>

        <!-- MODAL: PLAN EDITOR -->
        <div id="planModal" class="dashboard-modal"
            style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
            <div class="glass"
                style="padding:40px; width:100%; max-width:500px; border-radius:24px; position:relative;">
                <button onclick="closePlanModal()"
                    style="position:absolute; top:20px; right:20px; background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
                <h2 id="planModalTitle">Nouveau Plan</h2>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="save_plan" value="1">
                    <input type="hidden" name="plan_id" id="plan_id">

                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; opacity:0.6;">Nom de
                            l'offre</label>
                        <input type="text" name="name" id="plan_name" required
                            style="width:100%; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); padding:12px; border-radius:8px; color:white;">
                    </div>

                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; opacity:0.6;">Prix Mensuel
                            (XAF)</label>
                        <input type="number" name="price" id="plan_price" required
                            style="width:100%; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); padding:12px; border-radius:8px; color:white;">
                    </div>

                    <div
                        style="background:rgba(255,255,255,0.03); padding:20px; border-radius:12px; margin-bottom:20px;">
                        <h4 style="margin:0 0 15px 0; font-size:0.8rem; letter-spacing:1px; color:#FE7501;">VALEUR
                            AJOUTÉE (STACK)</h4>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:0.8rem;">
                            <div>
                                <label>Max Produits</label>
                                <input type="number" name="features[max_products]" id="f_max_products"
                                    style="width:100%; background:none; border:1px solid rgba(255,255,255,0.1); color:white; padding:5px;">
                            </div>
                            <div>
                                <label>Max Boutiques</label>
                                <input type="number" name="features[max_stores]" id="f_max_stores"
                                    style="width:100%; background:none; border:1px solid rgba(255,255,255,0.1); color:white; padding:5px;">
                            </div>
                            <div>
                                <label>VTO / mois</label>
                                <input type="number" name="features[vto_monthly]" id="f_vto_monthly"
                                    style="width:100%; background:none; border:1px solid rgba(255,255,255,0.1); color:white; padding:5px;">
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; margin-top:15px;">
                                <input type="checkbox" name="is_featured" id="plan_featured"> Mise en avant
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary"
                        style="width:100%; padding:15px; border-radius:10px; background:#FE7501; color:white; border:none; font-weight:800; cursor:pointer;">Enregistrer
                        le Plan</button>
                </form>
            </div>
        </div>

        <!-- AI CONFIGURATION PANEL -->
        <div class="panel" id="panel-admin-ai">
            <header class="content-header">
                <h2>✨ Configuration Aura Magic (VTO)</h2>
            </header>

            <div class="glass" style="padding: 40px; max-width: 600px;">
                <?php if (isset($_GET['success'])): ?>
                    <div
                        style="background: rgba(0,255,148,0.1); color: #00FF94; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid rgba(0,255,148,0.2);">
                        ✨ Configuration enregistrée avec succès !
                    </div>
                <?php endif; ?>

                <form id="aiConfigForm" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="save_ai_config" value="1">
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; margin-bottom: 15px; opacity: 0.6; font-weight: 600;">Moteur
                            d'Essayage Virtuel</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <label class="glass"
                                style="padding: 20px; cursor: pointer; border: 1px solid <?php echo $settings['vto_provider'] == 'free' ? 'rgba(254,117,1,0.5)' : 'rgba(255,255,255,0.1)'; ?>; border-radius: 16px; text-align: center;">
                                <input type="radio" name="vto_provider" value="free" <?php echo $settings['vto_provider'] == 'free' ? 'checked' : ''; ?>
                                    style="margin-bottom: 10px; accent-color: #FE7501;">
                                <div style="font-weight: 800;">Zéro Coût</div>
                                <div style="font-size: 0.7rem; opacity: 0.5;">HuggingFace (Free)</div>
                            </label>
                            <label class="glass"
                                style="padding: 20px; cursor: pointer; border: 1px solid <?php echo $settings['vto_provider'] == 'fal' ? 'rgba(254,117,1,0.5)' : 'rgba(255,255,255,0.1)'; ?>; border-radius: 16px; text-align: center;">
                                <input type="radio" name="vto_provider" value="fal" <?php echo $settings['vto_provider'] == 'fal' ? 'checked' : ''; ?>
                                    style="margin-bottom: 10px; accent-color: #FE7501;">
                                <div style="font-weight: 800; color: #FE7501;">Pro Edition</div>
                                <div style="font-size: 0.7rem; opacity: 0.5;">Fal.ai (Ultra Rapide)</div>
                            </label>
                        </div>
                    </div>

                    <div id="hfConfigSection"
                        style="display: <?php echo $settings['vto_provider'] == 'free' ? 'block' : 'none'; ?>;">
                        <label style="display: block; margin-bottom: 10px; opacity: 0.6; font-weight: 600;">URL API
                            HuggingFace (Gradio)</label>
                        <input type="text" name="hf_space_url"
                            value="<?php echo htmlspecialchars($settings['hf_space_url'] ?? 'https://yisol-idm-vton.hf.space/api/predict'); ?>"
                            placeholder="https://.../api/predict"
                            style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; color: white; margin-bottom: 15px;">

                        <label style="display: block; margin-bottom: 10px; opacity: 0.6; font-weight: 600;">Token Access
                            HuggingFace (HF_TOKEN)</label>
                        <input type="password" name="hf_token"
                            value="<?php echo htmlspecialchars($settings['hf_token'] ?? ''); ?>" placeholder="hf_..."
                            style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; color: white; margin-bottom: 5px;">
                        <p style="font-size: 0.7rem; opacity: 0.4; margin-bottom: 20px;">Optionnel, mais recommandé. <a
                                href="https://huggingface.co/settings/tokens" target="_blank"
                                style="color: #FE7501;">Obtenir un token ici</a>.</p>
                    </div>

                    <div id="falKeySection"
                        style="display: <?php echo $settings['vto_provider'] == 'fal' ? 'block' : 'none'; ?>;">
                        <label style="display: block; margin-bottom: 10px; opacity: 0.6; font-weight: 600;">Clé API
                            Fal.ai</label>
                        <input type="password" name="fal_api_key"
                            value="<?php echo htmlspecialchars($settings['fal_api_key'] ?? ''); ?>"
                            placeholder="votre_cle_ici..."
                            style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; color: white; margin-bottom: 20px;">
                    </div>

                    <div style="margin: 30px 0; padding: 25px; background: rgba(254, 117, 1, 0.05); border: 1px solid rgba(254, 117, 1, 0.1); border-radius: 15px;">
                        <h3 style="color:#FE7501; margin-bottom:15px; display:flex; align-items:center; gap:10px; font-size:1rem;">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10H12V2z"></path><path d="M12 12 2.1 2.1"></path><path d="M12 12l8.9-8.9"></path></svg>
                            Moteur Intelligence & Factures (GROQ)
                        </h3>
                        <label style="display: block; margin-bottom: 8px; opacity: 0.6; font-size: 0.8rem;">Clé API Groq Cloud</label>
                        <input type="password" name="groq_api_key" value="<?php echo htmlspecialchars($settings['groq_api_key'] ?? ''); ?>" placeholder="gsk_..." style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; color: white;">
                        <p style="font-size:0.75rem; opacity:0.4; margin-top:10px;">Indispensable pour l'Assistant Aura et les reçus personnalisés.</p>
                        
                        <div style="margin-top:20px; padding:15px; background:rgba(255,255,255,0.02); border-radius:10px; border:1px dashed rgba(255,255,255,0.1);">
                            <p style="font-size:0.7rem; margin-bottom:10px; opacity:0.6;">Testez le système de facturation intelligente :</p>
                            <a href="checkout.php?p=1&m=test_invoice" class="btn-primary" style="display:inline-block; padding:8px 15px; font-size:0.75rem; text-decoration:none; background:rgba(0,255,148,0.1); color:#00FF94; border:1px solid #00FF94; border-radius:5px;">
                                🚀 Envoyer un Reçu IA (Test)
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary"
                        style="width: 100%; padding: 18px; border-radius: 12px; font-weight: 800; border: none; background: linear-gradient(135deg, #FE7501, #B4160B); color: white; cursor: pointer;">
                        Enregistrer la configuration
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // --- 1. PRIORITY NAVIGATION ---
        function showPanel(panelName) {
            console.log("Switching to: " + panelName);
            document.querySelectorAll('.panel').forEach(p => {
                p.style.display = 'none';
                p.classList.remove('active');
            });
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

            const target = document.getElementById('panel-' + panelName);
            if (target) {
                target.style.display = 'block';
                target.classList.add('active');
                const link = document.querySelector(`.nav-link[data-panel="${panelName}"]`);
                if (link) link.classList.add('active');
            }
        }

        document.querySelectorAll('.nav-link[data-panel]').forEach(link => {
            link.onclick = (e) => {
                e.preventDefault();
                showPanel(link.dataset.panel);
            };
        });

        // Initialize
        showPanel('admin-overview');

        // --- PLAN MODAL LOGIC ---
        function openPlanModal() {
            document.getElementById('planModalTitle').innerText = "Nouveau Plan";
            document.getElementById('plan_id').value = "";
            document.getElementById('plan_name').value = "";
            document.getElementById('plan_price').value = "";
            document.getElementById('f_max_products').value = 20;
            document.getElementById('f_max_stores').value = 1;
            document.getElementById('f_vto_monthly').value = 50;
            document.getElementById('plan_featured').checked = false;
            document.getElementById('planModal').style.display = 'flex';
        }

        function closePlanModal() {
            document.getElementById('planModal').style.display = 'none';
        }

        function editPlan(plan) {
            const f = JSON.parse(plan.features || '{}');
            document.getElementById('planModalTitle').innerText = "Modifier Plan: " + plan.name;
            document.getElementById('plan_id').value = plan.id;
            document.getElementById('plan_name').value = plan.name;
            document.getElementById('plan_price').value = parseFloat(plan.price_xaf);
            document.getElementById('f_max_products').value = f.max_products || 0;
            document.getElementById('f_max_stores').value = f.max_stores || 0;
            document.getElementById('f_vto_monthly').value = f.vto_monthly || 0;
            document.getElementById('plan_featured').checked = plan.is_featured == 1;
            document.getElementById('planModal').style.display = 'flex';
        }

        // Close on overlay click
        window.onclick = function (event) {
            const modal = document.getElementById('planModal');
            if (event.target == modal) closePlanModal();
        }

        // --- 2. SECONDARY CHARTS ---
        try {
            const chartDom = document.getElementById('adminChart');
            if (chartDom) {
                new Chart(chartDom.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun'],
                        datasets: [{
                            label: 'Nouveaux vendeurs',
                            data: [<?php echo (int) $totalUsers; ?>, 0, 0, 0, 0, 0],
                            backgroundColor: 'rgba(254,117,1,0.5)',
                            borderColor: '#FE7501',
                            borderWidth: 1,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { display: false },
                            x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.4)' } }
                        }
                    }
                });
            }
        } catch (err) { console.error("Chart error", err); }

        // --- 3. AI CONFIG ---
        const aiForm = document.getElementById('aiConfigForm');
        if (aiForm) {
            const falSec = document.getElementById('falKeySection');

            document.getElementById('aiConfigForm').addEventListener('change', function (e) {
                if (e.target.name === 'vto_provider') {
                    const isFal = e.target.value === 'fal';
                    document.getElementById('falKeySection').style.display = isFal ? 'block' : 'none';
                    document.getElementById('hfConfigSection').style.display = isFal ? 'none' : 'block';
                }
            });
        }
    </script>
    <?php renderAuraAssistant('admin'); ?>
</body>

</html>