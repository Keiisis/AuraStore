<?php
/**
 * AuraStore — Complete Seller Dashboard
 * Lucide SVG Icons · Glassmorphism · GSAP Animations
 */
require_once 'includes/security.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/ai_assistant.php';
initSecureSession();
setSecurityHeaders();
requireLogin();

// Big Boss check : si admin, on va vers le panneau plateforme
// Admin Redirect Removed: Admin can access dashboard if they have a store
// if ($_SESSION['role'] === 'admin') { ... }

$user = getCurrentUser();
if (!$user || !isset($user['store_id']) || !$user['store_id']) {
    header("Location: setup-store.php");
    exit();
}

try {
    $stats = getSellerStats($user['id']);
    $theme = getTheme($user['category']);
    $products = getStoreProducts($user['store_id']);
    $creditsLeft = ($user['credits_total'] ?? 50) - ($user['credits_used'] ?? 0);
    $csrf = generateCSRFToken();
} catch (Exception $e) {
    error_log("Dashboard Data Error: " . $e->getMessage());
    die("Erreur de chargement des données. Veuillez vérifier que votre base de données est à jour (Tables inexistantes ?). Details: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?php echo htmlspecialchars($user['store_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="public/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
    <script src="https://unpkg.com/lucide@latest" defer></script>
</head>

<body class="dashboard-root">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.html" class="logo">Aura<span>Store</span></a>
            <button class="sidebar-close" id="sidebarClose" aria-label="Fermer le menu">
                <i data-lucide="x" class="icon"></i>
            </button>
        </div>

        <nav class="sidebar-nav" aria-label="Navigation dashboard">
            <a href="#" class="nav-link active" data-panel="overview">
                <i data-lucide="layout-dashboard" class="icon"></i>
                <span>Vue d'ensemble</span>
            </a>
            <a href="#" class="nav-link" data-panel="products">
                <i data-lucide="package" class="icon"></i>
                <span>Produits</span>
            </a>
            <a href="#" class="nav-link" data-panel="orders">
                <i data-lucide="shopping-cart" class="icon"></i>
                <span>Commandes</span>
            </a>
            <a href="#" class="nav-link" data-panel="settings">
                <i data-lucide="settings" class="icon"></i>
                <span>Paramètres</span>
            </a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="admin.php" class="nav-link">
                    <i data-lucide="shield" class="icon"></i>
                    <span>Admin Panel</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="credits-box">
            <div class="credits-header">
                <i data-lucide="zap" class="icon credit-icon"></i>
                <span class="credits-label">Crédits IA</span>
            </div>
            <div class="credits-bar">
                <div class="credits-fill"
                    style="width: <?php echo min(100, ($creditsLeft / max(1, $user['credits_total'] ?? 50)) * 100); ?>%">
                </div>
            </div>
            <span class="credits-count"><?php echo $creditsLeft; ?> / <?php echo $user['credits_total'] ?? 50; ?>
                restants</span>
        </div>

        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
            <div class="info">
                <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="logout.php" class="logout-link">
                    <i data-lucide="log-out" class="icon"></i> Déconnexion
                </a>
            </div>
        </div>
    </aside>

    <!-- ═══ MAIN CONTENT ═══ -->
    <main class="content">

        <!-- Mobile Top Bar -->
        <div class="mobile-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Ouvrir le menu">
                <i data-lucide="menu" class="icon"></i>
            </button>
            <span class="logo">Aura<span>Store</span></span>
            <a href="store.php?s=<?php echo $user['store_slug']; ?>" target="_blank" aria-label="Voir ma boutique">
                <i data-lucide="external-link" class="icon"></i>
            </a>
        </div>

        <!-- ═══ OVERVIEW PANEL ═══ -->
        <div class="panel active" id="panel-overview">
            <header class="content-header">
                <div>
                    <h2>Bonjour, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h2>
                    <p class="subtitle">Performances de
                        <strong><?php echo htmlspecialchars($user['store_name']); ?></strong>
                    </p>
                </div>
                <div class="actions">
                    <a href="store.php?s=<?php echo $user['store_slug']; ?>" target="_blank" class="btn-outline">
                        <i data-lucide="eye" class="icon"></i> Voir ma boutique
                    </a>
                    <button class="btn-primary" onclick="showPanel('products')">
                        <i data-lucide="plus" class="icon"></i> Ajouter un produit
                    </button>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon views-icon"><i data-lucide="eye" class="icon"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Vues Totales</span>
                        <div class="stat-value"><?php echo number_format($stats['views']); ?></div>
                    </div>
                    <div class="stat-trend positive"><i data-lucide="trending-up" class="icon"></i> Actif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon tryon-icon"><i data-lucide="scan-face" class="icon"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Essayages IA</span>
                        <div class="stat-value"><?php echo number_format($stats['tryons']); ?></div>
                    </div>
                    <div class="stat-trend positive"><i data-lucide="zap" class="icon"></i> Aura v2</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon product-icon"><i data-lucide="package" class="icon"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Produits</span>
                        <div class="stat-value"><?php echo $stats['products']; ?></div>
                    </div>
                    <div class="stat-trend <?php echo $stats['products'] > 0 ? 'positive' : 'warn'; ?>">
                        <i data-lucide="<?php echo $stats['products'] > 0 ? 'check-circle' : 'alert-triangle'; ?>"
                            class="icon"></i>
                        <?php echo $stats['products'] > 0 ? 'En ligne' : 'Ajoutez-en'; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon order-icon"><i data-lucide="message-circle" class="icon"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Commandes WhatsApp</span>
                        <div class="stat-value"><?php echo number_format($stats['orders']['total'] ?? 0); ?></div>
                    </div>
                    <div class="stat-trend positive">
                        <i data-lucide="check" class="icon"></i> <?php echo ($stats['orders']['confirmed'] ?? 0); ?>
                        confirmée(s)
                    </div>
                </div>
            </section>

            <section class="charts-area">
                <div class="chart-container">
                    <h3><i data-lucide="bar-chart-3" class="icon"></i> Essayages cette semaine</h3>
                    <canvas id="performanceChart"></canvas>
                </div>
                <div class="quick-actions">
                    <h3><i data-lucide="zap" class="icon"></i> Actions rapides</h3>
                    <button class="action-btn" onclick="showPanel('products')">
                        <i data-lucide="plus-circle" class="icon"></i> Ajouter un produit
                    </button>
                    <button class="action-btn" onclick="copyLink()">
                        <i data-lucide="copy" class="icon"></i> Copier le lien boutique
                    </button>
                    <button class="action-btn" onclick="showPanel('settings')">
                        <i data-lucide="settings" class="icon"></i> Modifier mes infos
                    </button>
                </div>
            </section>
        </div>

        <!-- ═══ PRODUCTS PANEL ═══ -->
        <div class="panel" id="panel-products">
            <header class="content-header">
                <h2><i data-lucide="package" class="icon"></i> Mes Produits</h2>
                <button class="btn-primary" id="openAddModal">
                    <i data-lucide="plus" class="icon"></i> Nouveau Produit
                </button>
            </header>

            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Essayages</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsBody">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="empty-cell">
                                    <i data-lucide="package-open" class="icon empty-icon-lg"></i>
                                    <p>Aucun produit. Ajoutez votre premier article !</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><img src="<?php echo $p['image_url'] ?: 'public/img/no-image.png'; ?>"
                                            class="product-thumb" alt="<?php echo htmlspecialchars($p['name']); ?>"></td>
                                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                    <td><?php echo formatPrice($p['price']); ?></td>
                                    <td><?php echo $p['stock']; ?></td>
                                    <td><?php echo $p['total_tryons']; ?></td>
                                    <td class="action-cell">
                                        <button class="mini-btn edit" onclick="editProduct(<?php echo $p['id']; ?>)"
                                            aria-label="Modifier">
                                            <i data-lucide="pencil" class="icon"></i>
                                        </button>
                                        <button class="mini-btn delete" onclick="deleteProduct(<?php echo $p['id']; ?>)"
                                            aria-label="Supprimer">
                                            <i data-lucide="trash-2" class="icon"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══ ORDERS PANEL ═══ -->
        <div class="panel" id="panel-orders">
            <header class="content-header">
                <h2><i data-lucide="shopping-cart" class="icon"></i> Commandes WhatsApp</h2>
            </header>
            <div class="empty-state">
                <i data-lucide="smartphone" class="icon empty-icon-xl"></i>
                <h3>Les commandes arrivent via WhatsApp</h3>
                <p>Chaque fois qu'un client clique "Commander", il est redirigé vers votre WhatsApp avec les détails du
                    produit pré-remplis.</p>
            </div>
        </div>

        <!-- ═══ SETTINGS PANEL ═══ -->
        <div class="panel" id="panel-settings">
            <header class="content-header">
                <h2><i data-lucide="settings" class="icon"></i> Paramètres de la boutique</h2>
            </header>
            <form class="settings-form" method="POST" action="api/stores.php?action=update">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="sn"><i data-lucide="store" class="icon"></i> Nom de la boutique</label>
                    <input type="text" id="sn" name="store_name"
                        value="<?php echo htmlspecialchars($user['store_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="wa"><i data-lucide="phone" class="icon"></i> Numéro WhatsApp</label>
                    <input type="tel" id="wa" name="whatsapp"
                        value="<?php echo htmlspecialchars($stats['store']['whatsapp_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="cur"><i data-lucide="coins" class="icon"></i> Devise de la boutique</label>
                    <select id="cur" name="currency" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:white; padding:12px; border-radius:8px;">
                        <option value="XAF" <?php echo ($user['currency'] ?? 'XAF') === 'XAF' ? 'selected' : ''; ?>>XAF (FCFA)</option>
                        <option value="EUR" <?php echo ($user['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                        <option value="USD" <?php echo ($user['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i data-lucide="credit-card" class="icon"></i> Méthodes de paiement acceptées</label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px;">
                        <label style="font-size:0.8rem; display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="pay_stripe" checked> Stripe (Cartes)
                        </label>
                        <label style="font-size:0.8rem; display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="pay_momo" checked> Mobile Money (MTN/MOOV)
                        </label>
                        <label style="font-size:0.8rem; display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="pay_flutter" checked> Flutterwave
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label><i data-lucide="link" class="icon"></i> Lien de votre boutique</label>
                    <div class="link-preview">
                        <span id="storeUrl"><?php echo 'store.php?s=' . $user['store_slug']; ?></span>
                        <button type="button" class="copy-btn" onclick="copyLink()">
                            <i data-lucide="copy" class="icon"></i> Copier
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i data-lucide="save" class="icon"></i> Sauvegarder
                </button>
            </form>
        </div>
    </main>

    <!-- ═══ ADD PRODUCT MODAL ═══ -->
    <div class="modal-overlay" id="productModal" role="dialog" aria-label="Nouveau Produit" aria-hidden="true">
        <div class="modal">
            <div class="modal-header">
                <h3><i data-lucide="package-plus" class="icon"></i> Nouveau Produit</h3>
                <button class="close-modal" onclick="closeModal()" aria-label="Fermer">
                    <i data-lucide="x" class="icon"></i>
                </button>
            </div>
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label><i data-lucide="tag" class="icon"></i> Nom du produit</label>
                        <input type="text" name="name" required placeholder="Robe Ankara Premium" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label><i data-lucide="banknote" class="icon"></i> Prix</label>
                        <input type="number" name="price" required placeholder="15000" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i data-lucide="percent" class="icon"></i> Ancien prix (barré)</label>
                        <input type="number" name="old_price" placeholder="20000" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label><i data-lucide="boxes" class="icon"></i> Stock</label>
                        <input type="number" name="stock" value="10" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label><i data-lucide="file-text" class="icon"></i> Description</label>
                    <textarea name="description" rows="3" placeholder="Décrivez votre produit..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i data-lucide="ruler" class="icon"></i> Tailles (ex: S,M,L,XL)</label>
                        <input type="text" name="sizes" placeholder="S,M,L,XL">
                    </div>
                    <div class="form-group">
                        <label><i data-lucide="palette" class="icon"></i> Couleurs (ex: Noir,Blanc)</label>
                        <input type="text" name="colors" placeholder="Noir,Blanc,Rouge">
                    </div>
                </div>
                <div class="form-group">
                    <label><i data-lucide="images" class="icon"></i> Photos du produit (Max 3)</label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; margin-top:10px;">
                        <div class="mini-upload">
                            <label style="font-size:0.7rem; opacity:0.6; display:block; margin-bottom:5px;">Photo 1 (Principale)</label>
                            <input type="file" name="image" id="img1" accept="image/*" class="mini-file-input">
                            <div class="vto-sel"><input type="radio" name="vto_target_image" value="1" checked> VTO</div>
                        </div>
                        <div class="mini-upload">
                            <label style="font-size:0.7rem; opacity:0.6; display:block; margin-bottom:5px;">Photo 2</label>
                            <input type="file" name="image_2" id="img2" accept="image/*" class="mini-file-input">
                            <div class="vto-sel"><input type="radio" name="vto_target_image" value="2"> VTO</div>
                        </div>
                        <div class="mini-upload">
                            <label style="font-size:0.7rem; opacity:0.6; display:block; margin-bottom:5px;">Photo 3</label>
                            <input type="file" name="image_3" id="img3" accept="image/*" class="mini-file-input">
                            <div class="vto-sel"><input type="radio" name="vto_target_image" value="3"> VTO</div>
                        </div>
                    </div>
                    <style>
                        .mini-file-input { width:100%; font-size:0.6rem; background:rgba(255,255,255,0.05); padding:5px; border-radius:4px; }
                        .vto-sel { font-size:0.6rem; margin-top:5px; color:#FE7501; font-weight:700; }
                    </style>
                </div>
                <div class="form-group checkbox-group">
                    <label><input type="checkbox" name="is_featured"> <i data-lucide="star" class="icon"></i> Mettre en
                        avant (Featured)</label>
                </div>
                <button type="submit" class="btn-primary full-width">
                    <i data-lucide="rocket" class="icon"></i> Publier le produit
                </button>
            </form>
        </div>
    </div>

    <input type="hidden" id="storeSlug" value="<?php echo $user['store_slug']; ?>">

    <script src="public/js/dashboard.js"></script>
    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            // Sidebar toggle (mobile)
            const toggle = document.getElementById('sidebarToggle');
            const close = document.getElementById('sidebarClose');
            const sidebar = document.getElementById('sidebar');
            if (toggle && sidebar) {
                toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
                close?.addEventListener('click', () => sidebar.classList.remove('open'));
            }

            // File drop zone
            const drop = document.getElementById('fileDrop');
            const input = document.getElementById('fileInput');
            const preview = document.getElementById('filePreview');
            if (drop && input) {
                drop.addEventListener('click', () => input.click());
                drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.classList.add('drag-over'); });
                drop.addEventListener('dragleave', () => drop.classList.remove('drag-over'));
                drop.addEventListener('drop', (e) => {
                    e.preventDefault(); drop.classList.remove('drag-over');
                    if (e.dataTransfer.files.length) {
                        input.files = e.dataTransfer.files;
                        showPreview(e.dataTransfer.files[0]);
                    }
                });
                input.addEventListener('change', () => { if (input.files[0]) showPreview(input.files[0]); });
            }
            function showPreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview"><button type="button" class="remove-preview" onclick="removePreview()">×</button>`;
                    preview.style.display = 'block';
                    drop.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
            window.removePreview = () => {
                preview.style.display = 'none';
                drop.style.display = 'flex';
                input.value = '';
            };

            // GSAP card animations
            if (window.gsap) {
                gsap.from('.stat-card', { y: 30, opacity: 0, duration: 0.6, stagger: 0.1, ease: 'power3.out', delay: 0.2 });
                gsap.from('.chart-container, .quick-actions', { y: 20, opacity: 0, duration: 0.5, stagger: 0.15, ease: 'power2.out', delay: 0.6 });
            }
        });

        // Chart
        const weeklyData = <?php echo json_encode($stats['weekly_tryons'] ?? []); ?>;
        const labels = weeklyData.map(d => d.day ? d.day.substring(5) : '');
        const data = weeklyData.map(d => d.count || 0);

        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('performanceChart');
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels.length ? labels : ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                        datasets: [{
                            label: 'Essayages',
                            data: data.length ? data : [0, 0, 0, 0, 0, 0, 0],
                            borderColor: '#FE7501',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(254,117,1,0.08)',
                            pointBackgroundColor: '#FFE946',
                            pointBorderColor: '#FE7501',
                            pointRadius: 5,
                            borderWidth: 2.5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                                        pointBackgroundColor: '#FFE946',
                                        pointBorderColor: '#FE7501',
                                        pointRadius: 5,
                                        borderWidth: 2.5
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: { display: false },
                                        x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.4)', font: { family: 'DM Sans' } } }
                                    }
                                }
                            });
                        } catch(e) {
                            console.error(e);
                        }
                    }
                });
    </script>
    <?php renderAuraAssistant('seller'); ?>
</body>

</html>