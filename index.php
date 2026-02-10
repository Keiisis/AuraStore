<?php
/**
 * AuraStore - Premium Landing Page (Dynamic)
 */
require_once 'includes/functions.php';
require_once 'config/database.php';

$db = getDB();

// Fetch Dynamic Content (CMS)
$cmsRaw = $db->query("SELECT * FROM landing_settings")->fetchAll();
$cms = [];
foreach ($cmsRaw as $row) {
    $cms[$row['setting_key']] = $row['setting_value'];
}

// Fetch Plans
$plans = $db->query("SELECT * FROM pricing_plans WHERE is_active IS TRUE ORDER BY price_xaf ASC")->fetchAll();

// Default Fallbacks
$title = $cms['hero_title'] ?? 'Votre boutique.<br><span class="gradient-text">Propulsée par l\'IA.</span>';
$subtitle = $cms['hero_subtitle'] ?? 'Essayage virtuel intelligent. Commande WhatsApp en un clic. 10 thèmes premium adaptés à votre métier.';
$logo = $cms['site_logo_main'] ?? 'Aura<span>Store</span>';
$primary = $cms['primary_color'] ?? '#FE7501';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuraStore | Boutiques IA avec Essayage Virtuel & WhatsApp</title>
    <meta name="description"
        content="Créez votre boutique en ligne premium avec essayage virtuel IA et commande WhatsApp. 10 thèmes uniques. Lancez en 5 minutes.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;0,700;1,400&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary:
                <?php echo $primary; ?>
            ;
            --primary-rgb:
                <?php echo hexToRgb($primary); ?>
            ;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" defer></script>
    <script src="main.js" defer></script>
</head>

<body>
    <div class="noise" aria-hidden="true"></div>

    <nav class="main-nav">
        <a href="index.php" class="logo"><?php echo $logo; ?></a>
        <div class="nav-links">
            <a href="#features">Technologie</a>
            <a href="#categories">Univers</a>
            <a href="#pricing">Tarifs</a>
        </div>
        <div class="nav-cta">
            <a href="login.php" class="btn-ghost"
                style="color: white; text-decoration: none; margin-right: 20px;">Connexion</a>
            <a href="register.php" class="btn-nav-primary">Démarrer</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <div class="badge anim-fade">La plateforme #1 en Afrique</div>
            <h1 class="anim-fade"><?php echo $title; ?></h1>
            <p class="hero-subtitle anim-fade"><?php echo $subtitle; ?></p>
            <div class="hero-actions anim-fade">
                <a href="register.php" class="btn-main">Commencer gratuitement</a>
            </div>
        </div>

        <div class="hero-visual anim-fade">
            <div class="lava-orb"></div>
            <div class="vto-phone">
                <div class="phone-screen">
                    <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=400"
                        alt="VTO Preview">
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING SECTION -->
    <section class="pricing" id="pricing">
        <div class="container">
            <div class="section-eyebrow">Tarifs</div>
            <h2 class="section-title">Choissisez la puissance <span>adaptée à votre business</span></h2>

            <div class="pricing-grid">
                <?php foreach ($plans as $p):
                    $f = json_decode($p['features'], true) ?: [];
                    ?>
                    <article class="price-card glass-card <?php echo $p['is_featured'] ? 'featured' : ''; ?>">
                        <?php if ($p['is_featured']): ?>
                            <div class="popular-tag">POPULAIRE</div><?php endif; ?>
                        <div class="plan-header">
                            <span class="plan-name"><?php echo htmlspecialchars($p['name']); ?></span>
                            <div class="plan-price">
                                <?php echo $p['price_xaf'] > 0 ? number_format($p['price_xaf']) : '0'; ?> <span>XAF /
                                    mois</span></div>
                        </div>
                        <ul class="plan-features">
                            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#00FF94"
                                    stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> <?php echo $f['max_stores'] ?? 1; ?> Boutique</li>
                            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#00FF94"
                                    stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <?php echo ($f['max_products'] ?? 0) > 10000 ? 'Produits illimités' : ($f['max_products'] ?? 0) . ' Produits'; ?>
                            </li>
                            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#00FF94"
                                    stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> <?php echo $f['vto_monthly'] ?? 0; ?> Essayages IA / mois</li>
                            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#00FF94"
                                    stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> WhatsApp Checkout</li>
                        </ul>
                        <a href="<?php echo $p['cta_url'] ?? 'register.php'; ?>"
                            class="btn-plan <?php echo $p['is_featured'] ? 'btn-plan-featured' : ''; ?>">
                            <?php echo $p['cta_text'] ?? 'Commencer'; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> AuraStore. Dynamic Admin Sync Enabled.</p>
            </div>
        </div>
    </footer>
</body>

</html>