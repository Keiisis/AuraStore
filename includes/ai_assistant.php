<?php
/**
 * Aura AI Assistant - Floating Chat UI
 */
function renderAuraAssistant($persona = 'default')
{
    $userId = $_SESSION['user_id'] ?? 0;
    ?>
    <!-- Aura AI floating button -->
    <div id="aura-ai-bubble"
        style="position:fixed; bottom:30px; right:30px; z-index:9999; cursor:pointer; width:65px; height:65px; background:linear-gradient(135deg, #FE7501, #B4160B); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow: 0 10px 30px rgba(254,117,1,0.4); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        <div class="pulse-ring"
            style="position:absolute; inset:0; border-radius:50%; border:2px solid #FE7501; animation: ai-pulse 2s infinite;">
        </div>
        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="white" stroke-width="2">
            <path d="M12 2a10 10 0 1 0 10 10H12V2z"></path>
            <path d="M12 12 2.1 2.1"></path>
            <path d="M12 12l8.9-8.9"></path>
        </svg>
    </div>

    <!-- AI Chat Window -->
    <div id="aura-ai-window"
        style="display:none; position:fixed; bottom:110px; right:30px; width:380px; height:500px; background:#0f0f12; border:1px solid rgba(255,255,255,0.1); border-radius:30px; z-index:9999; flex-direction:column; overflow:hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
        <!-- Header -->
        <div
            style="padding:20px; background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; gap:12px;">
            <div style="width:10px; height:10px; background:#00FF94; border-radius:50%; box-shadow:0 0 10px #00FF94;"></div>
            <strong style="color:white; font-size:0.9rem;">Aura Intelligence <span
                    style="opacity:0.4; font-weight:300;">(Groq Llama 3)</span></strong>
            <button id="close-aura"
                style="margin-left:auto; background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <!-- Messages -->
        <div id="aura-chat-body"
            style="flex:1; padding:20px; overflow-y:auto; font-size:0.85rem; line-height:1.5; color:rgba(255,255,255,0.8); display:flex; flex-direction:column; gap:15px;">
            <div class="ai-msg"
                style="background:#1a1a20; padding:12px 18px; border-radius:18px 18px 18px 0; max-width:85%;">
                Bonjour ! ✨ Je suis l'intelligence d'AuraStore. Comment puis-je vous aider aujourd'hui ?
            </div>
        </div>

        <!-- Input -->
        <div style="padding:20px; background:rgba(0,0,0,0.2); border-top:1px solid rgba(255,255,255,0.05);">
            <div
                style="display:flex; gap:10px; background:rgba(255,255,255,0.05); border-radius:50px; padding:6px 6px 6px 15px; border:1px solid rgba(255,255,255,0.1);">
                <input type="text" id="aura-input" placeholder="Posez-moi une question..."
                    style="flex:1; background:none; border:none; color:white; outline:none; font-size:0.85rem;">
                <button id="send-aura"
                    style="width:36px; height:36px; border-radius:50%; background:#FE7501; border:none; color:white; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes ai-pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }

            100% {
                transform: scale(1.6);
                opacity: 0;
            }
        }

        .user-msg {
            align-self: flex-end;
            background: #FE7501;
            color: white !important;
            padding: 10px 15px;
            border-radius: 18px 18px 0 18px;
            max-width: 85%;
        }

        .ai-msg {
            align-self: flex-start;
        }
    </style>

    <script>
        (function () {
            const bubble = document.getElementById('aura-ai-bubble');
            const windowEl = document.getElementById('aura-ai-window');
            const closeBtn = document.getElementById('close-aura');
            const input = document.getElementById('aura-input');
            const btn = document.getElementById('send-aura');
            const body = document.getElementById('aura-chat-body');
            const persona = "<?php echo $persona; ?>";

            bubble.onclick = () => {
                windowEl.style.display = windowEl.style.display === 'none' ? 'flex' : 'none';
                bubble.style.transform = windowEl.style.display === 'flex' ? 'scale(0)' : 'scale(1)';
            };

            closeBtn.onclick = () => {
                windowEl.style.display = 'none';
                bubble.style.transform = 'scale(1)';
            };

            async function sendMessage() {
                const text = input.value.trim();
                if (!text) return;

                // UI update
                input.value = '';
                const userDiv = document.createElement('div');
                userDiv.className = 'user-msg';
                userDiv.innerText = text;
                body.appendChild(userDiv);
                body.scrollTop = body.scrollHeight;

                // Loading
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'ai-msg';
                loadingDiv.style.opacity = '0.5';
                loadingDiv.innerText = 'Réflexion...';
                body.appendChild(loadingDiv);

                try {
                    const res = await fetch('ai_proxy.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: text, persona: persona })
                    });
                    const data = await res.json();

                    body.removeChild(loadingDiv);

                    const aiDiv = document.createElement('div');
                    aiDiv.className = 'ai-msg';
                    aiDiv.style.background = '#1a1a20';
                    aiDiv.style.padding = '12px 18px';
                    aiDiv.style.borderRadius = '18px 18px 18px 0';
                    aiDiv.style.maxWidth = '85%';
                    aiDiv.innerText = data.reply || "Désolé, j'ai rencontré un problème.";
                    body.appendChild(aiDiv);
                    body.scrollTop = body.scrollHeight;

                } catch (err) {
                    loadingDiv.innerText = "Erreur de connexion.";
                }
            }

            btn.onclick = sendMessage;
            input.onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };
        })();
    </script>
    <?php
}
