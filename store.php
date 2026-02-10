<?php
/**
 * AuraStore - Public Storefront
 * Each seller's unique shop page
 */
require_once 'includes/functions.php';
require_once 'config/database.php';

$slug = $_GET['s'] ?? '';
if (empty($slug)) {
    header("Location: index.html");
    exit();
}

$store = getStoreBySlug($slug);
if (!$store) {
    echo "Boutique introuvable.";
    exit();
}

incrementView($store['id']);

$themes = include 'config/themes.php';
$theme = $themes[$store['category']] ?? $themes['streetwear'];
$products = getStoreProducts($store['id']);
$featured = getStoreProducts($store['id'], 10, true);
$whatsapp = preg_replace('/[^0-9]/', '', $store['whatsapp_number']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($store['store_name']); ?> | Boutique en ligne
    </title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($store['description'] ?? '', 0, 160)); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($theme['font']); ?>:wght@300;400;700;800&family=Inter:wght@300;400;600;800&display=swap"
        rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
        :root {
            --p:
                <?php echo $theme['primary']; ?>
            ;
            --s:
                <?php echo $theme['secondary']; ?>
            ;
            --a:
                <?php echo $theme['accent']; ?>
            ;
            --bg:
                <?php echo $theme['bg']; ?>
            ;
            --font: '<?php echo $theme['font']; ?>', 'Inter', sans-serif;
            --grad: linear-gradient(135deg, var(--p), var(--s));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            color: white;
            font-family: var(--font);
            overflow-x: hidden;
        }

        .glass {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
        }

        /* Header */
        .store-header {
            padding: 25px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
        }

        .store-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .store-logo {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.1);
        }

        .store-brand h1 {
            font-size: 1.3rem;
            font-weight: 800;
        }

        .store-brand .powered {
            font-size: 0.65rem;
            opacity: 0.3;
            letter-spacing: 1px;
        }

        .store-nav {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .store-nav a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .store-nav a:hover {
            color: var(--p);
        }

        .whatsapp-btn {
            background: #25D366;
            color: white;
            padding: 10px 22px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .whatsapp-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(37, 211, 102, 0.3);
        }

        /* Hero Banner */
        .store-hero {
            height: 50vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-blob {
            position: absolute;
            width: 600px;
            height: 600px;
            background: var(--grad);
            filter: blur(120px);
            opacity: 0.25;
            border-radius: 50%;
            animation: float 12s ease-in-out infinite alternate;
        }

        @keyframes float {
            from {
                transform: translate(-20%, -10%) scale(1);
            }

            to {
                transform: translate(20%, 10%) scale(1.3);
            }
        }

        .store-hero h2 {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            position: relative;
            z-index: 2;
            line-height: 1;
            letter-spacing: -2px;
        }

        .store-hero p {
            max-width: 500px;
            opacity: 0.5;
            position: relative;
            z-index: 2;
            margin-top: 20px;
            font-size: 1.05rem;
        }

        /* Product Grid */
        .products-section {
            padding: 60px 5%;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title span {
            width: 40px;
            height: 3px;
            background: var(--p);
            display: inline-block;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .product-card {
            overflow: hidden;
            transition: transform 0.5s, box-shadow 0.5s;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .product-img {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            border-radius: 16px 16px 0 0;
            background: rgba(255, 255, 255, 0.05);
        }

        .product-info {
            padding: 20px;
        }

        .product-info h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--p);
        }

        .product-old-price {
            text-decoration: line-through;
            opacity: 0.4;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .try-on-btn {
            flex: 1;
            padding: 12px;
            background: var(--grad);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.85rem;
        }

        .try-on-btn:hover {
            transform: scale(1.03);
        }

        .order-btn {
            padding: 12px 18px;
            background: #25D366;
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .order-btn:hover {
            background: #1EB554;
        }

        /* Like Button */
        .like-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .like-btn:hover,
        .like-btn.liked {
            background: #FF4D4D;
        }

        .product-card-inner {
            position: relative;
        }

        /* Featured Badge */
        .featured-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            background: var(--p);
            color: var(--bg);
            font-size: 0.7rem;
            font-weight: 800;
            border-radius: 50px;
            letter-spacing: 1px;
        }

        /* VTO Modal Redesign */
        .vto-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 20px;
        }

        .vto-modal-overlay.active {
            display: flex;
        }

        .vto-modal {
            width: 100%;
            max-width: 520px;
            background: rgba(15, 12, 10, 0.85);
            border: 1px solid rgba(254, 117, 1, 0.15);
            border-radius: 32px;
            padding: 48px 32px;
            position: relative;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
            overflow: hidden;
        }

        .vto-close {
            position: absolute !important;
            top: 20px !important;
            right: 20px !important;
            width: 44px !important;
            height: 44px !important;
            background: rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 50% !important;
            color: white !important;
            font-size: 28px !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: 0.3s !important;
            z-index: 999999 !important;
            pointer-events: auto !important;
        }

        .vto-close:hover {
            background: #B4160B !important;
            transform: rotate(90deg) scale(1.1) !important;
        }

        .vto-modal h3 {
            font-family: var(--font);
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -1px;
            text-align: center;
        }

        .vto-modal .vto-desc {
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
            margin-bottom: 32px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .upload-zone {
            border: 2px dashed rgba(254, 117, 1, 0.15);
            background: rgba(254, 117, 1, 0.02);
            border-radius: 24px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .upload-zone:hover {
            border-color: var(--p);
            background: rgba(254, 117, 1, 0.06);
            transform: scale(1.02);
        }

        .upload-zone .icon {
            font-size: 3.5rem;
            margin-bottom: 16px;
            display: block;
            filter: grayscale(1);
            opacity: 0.8;
        }

        .upload-zone p {
            font-weight: 600;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .upload-zone span {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.3);
            margin-top: 8px;
            display: block;
        }

        .vto-progress-wrapper {
            margin-top: 24px;
            padding: 0 10px;
        }

        .vto-status-text {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--p);
            font-weight: 800;
            margin-bottom: 10px;
            display: block;
        }

        .vto-progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .vto-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--p), var(--s));
            box-shadow: 0 0 15px var(--p);
            transition: width 0.4s;
        }

        .result-container {
            margin-top: 20px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .result-img {
            width: 100%;
            border-radius: 20px;
            border: 1px solid rgba(254, 117, 1, 0.2);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .btn-order-look {
            margin-top: 24px;
            width: 100%;
            padding: 18px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--p), var(--s));
            color: white;
            font-weight: 800;
            font-family: var(--font);
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            box-shadow: 0 15px 35px rgba(254, 117, 1, 0.25);
            transition: 0.3s;
        }

        .btn-order-look:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 45px rgba(254, 117, 1, 0.4);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Footer */
        .store-footer {
            text-align: center;
            padding: 60px 5%;
            opacity: 0.3;
            font-size: 0.8rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .store-footer a {
            color: var(--p);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .store-header {
                flex-direction: column;
                gap: 15px;
            }

            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .store-hero h2 {
                font-size: 2.2rem;
            }

            .vto-modal {
                padding: 30px;
            }

            .upload-zone {
                padding: 30px;
            }
        }

        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Store Header -->
    <header class="store-header">
        <div class="store-brand">
            <?php if ($store['logo_url']): ?>
                <img src="<?php echo $store['logo_url']; ?>" class="store-logo" alt="Logo">
            <?php endif; ?>
            <div>
                <h1>
                    <?php echo htmlspecialchars($store['store_name']); ?>
                </h1>
                <div class="powered">POWERED BY AURASTORE</div>
            </div>
        </div>
        <div class="store-nav">
            <a href="#products">Catalogue</a>
            <a href="https://wa.me/<?php echo $whatsapp; ?>" class="whatsapp-btn" target="_blank">💬 WhatsApp</a>
        </div>
    </header>

    <!-- Hero -->
    <section class="store-hero">
        <div class="hero-blob"></div>
        <h2>
            <?php echo htmlspecialchars($store['store_name']); ?>
        </h2>
        <p>
            <?php echo htmlspecialchars($store['description'] ?? 'Bienvenue dans notre boutique. Explorez, essayez, commandez.'); ?>
        </p>
    </section>

    <!-- Featured Products -->
    <?php if (!empty($featured)): ?>
        <section class="products-section">
            <div class="section-title"><span></span> EN VEDETTE</div>
            <div class="product-grid">
                <?php foreach ($featured as $p): ?>
                    <div class="product-card glass reveal">
                        <div class="product-card-inner">
                            <div class="featured-badge">★ VEDETTE</div>
                            <button class="like-btn" onclick="toggleLike(this)">♡</button>
                            <img src="<?php echo $p['image_url'] ?: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&q=80&w=400'; ?>"
                                class="product-img" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        </div>
                        <div class="product-info">
                            <h3>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </h3>
                            <div>
                                <span class="product-price">
                                    <?php echo formatPrice($p['price']); ?>
                                </span>
                                <?php if ($p['old_price']): ?>
                                    <span class="product-old-price">
                                        <?php echo formatPrice($p['old_price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <?php if (true): // Force for test ?>
                                    <button class="try-on-btn"
                                        onclick="openVTO(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>', <?php echo $p['price']; ?>, '<?php echo htmlspecialchars($p['image_url']); ?>')">✨
                                        Essayer</button>
                                <?php endif; ?>
                                <button class="order-btn"
                                    onclick="orderWhatsApp(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>', <?php echo $p['price']; ?>)">Commander</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- All Products -->
    <section class="products-section" id="products">
        <div class="section-title"><span></span> TOUS LES ARTICLES</div>
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 80px; opacity: 0.3;">
                <p style="font-size: 3rem;">🛍️</p>
                <p>La boutique prépare ses articles. Revenez bientôt !</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card glass reveal">
                        <div class="product-card-inner">
                            <button class="like-btn" onclick="toggleLike(this)">♡</button>
                            <img src="<?php echo $p['image_url'] ?: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&q=80&w=400'; ?>"
                                class="product-img" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
                        </div>
                        <div class="product-info">
                            <h3>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </h3>
                            <div>
                                <span class="product-price">
                                    <?php echo formatPrice($p['price']); ?>
                                </span>
                                <?php if ($p['old_price']): ?>
                                    <span class="product-old-price">
                                        <?php echo formatPrice($p['old_price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <?php if (true): // Force show for tests ?>
                                    <button class="try-on-btn"
                                        onclick="openVTO(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>', <?php echo $p['price']; ?>, '<?php echo htmlspecialchars($p['image_url']); ?>')">✨
                                        Essayer</button>
                                <?php endif; ?>
                                <button class="order-btn"
                                    onclick="orderWhatsApp(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>', <?php echo $p['price']; ?>)">Commander</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- VTO Modal -->
    <div class="vto-modal-overlay" id="vtoModal">
        <div class="vto-modal glass">
            <button class="vto-close" onclick="closeVTO()">&times;</button>

            <div id="vtoStepUpload">
                <h3>✨ Aura Magic</h3>
                <p class="vto-desc">Révélez votre style. Téléchargez une photo de vous (de face) et notre IA fera le
                    reste.</p>

                <div class="upload-zone" id="vtoUploadZone" onclick="document.getElementById('vtoUpload').click();">
                    <span class="icon" id="vtoIcon">📸</span>
                    <p id="vtoText">Importer ma photo</p>
                    <span>JPG, PNG ou WebP. Max 5MB.</span>
                    <input type="file" id="vtoUpload" accept="image/*" style="display:none;">
                </div>

                <div class="vto-progress-wrapper" id="vtoProgressSection" style="display:none;">
                    <span class="vto-status-text" id="vtoStatus">Initialisation de l'IA...</span>
                    <div class="vto-progress-bar">
                        <div class="vto-fill" id="vtoMetricFill"></div>
                    </div>
                </div>
            </div>

            <div id="vtoResult" class="result-container" style="display:none;">
                <h3>Votre Look ✨</h3>
                <p class="vto-desc">Généré avec Aura Sync v2.0</p>
                <img id="vtoResultImg" src="" class="result-img">
                <button class="btn-order-look"
                    onclick="orderWhatsApp(currentVTOProduct.id, currentVTOProduct.name, currentVTOProduct.price)">
                    Commander ce look
                </button>
            </div>

            <div style="text-align: center; margin-top: 25px;">
                <button onclick="closeVTO()"
                    style="background: none; border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.4); padding: 8px 20px; border-radius: 50px; cursor: pointer; font-size: 0.8rem;">
                    ✕ Fermer la fenêtre
                </button>
            </div>

            <p style="font-size: 0.7rem; opacity: 0.2; margin-top: 20px; text-align: center;">
                🔒 Traitement sécurisé. Votre photo est supprimée après l'essayage.
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="store-footer">
        <p>Propulsé par <a href="index.html">AuraStore</a> — Essayage virtuel IA</p>
    </footer>

    <script>
        const WHATSAPP = '<?php echo $whatsapp; ?>';

        // GSAP Scroll Reveals
        gsap.registerPlugin(ScrollTrigger);
        document.querySelectorAll('.reveal').forEach(el => {
            gsap.from(el, {
                scrollTrigger: { trigger: el, start: 'top 85%' },
                y: 40, opacity: 0, duration: 0.8, ease: "power4.out"
            });
        });

        gsap.from('.store-hero h2', { y: 30, opacity: 0, duration: 1.5, ease: "power4.out" });
        gsap.from('.store-hero p', { y: 20, opacity: 0, duration: 1.5, delay: 0.2, ease: "power4.out" });

        // VTO Modal
        let currentVTOProduct = null;

        function openVTO(productId, productName, productPrice, productImg) {
            currentVTOProduct = { id: productId, name: productName, price: productPrice, image: productImg };
            document.getElementById('vtoModal').classList.add('active');
            resetVTO();
        }

        function closeVTO() {
            console.log("Closing VTO...");
            const modal = document.getElementById('vtoModal');
            modal.classList.remove('active');
            // Force reset after transition
            setTimeout(resetVTO, 400);
        }

        function resetVTO() {
            document.getElementById('vtoStepUpload').style.display = 'block';
            document.getElementById('vtoUploadZone').style.display = 'block';
            document.getElementById('vtoUploadZone').style.opacity = '1';
            document.getElementById('vtoUploadZone').style.pointerEvents = 'all';
            document.getElementById('vtoResult').style.display = 'none';
            document.getElementById('vtoProgressSection').style.display = 'none';
            document.getElementById('vtoText').innerText = "Importer ma photo";
            document.getElementById('vtoMetricFill').style.width = '0%';
        }

        document.getElementById('vtoUpload').addEventListener('change', async function (e) {
            const file = e.target.files[0];
            if (!file) return;

            // Start AI Processing UI
            const status = document.getElementById('vtoStatus');
            const progressSection = document.getElementById('vtoProgressSection');
            const fill = document.getElementById('vtoMetricFill');

            progressSection.style.display = 'block';
            document.getElementById('vtoUploadZone').style.opacity = '0.3';
            document.getElementById('vtoUploadZone').style.pointerEvents = 'none';

            // Phase 1: local UI progress
            let p = 0;
            const progressInterval = setInterval(() => {
                p += Math.random() * 5;
                if (p > 95) p = 95; // Wait for API at 95%
                fill.style.width = p + '%';
                if (p > 10) status.innerText = "Analyse morphologique...";
                if (p > 40) status.innerText = "Synchronisation Aura Sync...";
                if (p > 70) status.innerText = "Génération du rendu final...";
            }, 300);

            try {
                // Convert file to Base64 for the API
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = async function () {
                    const base64Image = reader.result;

                    // Call our Proxy API with REAL data
                    const response = await fetch('api/tryon.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            product_image: currentVTOProduct.image,
                            user_image: base64Image
                        })
                    });

                    const data = await response.json();

                    clearInterval(progressInterval);
                    fill.style.width = '100%';

                    if (data.status === 'error') {
                        status.innerText = "⚠️ " + (data.error || "Erreur IA");
                        status.style.color = "#ff4d4d";
                    } else {
                        status.innerText = "Aura Sync Terminé ! ✨";
                    }

                    setTimeout(() => {
                        showResult(data.url || data.image?.url);
                    }, 800);
                };

            } catch (err) {
                console.error(err);
                status.innerText = "Erreur de connexion IA...";
                clearInterval(progressInterval);
            }
        });

        function showResult(imgUrl) {
            document.getElementById('vtoStepUpload').style.display = 'none';
            document.getElementById('vtoResult').style.display = 'block';
            document.getElementById('vtoResultImg').src = imgUrl || 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=600';
            if (window.confetti) confetti();
        }

        // Close on click overlay
        document.getElementById('vtoModal').addEventListener('click', function (e) {
            if (e.target === this) closeVTO();
        });

        // WhatsApp Order
        function orderWhatsApp(id, name, price) {
            const msg = encodeURIComponent(
                `Bonjour ! Je souhaite commander :\n\n` +
                `📦 ${name}\n` +
                `💰 Prix : ${price} €\n\n` +
                `Merci de me confirmer la disponibilité.`
            );
            window.open(`https://wa.me/${WHATSAPP}?text=${msg}`, '_blank');
        }

        // Like Toggle
        function toggleLike(btn) {
            btn.classList.toggle('liked');
            btn.textContent = btn.classList.contains('liked') ? '♥' : '♡';
        }
    </script>
</body>

</html>