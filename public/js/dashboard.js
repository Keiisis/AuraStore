/**
 * AuraStore Dashboard - Client-Side Logic
 */

// Panel Navigation
function showPanel(name) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

    const panel = document.getElementById('panel-' + name);
    if (panel) panel.classList.add('active');

    const link = document.querySelector(`[data-panel="${name}"]`);
    if (link) link.classList.add('active');
}

document.querySelectorAll('.nav-link[data-panel]').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        showPanel(link.dataset.panel);
    });
});

// Modal Logic
const modal = document.getElementById('productModal');
const openBtn = document.getElementById('openAddModal');

if (openBtn) {
    openBtn.addEventListener('click', () => {
        modal.classList.add('active');
    });
}

function closeModal() {
    modal.classList.remove('active');
}

modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
});

// Product Form Submission (AJAX)
const productForm = document.getElementById('productForm');
if (productForm) {
    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = productForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="icon-loader spin"></i> Publication...';

        const formData = new FormData(productForm);

        try {
            const res = await fetch('products.php?action=create', {
                method: 'POST',
                body: formData
            });

            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Server response:', text);
                throw new Error('Réponse serveur invalide (voir console)');
            }

            if (data.success) {
                // Success Toast
                const toast = document.createElement('div');
                toast.className = 'toast success';
                toast.innerHTML = '<i data-lucide="check"></i> Produit publié !';
                document.body.appendChild(toast);
                if (window.lucide) lucide.createIcons();

                setTimeout(() => {
                    closeModal();
                    location.reload();
                }, 1000);
            } else {
                alert(data.error || 'Erreur lors de la création.');
            }
        } catch (err) {
            console.error(err);
            alert('Erreur: ' + err.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

// Delete Product
async function deleteProduct(id) {
    if (!confirm('Supprimer ce produit ?')) return;

    const formData = new FormData();
    formData.append('id', id);

    try {
        const res = await fetch('products.php?action=delete', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) location.reload();
    } catch (err) {
        alert('Erreur réseau.');
    }
}

// Edit Product
function editProduct(id) {
    alert('Fonctionnalité d\'édition - Redirection vers le formulaire...');
    // Future: Open modal pre-filled with product data
}

// Copy Store Link
function copyLink() {
    const slug = document.getElementById('storeSlug')?.value || '';
    const url = window.location.origin + window.location.pathname.replace('dashboard.php', '') + 'store.php?s=' + slug;

    navigator.clipboard.writeText(url).then(() => {
        const btn = document.querySelector('.copy-btn');
        if (btn) {
            btn.textContent = '✅ Copié !';
            setTimeout(() => btn.textContent = '📋 Copier', 2000);
        }
        alert('Lien copié : ' + url);
    });
}

// GSAP Animations
if (typeof gsap !== 'undefined') {
    gsap.from('.animate-card', {
        y: 30,
        opacity: 0,
        stagger: 0.1,
        duration: 0.8,
        ease: "power4.out"
    });
}
