<?php
/**
 * AuraStore - Premium Landing Page (Dynamic)
 */
require_once 'includes/functions.php';
require_once 'config/database.php';

$db = null;
$cms = [];
$plans = [];

try {
    $db = getDB();

    // Fetch Dynamic Content (CMS)
    $cmsRaw = $db->query("SELECT * FROM landing_settings")->fetchAll();
    foreach ($cmsRaw as $row) {
        $cms[$row['setting_key']] = $row['setting_value'];
    }

    // Fetch Plans
    $plans = $db->query("SELECT * FROM pricing_plans WHERE is_active IS TRUE ORDER BY price_xaf ASC")->fetchAll();

} catch (Exception $e) {
    // If DB is not ready or tables missing, we use defaults without crashing
    error_log("Landing Page DB Error: " . $e->getMessage());
}

// Default Fallbacks
$title = $cms['hero_title'] ?? 'Votre boutique.<br><span class="gradient-text">Propulsée par l\'IA.</span>';
$subtitle = $cms['hero_subtitle'] ?? 'Essayage virtuel intelligent. Commande WhatsApp en un clic. 10 thèmes premium adaptés à votre métier. Zéro friction pour vos clients.';
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
    <meta property="og:title" content="AuraStore — L'avenir du shopping en Afrique">
    <meta property="og:description" content="Essayage virtuel IA + Commande WhatsApp. Votre boutique en 5 minutes.">

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

    <!-- Noise Texture Overlay -->
    <div class="noise" aria-hidden="true"></div>

    <!-- ═══ NAVIGATION ═══ -->
    <nav class="main-nav" role="navigation" aria-label="Navigation principale">
        <a href="index.php" class="logo" aria-label="AuraStore — Accueil"><?php echo $logo; ?></a>
        <div class="nav-links" role="list">
            <a href="#features" role="listitem">Technologie</a>
            <a href="#categories" role="listitem">Univers</a>
            <a href="#how" role="listitem">Fonctionnement</a>
            <a href="#pricing" role="listitem">Tarifs</a>
        </div>
        <div class="nav-cta">
            <a href="login.php" class="btn-ghost"
                style="color:white; text-decoration:none; margin-right:20px;">Connexion</a>
            <a href="register.php" class="btn-nav-primary">Démarrer</a>
        </div>
        <button class="menu-toggle" id="menuToggle" aria-label="Ouvrir le menu" aria-expanded="false">
            <span></span><span></span>
        </button>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Menu mobile">
        <a href="#features">Technologie</a>
        <a href="#categories">Univers</a>
        <a href="#how">Fonctionnement</a>
        <a href="#pricing">Tarifs</a>
        <a href="login.php" style="color:white;">Connexion</a>
        <a href="register.php" class="btn-nav-primary" style="text-align:center;">Démarrer</a>
    </div>

    <!-- ═══ HERO ═══ -->
    <section class="hero" aria-label="Introduction">
        <div class="hero-content">
            <div class="badge anim-fade">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                </svg>
                La plateforme #1 en Afrique
            </div>
            <h1 class="anim-fade"><?php echo $title; ?></h1>
            <p class="hero-subtitle anim-fade"><?php echo $subtitle; ?></p>
            <div class="hero-actions anim-fade">
                <a href="register.php" class="btn-main">
                    Commencer gratuitement
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </a>
                <button class="play-demo" id="playDemo" aria-label="Voir la démonstration">
                    <span class="play-ring">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="5 3 19 12 5 21 5 3" />
                        </svg>
                    </span>
                    <span>Voir la démo</span>
                </button>
            </div>
            <div class="hero-proof anim-fade">
                <div class="proof-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    Aucune carte requise
                </div>
                <div class="proof-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    Prêt en 5 minutes
                </div>
                <div class="proof-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    Privacy-first
                </div>
            </div>
        </div>

        <div class="hero-visual anim-fade" aria-hidden="true">
            <div class="lava-orb"></div>
            <div class="vto-phone">
                <div class="phone-notch"></div>
                <div class="phone-screen">
                    <div class="phone-header">
                        <span class="phone-status">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00FF94"
                                stroke-width="2.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                            Aura Engine v2.0
                        </span>
                    </div>
                    <div class="phone-image">
                        <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=400"
                            alt="Preview" loading="eager" width="340" height="440">
                    </div>
                    <div class="phone-metrics">
                        <div class="metric">
                            <span class="metric-label">Match</span>
                            <span class="metric-value">98<small>%</small></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Style</span>
                            <span class="metric-value">A+</span>
                        </div>
                        <div class="metric accent">
                            <span class="metric-label">Rendu</span>
                            <span class="metric-value">HD</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ LOGOS / SOCIAL PROOF ═══ -->
    <section class="social-proof" aria-label="Technologies utilisées">
        <div class="proof-track">
            <span>VIRTUAL TRY-ON IA</span><span class="dot"></span>
            <span>WHATSAPP CHECKOUT</span><span class="dot"></span>
            <span>AURA SYNC LIGHTING</span><span class="dot"></span>
            <span>ZERO INSCRIPTION CLIENT</span><span class="dot"></span>
            <span>10 THÈMES PREMIUM</span><span class="dot"></span>
            <span>VIRAL CONTENT HUB</span><span class="dot"></span>
            <span>VIRTUAL TRY-ON IA</span><span class="dot"></span>
            <span>WHATSAPP CHECKOUT</span><span class="dot"></span>
            <span>AURA SYNC LIGHTING</span><span class="dot"></span>
            <span>ZERO INSCRIPTION CLIENT</span><span class="dot"></span>
            <span>10 THÈMES PREMIUM</span><span class="dot"></span>
            <span>VIRAL CONTENT HUB</span>
        </div>
    </section>

    <!-- ═══ FEATURES ═══ -->
    <section class="features" id="features" aria-label="Fonctionnalités">
        <div class="container">
            <div class="section-eyebrow reveal">Technologie</div>
            <h2 class="section-title reveal">Chaque fonctionnalité<br>est un <span>avantage compétitif</span></h2>

            <div class="features-grid">
                <article class="feature-card glass-card reveal" tabindex="0">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446A9 9 0 1 1 12 3Z" />
                        </svg>
                    </div>
                    <h3>Aura Sync IA</h3>
                    <p>L'IA analyse l'éclairage de la photo du client et adapte le rendu de l'essayage pour un résultat
                        ultra-réaliste.</p>
                </article>

                <article class="feature-card glass-card reveal" tabindex="0">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <path d="m22 8-6 4 6 4V8Z" />
                            <rect width="14" height="12" x="2" y="6" rx="2" ry="2" />
                        </svg>
                    </div>
                    <h3>Viral Content Hub</h3>
                    <p>Chaque essayage génère automatiquement du contenu partageable prêt pour TikTok et Instagram
                        Stories.</p>
                </article>

                <article class="feature-card glass-card reveal" tabindex="0">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                    <h3>WhatsApp Checkout</h3>
                    <p>Le tunnel le plus court du marché. De l'essayage au message WhatsApp pré-rempli en un seul tap.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- ═══ CATEGORIES ═══ -->
    <section class="categories" id="categories" aria-label="Catégories">
        <div class="container">
            <div class="section-eyebrow reveal">Univers</div>
            <h2 class="section-title reveal">10 identités visuelles.<br><span>La vôtre existe déjà.</span></h2>
            <div class="categories-grid">
                <div class="cat-card reveal" style="--accent:#FFE946">
                    <h4>Streetwear</h4>
                    <p>Neon · Cyber-Tech</p>
                </div>
                <div class="cat-card reveal" style="--accent:#D4AF37">
                    <h4>Montres</h4>
                    <p>Gold · Minimalism</p>
                </div>
                <div class="cat-card reveal" style="--accent:#00FF94">
                    <h4>Sneakers</h4>
                    <p>Electric · Urban</p>
                </div>
                <div class="cat-card reveal" style="--accent:#B9F2FF">
                    <h4>Bijoux</h4>
                    <p>Pure · Radiant</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ HOW IT WORKS ═══ -->
    <section class="how-section" id="how" aria-label="Fonctionnement">
        <div class="container">
            <div class="section-eyebrow reveal">Processus</div>
            <h2 class="section-title reveal">Trois étapes.<br><span>Zéro complexité.</span></h2>
            <div class="steps-grid">
                <article class="step-card reveal">
                    <div class="step-num">01</div>
                    <h3>Créez votre compte</h3>
                    <p>Inscription gratuite en 30 secondes. Email et WhatsApp suffisent.</p>
                </article>
                <article class="step-card reveal">
                    <div class="step-num">02</div>
                    <h3>Configurez</h3>
                    <p>Choisissez votre catégorie, uploadez vos produits. Le thème s'adapte.</p>
                </article>
                <article class="step-card reveal">
                    <div class="step-num">03</div>
                    <h3>Vendez</h3>
                    <p>Partagez votre lien. Clients essayent et commandent sur WhatsApp.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ═══ PRICING SECTION ═══ -->
    <section class="pricing" id="pricing">
        <div class="container">
            <div class="section-eyebrow reveal">Tarifs</div>
            <h2 class="section-title reveal">Choissisez la puissance <span>adaptée à votre business</span></h2>

            <div class="pricing-grid">
                <?php if (empty($plans)): ?>
                    <article class="price-card glass-card reveal">
                        <div class="plan-header"><span class="plan-name">Starter</span>
                            <div class="plan-price">0 <span>XAF / mois</span></div>
                        </div>
                        <ul class="plan-features">
                            <li>1 Boutique</li>
                            <li>20 Produits</li>
                            <li>50 Essayages IA / mois</li>
                        </ul>
                        <a href="register.php" class="btn-plan">Commencer</a>
                    </article>
                    <article class="price-card glass-card featured reveal">
                        <div class="popular-tag">POPULAIRE</div>
                        <div class="plan-header"><span class="plan-name">Pro</span>
                            <div class="plan-price">9 900 <span>XAF / mois</span></div>
                        </div>
                        <ul class="plan-features">
                            <li>Produits illimités</li>
                            <li>500 Essayages IA / mois</li>
                            <li>WhatsApp Checkout</li>
                        </ul>
                        <a href="register.php" class="btn-plan btn-plan-featured">Choisir Pro</a>
                    </article>
                <?php else: ?>
                    <?php foreach ($plans as $p):
                        $f = json_decode($p['features'], true) ?: [];
                        ?>
                        <article class="price-card glass-card <?php echo $p['is_featured'] ? 'featured' : ''; ?> reveal">
                            <?php if ($p['is_featured']): ?>
                                <div class="popular-tag">POPULAIRE</div><?php endif; ?>
                            <div class="plan-header">
                                <span class="plan-name"><?php echo htmlspecialchars($p['name']); ?></span>
                                <div class="plan-price">
                                    <?php echo $p['price_xaf'] > 0 ? number_format($p['price_xaf']) : '0'; ?> <span>XAF /
                                        mois</span></div>
                            </div>
                            <ul class="plan-features">
                                <li><?php echo $f['max_stores'] ?? 1; ?> Boutique</li>
                                <li><?php echo ($f['max_products'] ?? 0) > 10000 ? 'Produits illimités' : ($f['max_products'] ?? 0) . ' Produits'; ?>
                                </li>
                                <li><?php echo $f['vto_monthly'] ?? 0; ?> Essayages IA / mois</li>
                                <li>WhatsApp Checkout</li>
                            </ul>
                            <a href="<?php echo $p['cta_url'] ?? 'register.php'; ?>"
                                class="btn-plan <?php echo $p['is_featured'] ? 'btn-plan-featured' : ''; ?>"><?php echo $p['cta_text'] ?? 'Commencer'; ?></a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ═══ CTA FINAL ═══ -->
    <section class="final-cta">
        <div class="container">
            <h2 class="reveal">Prêt à transformer<br><span class="gradient-text">votre business ?</span></h2>
            <a href="register.php" class="btn-main reveal">Lancer ma boutique</a>
        </div>
    </section>

    <!-- ═══ FOOTER ═══ -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo">Aura<span>Store</span></div>
                    <p>La plateforme IA qui révolutionne la mode.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> AuraStore. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>

</html>