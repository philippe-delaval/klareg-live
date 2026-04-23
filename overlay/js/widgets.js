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

    // ── Airport Split-Flap Clock (HH : MM : SS) ──
    // Each digit card has 4 halves: top/bottom STATIC (permanent) and top/bottom FLIP (animated).
    // When a digit changes:
    //   Phase 1 (~220ms) — top-flip (showing OLD) pivots forward/down around the seam,
    //                       revealing the static top which has been updated to NEW.
    //   Phase 2 (~220ms) — bottom-flip (showing NEW) swings down from above around the seam,
    //                       landing flat; static bottom then updates to NEW.
    // This reproduces the two-phase Solari mechanism (top flap falls, bottom flap drops).
    function initAirportClock(el) {
        if (!el) return;

        el.classList.add('airport-clock');
        el.textContent = '';

        const makeDigit = () => {
            const card = document.createElement('div');
            card.className = 'flap-digit';
            card.innerHTML = `
                <div class="flap-static flap-static-top"><span>0</span></div>
                <div class="flap-static flap-static-bottom"><span>0</span></div>
                <div class="flap-flip flap-flip-top" aria-hidden="true"><span>0</span></div>
                <div class="flap-flip flap-flip-bottom" aria-hidden="true"><span>0</span></div>
            `;
            return card;
        };
        const makeColon = () => {
            const c = document.createElement('div');
            c.className = 'flap-colon';
            c.textContent = ':';
            return c;
        };
        const makeGroup = () => {
            const g = document.createElement('div');
            g.className = 'flap-group';
            g.appendChild(makeDigit());
            g.appendChild(makeDigit());
            return g;
        };

        el.appendChild(makeGroup());      // HH
        el.appendChild(makeColon());
        el.appendChild(makeGroup());      // MM
        el.appendChild(makeColon());
        el.appendChild(makeGroup());      // SS

        const digitEls = el.querySelectorAll('.flap-digit');

        const update = () => {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            const digits = [h[0], h[1], m[0], m[1], s[0], s[1]];
            digitEls.forEach((card, i) => flapDigit(card, digits[i]));
        };

        update();
        setInterval(update, 1000);
    }

    // Drives the two-phase Solari flip for a single card.
    function flapDigit(card, newVal) {
        const topStatic = card.querySelector('.flap-static-top span');
        const bottomStatic = card.querySelector('.flap-static-bottom span');
        const topFlip = card.querySelector('.flap-flip-top');
        const bottomFlip = card.querySelector('.flap-flip-bottom');
        const topFlipSpan = topFlip.querySelector('span');
        const bottomFlipSpan = bottomFlip.querySelector('span');

        const oldVal = topStatic.textContent;
        if (oldVal === newVal) return;

        // Kill any in-flight animations on this card so fill:forwards state doesn't stack.
        topFlip.getAnimations().forEach(a => a.cancel());
        bottomFlip.getAnimations().forEach(a => a.cancel());

        // Arm flippers: top shows OLD (about to fall), bottom shows NEW (about to drop in).
        topFlipSpan.textContent = oldVal;
        bottomFlipSpan.textContent = newVal;

        // Static top is updated immediately — it sits underneath the top-flip and becomes
        // visible as the flip pivots away.
        topStatic.textContent = newVal;

        // Phase 1 — only the top flipper becomes visible here.
        // The bottom flipper stays hidden (opacity 0, edge-on via CSS default) until phase 2.
        topFlip.style.opacity = '1';
        const topAnim = topFlip.animate(
            [{ transform: 'rotateX(0deg)' }, { transform: 'rotateX(-90deg)' }],
            { duration: 220, easing: 'cubic-bezier(0.45, 0.05, 0.95, 0.25)', fill: 'forwards' }
        );

        topAnim.onfinish = () => {
            topFlip.style.opacity = '0';

            // Phase 2 — bottom flap swings down from above around the seam (top edge).
            // It starts edge-on (rotateX(90deg) via CSS default), so showing it is safe.
            bottomFlip.style.opacity = '1';
            const bottomAnim = bottomFlip.animate(
                [{ transform: 'rotateX(90deg)' }, { transform: 'rotateX(0deg)' }],
                { duration: 220, easing: 'cubic-bezier(0.25, 0.45, 0.3, 0.95)', fill: 'forwards' }
            );

            bottomAnim.onfinish = () => {
                // Update static bottom while it's hidden behind the flip, then release the flip.
                bottomStatic.textContent = newVal;
                bottomFlip.style.opacity = '0';
            };
        };
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
    function initTicker(el, messages, speedSeconds) {
        if (!el) return;
        const track = el.querySelector('.ticker-track');
        if (!track) return;
        track.textContent = '';

        // Strip emergency state if re-initializing normally
        el.classList.remove('ticker-emergency');
        const accent = el.querySelector('.ticker-accent');
        if (accent) accent.style.background = '';

        if (!messages || messages.length === 0) return;

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
                item.textContent = typeof m === 'object' ? (m.text || '') : m;
                track.appendChild(item);
            });
        };
        appendGroup();
        const sep = document.createElement('span');
        sep.className = 'ticker-sep';
        sep.textContent = '•';
        track.appendChild(sep);
        appendGroup(); // duplicate for seamless loop
        const duration = speedSeconds || Math.max(messages.length * 6, 20);
        track.style.animation = `tickerScroll ${duration}s linear infinite`;
    }

    function setTickerEmergency(message, color) {
        const safeColor = /^#[0-9A-Fa-f]{6}$/.test(color || '') ? color : '#FF4444';
        document.querySelectorAll('.ticker-band').forEach(band => {
            band.classList.add('ticker-emergency');
            const accent = band.querySelector('.ticker-accent');
            if (accent) accent.style.background = safeColor;
            const track = band.querySelector('.ticker-track');
            if (!track) return;

            // Cancel any previous animation
            if (track._emergencyAnim) { track._emergencyAnim.cancel(); track._emergencyAnim = null; }
            track.style.animation = 'none';
            track.style.transform = '';
            track.innerHTML = '';

            // Estimate copies for first half to cover at least 1.5× band width
            const approxPx = message.length * 13 + 98; // text + sep (margin 24px×2 + chars)
            const bandWidth = (band.querySelector('.ticker-content') || band).clientWidth || 1920;
            const copies = Math.max(6, Math.ceil((bandWidth * 1.5) / approxPx));

            // Build first half
            for (let i = 0; i < copies; i++) {
                const item = document.createElement('span');
                item.className = 'ticker-item ticker-emergency-item';
                item.textContent = message;
                item.style.fontWeight = '600';
                item.style.color = '#fff';
                track.appendChild(item);
                const sep = document.createElement('span');
                sep.className = 'ticker-sep';
                sep.textContent = '⚠';
                sep.style.color = safeColor;
                track.appendChild(sep);
            }

            // Measure exact first half width after reflow
            void track.offsetWidth;
            const halfWidth = track.getBoundingClientRect().width;

            // Clone first half exactly — guarantees pixel-perfect equality
            Array.from(track.children).forEach(child => track.appendChild(child.cloneNode(true)));

            void track.offsetWidth;

            // Web Animations API with exact pixel value — no percentage rounding issues
            const duration = Math.max(halfWidth / 70, 8) * 1000; // ms
            track._emergencyAnim = track.animate(
                [{ transform: 'translateX(0px)' }, { transform: `translateX(-${halfWidth}px)` }],
                { duration, iterations: Infinity, easing: 'linear' }
            );
        });
    }

    function clearTickerEmergency() {
        document.querySelectorAll('.ticker-band').forEach(band => {
            band.classList.remove('ticker-emergency');
            const accent = band.querySelector('.ticker-accent');
            if (accent) accent.style.background = '';
        });
        if (typeof CONFIG !== 'undefined' && CONFIG.tickerMessages) {
            document.querySelectorAll('.ticker-band').forEach(band => {
                initTicker(band, CONFIG.tickerMessages, CONFIG.tickerSpeed);
            });
        }
    }

    function injectTickerMessage(message, _icon) {
        if (!message) return;
        document.querySelectorAll('.ticker-track').forEach(track => {
            const item = document.createElement('span');
            item.className = 'ticker-item ticker-priority-item';
            item.textContent = '★ ' + message;
            const sep = document.createElement('span');
            sep.className = 'ticker-sep';
            sep.textContent = '•';
            track.prepend(sep);
            track.prepend(item);
        });
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
        initClock, initAirportClock, initLiveBadge, initViewerCount, updateViewerCount,
        initTicker, setTickerEmergency, clearTickerEmergency, injectTickerMessage,
        initGoalBar, updateGoalBar,
        addRecentEvent, renderRecentEvents,
        showHypeTrain, hideHypeTrain, updateHypeTrain,
        initNowPlaying, initCountdown, initRadialTimer,
        setOnline,
        animateCountUp, initViewportScale, flipDigit, flapDigit
    };
})();