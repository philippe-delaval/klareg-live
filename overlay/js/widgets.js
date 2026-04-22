// ═══════════════════════════════════════════════
// KLAREG OVERLAY — Widgets Engine
// Clock, Ticker, Goal Bar, Recent Events, Hype Train, Music, Viewer Count
// ═══════════════════════════════════════════════

const Widgets = (() => {
    const EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';
    const EASE_OUT = 'cubic-bezier(0.33, 1, 0.68, 1)';

    // ── Clock (flip digits) ──
    function initClock(el) {
        if (!el) return;
        const update = () => {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            const digits = [h[0], h[1], m[0], m[1], s[0], s[1]];
            const spans = el.querySelectorAll('.clock-digit');
            spans.forEach((span, i) => {
                if (span.innerText !== digits[i]) {
                    flipDigit(span, digits[i]);
                }
            });
        };
        update();
        setInterval(update, 1000);
    }

    function flipDigit(el, newVal) {
        const old = el.innerText;
        el.innerHTML = `<span style="position:absolute;width:100%;top:0">${old}</span><span style="position:absolute;width:100%;top:100%">${newVal}</span><span style="visibility:hidden">${newVal}</span>`;
        const [oldS, newS] = el.querySelectorAll('span');
        oldS.animate([{ transform: 'translateY(0)', opacity: 1 }, { transform: 'translateY(-100%)', opacity: 0 }], { duration: 300, easing: EASE, fill: 'forwards' });
        newS.animate([{ transform: 'translateY(100%)', opacity: 0 }, { transform: 'translateY(0)', opacity: 1 }], { duration: 300, easing: EASE, fill: 'forwards' }).onfinish = () => { el.innerText = newVal; };
    }

    // ── Live Badge + Uptime ──
    let streamStartTime = null;
    function initLiveBadge(el) {
        if (!el) return;
        streamStartTime = Date.now();
        const uptimeEl = el.querySelector('.uptime');
        if (uptimeEl) {
            const tick = () => {
                if (!streamStartTime) return;
                const diff = Date.now() - streamStartTime;
                const h = Math.floor(diff / 3600000);
                const m = Math.floor((diff % 3600000) / 60000);
                // Display minutes only (no seconds) for the LIVE badge.
                // Format matches "15h34" (no :SS).
                uptimeEl.textContent = `${String(h).padStart(2, '0')}h${String(m).padStart(2, '0')}`;
            };
            tick();
            setInterval(tick, 1000);
        }
    }

    // Called by Reverb when stream goes online/offline.
    // We keep the uptime interval running (if initLiveBadge was called),
    // but freeze it when offline.
    function setOnline(isOnline) {
        streamStartTime = isOnline ? Date.now() : null;
        const badge = document.querySelector('.live-badge');
        if (badge) {
            badge.style.opacity = isOnline ? '1' : '0.5';
        }
    }

    // ── Viewer Count ──
    let viewerCount = 0;
    function initViewerCount(el, initial = 0) {
        if (!el) return;
        viewerCount = initial;
        const numEl = el.querySelector('.viewer-number');
        if (numEl) numEl.textContent = viewerCount.toLocaleString();
    }
    function updateViewerCount(newCount) {
        viewerCount = newCount;
        document.querySelectorAll('.viewer-number').forEach(el => {
            const old = el.innerText;
            const newTxt = newCount.toLocaleString();
            if (old !== newTxt) {
                el.animate([{ transform: 'translateY(0)', opacity: 1 }, { transform: 'translateY(-8px)', opacity: 0 }], { duration: 200, easing: EASE_OUT }).onfinish = () => {
                    el.textContent = newTxt;
                    el.animate([{ transform: 'translateY(8px)', opacity: 0 }, { transform: 'translateY(0)', opacity: 1 }], { duration: 200, easing: EASE });
                };
            }
        });
    }

    // ── Ticker Band ──
    function initTicker(el, messages) {
        if (!el) return;
        const track = el.querySelector('.ticker-track');
        if (!track) return;
        track.textContent = '';
        const appendGroup = () => {
            messages.forEach((m, i) => {
                if (i > 0) {
                    const sep = document.createElement('span');
                    sep.className = 'ticker-sep';
                    sep.textContent = '•';
                    track.appendChild(sep);
                }
                const item = document.createElement('span');
                item.className = 'ticker-item';
                item.textContent = m;
                track.appendChild(item);
            });
        };
        appendGroup();
        const sep = document.createElement('span');
        sep.className = 'ticker-sep';
        sep.textContent = '•';
        track.appendChild(sep);
        appendGroup(); // duplicate for seamless loop
        track.style.animation = `tickerScroll ${Math.max(messages.length * 6, 20)}s linear infinite`;
    }

    // ── Goal Bar ──
    function initGoalBar(el, current, target, label) {
        if (!el) return;
        const labelEl = el.querySelector('.goal-label');
        const currentEl = el.querySelector('.goal-current');
        const targetEl = el.querySelector('.goal-target');
        const fillEl = el.querySelector('.goal-fill');
        if (labelEl) labelEl.textContent = label;
        if (currentEl) currentEl.textContent = current;
        if (targetEl) targetEl.textContent = target;
        if (fillEl) {
            const pct = Math.min((current / target) * 100, 100);
            setTimeout(() => { fillEl.style.width = pct + '%'; }, 100);
        }
    }
    function updateGoalBar(current) {
        document.querySelectorAll('.goal-bar').forEach(el => {
            const target = parseInt(el.querySelector('.goal-target')?.textContent || 1);
            const fill = el.querySelector('.goal-fill');
            const cur = el.querySelector('.goal-current');
            if (cur) cur.textContent = current;
            if (fill) fill.style.width = Math.min((current / target) * 100, 100) + '%';
        });
    }

    // ── Recent Events Strip ──
    const recentEvents = [];
    function addRecentEvent(type, username, icon = 'ph:user') {
        recentEvents.unshift({ type, username, icon, time: Date.now() });
        if (recentEvents.length > 3) recentEvents.pop();
        renderRecentEvents();
    }
    function renderRecentEvents() {
        document.querySelectorAll('.recent-events').forEach(el => {
            el.textContent = '';
            recentEvents.forEach(ev => {
                const wrap = document.createElement('div');
                wrap.className = 'recent-event';

                const iconSpan = document.createElement('span');
                iconSpan.className = 'event-icon';
                const icon = document.createElement('iconify-icon');
                icon.setAttribute('icon', ev.icon);
                icon.setAttribute('width', '14');
                iconSpan.appendChild(icon);
                wrap.appendChild(iconSpan);

                const userSpan = document.createElement('span');
                userSpan.className = 'event-user';
                userSpan.textContent = ev.username;
                wrap.appendChild(userSpan);

                const typeSpan = document.createElement('span');
                typeSpan.className = 'event-type';
                typeSpan.textContent = ev.type;
                wrap.appendChild(typeSpan);

                el.appendChild(wrap);
            });
        });
    }

    // ── Hype Train ──
    let hypeLevel = 0;
    let hypeProgress = 0;
    function showHypeTrain(level, progress) {
        hypeLevel = level;
        hypeProgress = progress;
        document.querySelectorAll('.hype-train').forEach(el => {
            el.classList.remove('hidden');
            const lvl = el.querySelector('.hype-level');
            const fill = el.querySelector('.hype-fill');
            if (lvl) lvl.textContent = `LV.${level}`;
            if (fill) fill.style.width = (progress * 100) + '%';
        });
    }
    function hideHypeTrain() {
        document.querySelectorAll('.hype-train').forEach(el => el.classList.add('hidden'));
    }
    function updateHypeTrain(progress) {
        hypeProgress = progress;
        document.querySelectorAll('.hype-fill').forEach(el => {
            el.style.width = (progress * 100) + '%';
        });
    }

    // ── Now Playing / Music ──
    function initNowPlaying(el, track, artist) {
        if (!el) return;
        const trackEl = el.querySelector('.np-track');
        if (trackEl) {
            trackEl.textContent = `${track}  \u2022  ${artist}`;
            trackEl.style.animation = 'musicPingPong 15s ease-in-out infinite';
        }
    }

    // ── Countdown (Starting Soon / BRB) ──
    let countdownInterval = null;
    function initCountdown(el, minutes, seconds, onEnd) {
        if (!el) return;
        clearInterval(countdownInterval);
        let total = minutes * 60 + seconds;
        const minT = el.querySelector('#min-tens, .min-tens');
        const minO = el.querySelector('#min-ones, .min-ones');
        const secT = el.querySelector('#sec-tens, .sec-tens');
        const secO = el.querySelector('#sec-ones, .sec-ones');

        const update = () => {
            if (total < 0) { clearInterval(countdownInterval); if (onEnd) onEnd(); return; }
            const m = Math.floor(total / 60);
            const s = total % 60;
            const mStr = String(m).padStart(2, '0');
            const sStr = String(s).padStart(2, '0');
            [minT, minO, secT, secO].forEach((d, i) => {
                const val = [mStr[0], mStr[1], sStr[0], sStr[1]][i];
                if (d && d.innerText !== val) flipDigit(d, val);
            });
            // Pulse last 10s, urgent last 3s
            const digits = el.querySelectorAll('.countdown-digit');
            if (total <= 10 && total > 3) digits.forEach(d => d.style.animation = 'countdownPulse 1s ease-in-out infinite');
            else if (total <= 3 && total >= 0) digits.forEach(d => d.style.animation = 'countdownUrgent 0.5s ease-in-out infinite');
            else digits.forEach(d => d.style.animation = 'none');
            total--;
        };
        update();
        countdownInterval = setInterval(update, 1000);
    }

    // ── Radial Timer (BRB) ──
    let radialInterval = null;
    function initRadialTimer(el, minutes) {
        if (!el) return;
        clearInterval(radialInterval);
        const circumference = 2 * Math.PI * 108; // radius 108
        let remaining = minutes * 60;
        const circle = el.querySelector('.timer-fill');
        const text = el.querySelector('.timer-text');
        if (circle) {
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = 0;
        }
        const tick = () => {
            if (remaining < 0) { clearInterval(radialInterval); return; }
            const pct = 1 - (remaining / (minutes * 60));
            if (circle) circle.style.strokeDashoffset = pct * circumference;
            if (text) {
                const m = Math.floor(remaining / 60);
                const s = remaining % 60;
                text.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            }
            remaining--;
        };
        tick();
        radialInterval = setInterval(tick, 1000);
    }

    // ── Count-Up Animation (Ending scene) ──
    function animateCountUp(el, target, duration = 2000) {
        if (!el) return;
        let start = 0;
        const step = (timestamp) => {
            if (!start) start = timestamp;
            const progress = Math.min((timestamp - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            el.textContent = Math.floor(eased * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }

    // ── Scale to viewport ──
    function initViewportScale(frameEl) {
        const adjust = () => {
            const sx = window.innerWidth / 1920;
            const sy = window.innerHeight / 1080;
            const s = Math.min(sx, sy, 1);
            frameEl.style.transform = `scale(${s})`;
            frameEl.style.transformOrigin = 'top left';
            if (s < 1) {
                frameEl.style.marginLeft = ((window.innerWidth - 1920 * s) / 2) + 'px';
                frameEl.style.marginTop = ((window.innerHeight - 1080 * s) / 2) + 'px';
            } else {
                frameEl.style.marginLeft = ((window.innerWidth - 1920) / 2) + 'px';
                frameEl.style.marginTop = ((window.innerHeight - 1080) / 2) + 'px';
            }
        };
        window.addEventListener('resize', adjust);
        adjust();
    }

    return {
        initClock, initLiveBadge, initViewerCount, updateViewerCount,
        initTicker, initGoalBar, updateGoalBar,
        addRecentEvent, renderRecentEvents,
        showHypeTrain, hideHypeTrain, updateHypeTrain,
        initNowPlaying, initCountdown, initRadialTimer,
        setOnline,
        animateCountUp, initViewportScale, flipDigit
    };
})();