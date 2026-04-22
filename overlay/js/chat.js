// ═══════════════════════════════════════════════
// KLAREG OVERLAY — Chat Engine
// Twitch IRC chat rendering + demo mode fake messages
// ═══════════════════════════════════════════════

const Chat = (() => {
    const MAX_MESSAGES = 50;
    let ws = null;
    let chatContainer = null;
    let messageCount = 0;

    // Username color palette (Twitch-inspired, premium)
    const COLORS = [
        '#5B7FFF', '#FF6B9D', '#FFD93D', '#6BCB77', '#4FC3F7',
        '#FF8A65', '#BA68C8', '#81C784', '#E57373', '#64B5F6'
    ];

    function hashColor(username) {
        let hash = 0;
        for (let i = 0; i < username.length; i++) {
            hash = username.charCodeAt(i) + ((hash << 5) - hash);
        }
        return COLORS[Math.abs(hash) % COLORS.length];
    }

    function renderMessage(data) {
        if (!chatContainer) {
            chatContainer = document.querySelector('.chat-messages');
        }
        if (!chatContainer) return;

        const { username, message, badges = [], color } = data;
        const safeColor = sanitizeColor(color) || hashColor(username);

        const msgEl = document.createElement('div');
        msgEl.className = 'chat-msg';

        if (badges.length) {
            const badgesWrap = document.createElement('span');
            badgesWrap.className = 'msg-badges';
            badges.forEach(b => {
                const safeB = String(b).replace(/[^a-z0-9_-]/gi, '');
                const badgeEl = document.createElement('span');
                badgeEl.className = `msg-badge badge-${safeB}`;
                badgeEl.textContent = badgeLabel(safeB);
                badgesWrap.appendChild(badgeEl);
            });
            msgEl.appendChild(badgesWrap);
        }

        const userEl = document.createElement('span');
        userEl.className = 'msg-username';
        userEl.style.color = safeColor;
        userEl.textContent = username;
        msgEl.appendChild(userEl);

        const colonEl = document.createElement('span');
        colonEl.className = 'msg-colon';
        colonEl.textContent = ':';
        msgEl.appendChild(colonEl);

        const textEl = document.createElement('span');
        textEl.className = 'msg-text';
        textEl.textContent = message;
        msgEl.appendChild(textEl);

        chatContainer.appendChild(msgEl);
        messageCount++;

        // Remove oldest if over limit
        while (chatContainer.children.length > MAX_MESSAGES) {
            const oldest = chatContainer.firstChild;
            oldest.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 300, fill: 'forwards' }).onfinish = () => oldest.remove();
        }

        // Auto-scroll
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function badgeLabel(type) {
        const labels = { mod: 'M', vip: 'V', sub: 'S', bits: 'B' };
        return labels[type] || '?';
    }

    function sanitizeColor(c) {
        if (typeof c !== 'string') return null;
        return /^#[0-9A-Fa-f]{6}$/.test(c) ? c : null;
    }

    // ── Twitch IRC WebSocket ──
    function connect(channel) {
        if (ws) ws.close();
        ws = new WebSocket('wss://irc-ws.chat.twitch.tv:443');

        ws.onopen = () => {
            ws.send('CAP REQ :twitch.tv/tags twitch.tv/commands');
            ws.send('PASS oauth:' + CONFIG.oauthToken);
            ws.send('NICK justinfan' + Math.floor(Math.random() * 99999));
            ws.send('JOIN #' + channel.toLowerCase());
        };

        ws.onmessage = (e) => {
            const lines = e.data.split('\r\n');
            lines.forEach(line => {
                if (line.startsWith('PING')) {
                    ws.send('PONG :' + line.split(':')[1]);
                    return;
                }
                if (line.includes('PRIVMSG')) {
                    parseIRCMessage(line);
                }
            });
        };

        ws.onclose = () => {
            if (!CONFIG.demoMode) setTimeout(() => connect(channel), 5000);
        };
    }

    function parseIRCMessage(line) {
        try {
            const tagsPart = line.split(';');
            const badges = [];
            const badgeStr = tagsPart.find(t => t.startsWith('@badges='));
            if (badgeStr) {
                const bv = badgeStr.split('=')[1];
                if (bv.includes('moderator')) badges.push('mod');
                if (bv.includes('vip')) badges.push('vip');
                if (bv.includes('subscriber')) badges.push('sub');
                if (bv.includes('bits')) badges.push('bits');
            }
            const colorMatch = line.match(/color=(#[0-9A-Fa-f]{6})/);
            const color = colorMatch ? colorMatch[1] : null;

            const userMatch = line.match(/display-name=([^;]+)/);
            const username = userMatch ? userMatch[1] : 'Unknown';

            const msgMatch = line.match(/PRIVMSG #[^ ]+ :(.+)/);
            const message = msgMatch ? msgMatch[1].trim() : '';

            if (username && message) {
                renderMessage({ username, message, badges, color });
            }
        } catch(e) { /* silently ignore malformed */ }
    }

    // ── Init ──
    function init(container) {
        chatContainer = container || document.querySelector('.chat-messages');
        // Live chat messages arrive via Reverb (reverb-client.js calls renderMessage)
    }

    return { init, renderMessage };
})();