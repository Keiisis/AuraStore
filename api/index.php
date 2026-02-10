<?php
/**
 * AuraStore SaaS - Multitenancy Router
 * Handles store routing based on URL parameters
 */

$stores = [
    'demo-street' => ['category' => 'streetwear', 'name' => 'Cyber Street Store'],
    'luxury-watch' => ['category' => 'luxury_watches', 'name' => 'The Hour Glass'],
];

$store_id = $_GET['store'] ?? 'demo-street';
$store_data = $stores[$store_id] ?? $stores['demo-street'];

$themes = include 'config/themes.php';
$current_theme = $themes[$store_data['category']];

// Injecting theme variables into global scope for the view
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $store_data['name']; ?> | AuraStore
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=<?php echo str_replace(' ', '+', $current_theme['font']); ?>:wght@300;400;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --p-color: <?php echo $current_theme['primary']; ?>;
            --s-color: <?php echo $current_theme['secondary']; ?>;
            --a-color: <?php echo $current_theme['accent']; ?>;
            --bg-color: <?php echo $current_theme['bg']; ?>;
            --font-main: '<?php echo $current_theme['font']; ?>', sans-serif;
            --lava-gradient: linear-gradient(135deg, var(--p-color), var(--s-color));
        }
    </style>
    <link rel="stylesheet" href="public/css/core-engine.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</head>

<body class="theme-<?php echo $current_theme['style']; ?>">

    <div id="app">
        <header>
            <div class="store-info">
                <h1>
                    <?php echo $store_data['name']; ?>
                </h1>
                <p>
                    <?php echo $current_theme['vibe']; ?>
                </p>
            </div>
            <nav class="glass">
                <a href="#">Shop</a>
                <a href="#">Try-On</a>
                <a href="#">Contact</a>
            </nav>
        </header>

        <main>
            <!-- Store Content will be dynamic -->
            <section class="vto-dynamic-hero">
                <div class="visual-container">
                    <div class="blob-aura"></div>
                    <img src="public/img/placeholder-model.png" class="model-view" alt="">
                </div>
                <div class="controls glass">
                    <h3>AI TRY-ON</h3>
                    <div class="options">
                        <button class="vto-btn">Upload Photo</button>
                        <button class="vto-btn primary">Analyze Style</button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="public/js/core-engine.js"></script>
</body>

</html>