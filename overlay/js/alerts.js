// ═══════════════════════════════════════════════
// KLAREG OVERLAY — Alert Engine
// All 11 alert types with queue management + demo mode
// ═══════════════════════════════════════════════

const Alerts = (() => {
    const EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';
    const EASE_OUT = 'cubic-bezier(0.33, 1, 0.68, 1)';
    const HOLD_TIME = 6000;

    let container = null;
    let queue = [];
    let processing = false;

    const ALERT_DEFS = {
        follow:   { icon: 'ph:user-plus',     label: 'NOUVEAU FOLLOW',      cssClass: 'alert-follow' },
        sub:      { icon: 'ph:star',           label: 'NOUVEAU SUB',         cssClass: 'alert-sub' },
        resub:    { icon: 'ph:star',           label: 'RESUB',               cssClass: 'alert-resub' },
        giftsub:  { icon: 'ph:gift',           label: 'SUB OFFERT',          cssClass: 'alert-giftsub' },
        bits:     { icon: 'ph:diamond',        label: 'BITS',                cssClass: 'alert-bits' },
        donation: { icon: 'ph:hand-coins',     label: 'DON',                 cssClass: 'alert-donation' },
        raid:     { icon: 'ph:users-three',    label: 'RAID',                cssClass: 'alert-raid' },
        host:     { icon: 'ph:monitor',        label: 'HOST',                cssClass: 'alert-host' },
        redeem:   { icon: 'ph:ticket',         label: 'RÉDEMPTION',          cssClass: 'alert-redeem' },
        vip:      { icon: 'ph:crown-simple',   label: 'VIP AJOUTÉ',         cssClass: 'alert-vip' },
        mod:      { icon: 'ph:shield-check',   label: 'MOD AJOUTÉ',          cssClass: 'alert-mod' }
    };

    // ── Color map ──
    const ALERT_COLORS = {
        follow:   '#5B7FFF',
        sub:      '#F5A623',
        resub:    '#F5A623',
        giftsub:  '#EC4899',
        bits:     '#F5A623',
        donation: '#22C55E',
        raid:     '#A855F7',
        host:     '#5B7FFF',
        redeem:   '#A855F7',
        vip:      '#F5A623',
        mod:      '#22C55E'
    };

    function init(el) {
        container = el || document.getElementById('alert-container') || document.querySelector('.alert-container');
    }

    function push(alertData) {
        queue.push(alertData);
        if (!processing) processQueue();
    }

    function processQueue() {
        if (queue.length === 0) { processing = false; return; }
        processing = true;
        const data = queue.shift();
        renderAlert(data, () => processQueue());
    }

    function renderAlert(data, onDone) {
        if (!container) return onDone?.();

        const def = ALERT_DEFS[data.type] || ALERT_DEFS.follow;
        const color = ALERT_COLORS[data.type] || '#5B7FFF';

        const el = document.createElement('div');
        el.className = `alert-popup ${def.cssClass}`;

        // Special raid full-width treatment
        if (data.type === 'raid' && data.viewers >= 100) {
            el.classList.add('alert-raid-full');
        }

        el.style.setProperty('--alert-color', color);

        let extraInfo = data.message || '';
        if (data.type === 'resub' && data.months) {
            extraInfo = `${data.months} mois consécutifs`;
        }
        if (data.type === 'giftsub' && data.recipient) {
            extraInfo = `Offert à ${data.recipient}`;
            if (data.count) extraInfo = `${data.count} subs offerts !`;
        }
        if (data.type === 'raid') {
            extraInfo = `Raid avec ${Number(data.viewers || 0).toLocaleString()} spectateurs`;
        }
        if (data.type === 'host') {
            extraInfo = `Host avec ${Number(data.viewers || 0).toLocaleString()} spectateurs`;
        }
        if (data.type === 'bits') {
            extraInfo = `${Number(data.amount || 0)} bits envoyés`;
        }
        if (data.type === 'redeem') {
            extraInfo = data.reward || 'Rédemption de points';
        }

        const glow = document.createElement('div');
        glow.className = 'alert-glow';
        glow.style.background = `linear-gradient(90deg,${color},transparent)`;
        el.appendChild(glow);

        const accentBar = document.createElement('div');
        accentBar.className = 'alert-accent-bar';
        accentBar.style.background = color;
        el.appendChild(accentBar);

        const iconWrap = document.createElement('div');
        iconWrap.className = 'alert-icon-wrap';
        iconWrap.style.background = `${color}15`;
        iconWrap.style.border = `1px solid ${color}30`;
        const icon = document.createElement('iconify-icon');
        icon.setAttribute('icon', def.icon);
        icon.setAttribute('width', '24');
        icon.style.color = color;
        iconWrap.appendChild(icon);
        el.appendChild(iconWrap);

        const content = document.createElement('div');
        content.className = 'alert-content';
        const typeEl = document.createElement('div');
        typeEl.className = 'alert-type';
        typeEl.style.color = color;
        typeEl.textContent = def.label;
        content.appendChild(typeEl);
        const userEl = document.createElement('div');
        userEl.className = 'alert-user';
        userEl.textContent = data.user || 'Unknown';
        content.appendChild(userEl);
        if (extraInfo) {
            const msgEl = document.createElement('div');
            msgEl.className = 'alert-msg';
            msgEl.textContent = extraInfo;
            content.appendChild(msgEl);
        }
        el.appendChild(content);

        if (data.amount) {
            const amountEl = document.createElement('div');
            amountEl.className = 'alert-amount';
            amountEl.style.color = color;
            amountEl.textContent = String(data.amount);
            el.appendChild(amountEl);
        }

        container.appendChild(el);

        // Enter animation
        const enterAnim = el.animate([
            { transform: 'translateX(120%) scale(0.9)', opacity: 0, clipPath: 'inset(0 100% 0 0)' },
            { transform: 'translateX(0) scale(1)', opacity: 1, clipPath: 'inset(0 0 0 0)' }
        ], { duration: 600, easing: EASE, fill: 'forwards' });

        enterAnim.onfinish = () => {
            // Bits particle effect
            if (data.type === 'bits' && data.amount >= 100) {
                createParticleBurst(el, color, data.amount >= 1000 ? 30 : 12);
            }
            // Mass gift number explosion
            if (data.type === 'giftsub' && data.count && data.count > 1) {
                const amountEl = el.querySelector('.alert-amount');
                if (amountEl) amountEl.style.animation = 'numberExplode 0.5s var(--ease-spring)';
            }

            setTimeout(() => {
                // Exit animation
                const exitAnim = el.animate([
                    { transform: 'translateX(0)', opacity: 1 },
                    { transform: 'translateX(20px)', opacity: 0 }
                ], { duration: 400, easing: EASE_OUT, fill: 'forwards' });

                exitAnim.onfinish = () => {
                    el.animate([
                        { maxHeight: el.offsetHeight + 'px', marginBottom: '12px' },
                        { maxHeight: '0px', marginBottom: '0px' }
                    ], { duration: 300, easing: EASE, fill: 'forwards' }).onfinish = () => {
                        el.remove();
                        onDone?.();
                    };
                };
            }, HOLD_TIME);
        };
    }

    function createParticleBurst(parent, color, count) {
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.style.cssText = `position:absolute;width:4px;height:4px;border-radius:50%;background:${color};pointer-events:none;z-index:50;`;
            const angle = (Math.PI * 2 * i) / count;
            const dist = 40 + Math.random() * 60;
            p.style.setProperty('--px', Math.cos(angle) * dist + 'px');
            p.style.setProperty('--py', Math.sin(angle) * dist + 'px');
            p.style.left = '50%';
            p.style.top = '50%';
            p.style.animation = 'particleBurst 0.8s ease-out forwards';
            parent.appendChild(p);
            setTimeout(() => p.remove(), 1000);
        }
    }

    // ── Twitch Event Handlers ──
    function handleFollow(data) {
        push({ type: 'follow', user: data.user_name });
        Widgets.addRecentEvent('Follow', data.user_name, 'ph:user-plus');
    }
    function handleSub(data) {
        push({ type: 'sub', user: data.user_name, message: data.tier ? `Tier ${data.tier/1000}` : 'Tier 1' });
        Widgets.addRecentEvent('Sub', data.user_name, 'ph:star');
    }
    function handleResub(data) {
        push({ type: 'resub', user: data.user_name, months: data.cumulative_months });
    }
    function handleGiftSub(data) {
        push({
            type: 'giftsub',
            user: data.user_name,
            recipient: data.recipient_user_name,
            // Twitch returns the number of gifted subs as `total` (EventSub).
            count: data.total
        });
    }
    function handleBits(data) {
        push({ type: 'bits', user: data.user_name, amount: data.bits });
    }
    function handleRaid(data) {
        push({ type: 'raid', user: data.from_broadcaster_user_name, viewers: data.viewers });
        Widgets.addRecentEvent('Raid', data.from_broadcaster_user_name, 'ph:users-three');
    }
    function handleHost(data) {
        push({ type: 'host', user: data.from_broadcaster_user_name, viewers: data.viewers });
    }
    function handleRedemption(data) {
        push({ type: 'redeem', user: data.user_name, reward: data.reward.title });
    }
    function handleVip(data) {
        push({ type: 'vip', user: data.user_name });
    }
    function handleMod(data) {
        push({ type: 'mod', user: data.user_name });
    }
    function handleHypeTrainBegin(data) {
        Widgets.showHypeTrain(1, 0);
    }
    function handleHypeTrainProgress(data) {
        Widgets.showHypeTrain(data.level, data.progress);
    }
    function handleHypeTrainEnd() {
        Widgets.hideHypeTrain();
    }

    return {
        init, push,
        handleFollow, handleSub, handleResub, handleGiftSub,
        handleBits, handleRaid, handleHost, handleRedemption,
        handleVip, handleMod,
        handleHypeTrainBegin, handleHypeTrainProgress, handleHypeTrainEnd
    };
})();