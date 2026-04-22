// ═══════════════════════════════════════════════
// KLAREG OVERLAY — Reverb WebSocket Client
// Connects to Laravel Reverb for real-time overlay updates + Twitch events
// ═══════════════════════════════════════════════

const ReverbClient = (() => {
    let echo = null;
    let connected = false;

    // ── Configuration ──
    // Defaults used only if /api/overlay/reverb-config is unreachable.
    // No secrets here — the server holds REVERB_APP_SECRET.
    const REVERB_DEFAULTS = {
        key: '',
        host: window.location.hostname,
        port: 8080,
        scheme: window.location.protocol === 'https:' ? 'https' : 'http',
    };

    async function fetchReverbConfig() {
        try {
            const r = await fetch(getApiBase() + '/reverb-config', { cache: 'no-store' });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const data = await r.json();
            return {
                key: data.key || REVERB_DEFAULTS.key,
                host: data.host || REVERB_DEFAULTS.host,
                port: Number(data.port) || REVERB_DEFAULTS.port,
                scheme: data.scheme || REVERB_DEFAULTS.scheme,
            };
        } catch (e) {
            console.warn('[ReverbClient] Could not fetch reverb-config, using defaults:', e.message);
            return { ...REVERB_DEFAULTS };
        }
    }

    async function init(configOverride = {}) {
        const fetched = await fetchReverbConfig();
        const cfg = { ...fetched, ...configOverride };

        // Check if Laravel Echo is available
        if (typeof Echo === 'undefined' && typeof require === 'function') {
            try {
                const EchoModule = require('laravel-echo');
                const Pusher = require('pusher-js');
                window.Pusher = Pusher;
                window.Echo = new EchoModule.default({
                    broadcaster: 'reverb',
                    key: cfg.key,
                    wsHost: cfg.host,
                    wsPort: cfg.port,
                    wssPort: cfg.port,
                    forceTLS: cfg.scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                });
                echo = window.Echo;
            } catch (e) {
                console.warn('[ReverbClient] Could not load Echo/Pusher via require:', e);
            }
        }

        if (typeof Echo !== 'undefined') {
            echo = Echo;
        }

        if (!echo) {
            console.warn('[ReverbClient] Laravel Echo not available. Falling back to manual WebSocket.');
            connectManual(cfg);
            return;
        }

        // Connect via Laravel Echo — subscribe to all channels
        echo.channel('broadcast-overlay')
            .listen('.overlay-update', (data) => {
                handleUpdate(data);
            });

        echo.channel('twitch-events')
            .listen('.twitch-event', (data) => {
                handleTwitchEvent(data);
            });

        echo.channel('twitch-chat')
            .listen('.twitch-chat-message', (data) => {
                handleChatMessage(data);
            });

        echo.connector.pusher.connection.bind('connected', () => {
            connected = true;
            console.log('[ReverbClient] Connected to Reverb server');
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            connected = false;
            console.log('[ReverbClient] Disconnected from Reverb server');
        });

        echo.connector.pusher.connection.bind('error', (err) => {
            console.error('[ReverbClient] Connection error:', err);
        });

        connected = true;
    }

    // ── Manual WebSocket fallback ──
    let ws = null;
    let reconnectAttempts = 0;
    const MAX_RECONNECT = 10;
    const RECONNECT_DELAY = 3000;

    function connectManual(cfg) {
        const protocol = cfg.scheme === 'https' ? 'wss' : 'ws';
        const url = `${protocol}://${cfg.host}:${cfg.port}/app/${cfg.key}?protocol=7`;

        console.log('[ReverbClient] Connecting manually to:', url);

        try {
            ws = new WebSocket(url);

            ws.onopen = () => {
                connected = true;
                reconnectAttempts = 0;
                console.log('[ReverbClient] WebSocket connected');

                // Subscribe to all channels
                const channels = ['broadcast-overlay', 'twitch-events', 'twitch-chat'];
                channels.forEach(ch => {
                    ws.send(JSON.stringify({
                        event: 'pusher:subscribe',
                        data: { channel: ch }
                    }));
                });

                // Send ping interval
                setInterval(() => {
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({ event: 'pusher:ping', data: {} }));
                    }
                }, 30000);
            };

            ws.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data);

                    // Handle subscription confirmation
                    if (msg.event === 'pusher_internal:subscription_succeeded') {
                        console.log(`[ReverbClient] Subscribed to: ${msg.channel}`);
                        return;
                    }

                    // Handle pong
                    if (msg.event === 'pusher:pong') return;

                    // Route by channel
                    let data = msg.data;
                    if (typeof data === 'string') {
                        try { data = JSON.parse(data); } catch (e) { /* not JSON */ }
                    }

                    if (msg.channel === 'broadcast-overlay' || msg.event === 'overlay-update') {
                        handleUpdate(data);
                    } else if (msg.channel === 'twitch-events' || msg.event === 'twitch-event') {
                        handleTwitchEvent(data);
                    } else if (msg.channel === 'twitch-chat' || msg.event === 'twitch-chat-message') {
                        handleChatMessage(data);
                    }
                } catch (e) {
                    console.warn('[ReverbClient] Failed to parse message:', e);
                }
            };

            ws.onclose = () => {
                connected = false;
                console.log('[ReverbClient] WebSocket closed');
                if (reconnectAttempts < MAX_RECONNECT) {
                    reconnectAttempts++;
                    const delay = RECONNECT_DELAY * reconnectAttempts;
                    console.log(`[ReverbClient] Reconnecting in ${delay}ms (attempt ${reconnectAttempts})`);
                    setTimeout(() => connectManual(cfg), delay);
                }
            };

            ws.onerror = (err) => {
                console.error('[ReverbClient] WebSocket error:', err);
            };
        } catch (e) {
            console.error('[ReverbClient] Failed to create WebSocket:', e);
        }
    }

    // ── Handle overlay settings updates ──
    function isSafeHttpUrl(u) {
        try {
            const url = new URL(u, window.location.href);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch { return false; }
    }

    function renderScheduleFromConfig() {
        if (typeof CONFIG === 'undefined' || !Array.isArray(CONFIG.schedule)) return;
        const schedEl = document.getElementById('schedule-items');
        if (!schedEl) return;

        schedEl.textContent = '';
        CONFIG.schedule.forEach(s => {
            const item = document.createElement('div');
            item.className = 'schedule-item' + (s.active ? '' : ' inactive');

            const time = document.createElement('span');
            time.className = 'schedule-time';
            time.textContent = s.time;
            item.appendChild(time);

            const divider = document.createElement('div');
            divider.className = 'schedule-divider';
            item.appendChild(divider);

            const label = document.createElement('span');
            label.className = 'schedule-label';
            label.textContent = s.label;
            item.appendChild(label);

            schedEl.appendChild(item);
        });
    }

    function renderSocialLinksFromConfig() {
        if (typeof CONFIG === 'undefined' || !Array.isArray(CONFIG.socials)) return;
        const socialEl = document.getElementById('social-links');
        if (!socialEl) return;

        const iconMap = {
            twitch: 'ph:twitch-logo',
            twitter: 'ph:x-logo',
            youtube: 'ph:youtube-logo',
            discord: 'ph:discord-logo'
        };

        socialEl.textContent = '';
        CONFIG.socials.forEach(s => {
            const a = document.createElement('a');
            a.className = 'social-link';
            a.href = isSafeHttpUrl(s.url) ? s.url : '#';
            a.target = '_blank';
            a.rel = 'noopener noreferrer';

            const icon = document.createElement('iconify-icon');
            icon.setAttribute('icon', iconMap[s.platform] || 'ph:link');
            icon.setAttribute('width', '14');
            a.appendChild(icon);

            a.appendChild(document.createTextNode(' ' + (s.label ?? '')));
            socialEl.appendChild(a);
        });
    }

    function renderTickerFromConfig() {
        if (typeof CONFIG === 'undefined' || !Array.isArray(CONFIG.tickerMessages)) return;
        if (typeof Widgets === 'undefined' || !Widgets.initTicker) return;

        // Multiple scenes contain a .ticker-band; update all of them safely.
        document.querySelectorAll('.ticker-band').forEach(el => {
            Widgets.initTicker(el, CONFIG.tickerMessages);
        });
    }

    function renderNowPlayingFromConfig() {
        if (typeof CONFIG === 'undefined') return;
        if (typeof Widgets === 'undefined' || !Widgets.initNowPlaying) return;

        const track = CONFIG.nowPlaying?.track;
        const artist = CONFIG.nowPlaying?.artist;
        if (!track && !artist) return;

        document.querySelectorAll('.now-playing').forEach(el => {
            Widgets.initNowPlaying(el, track, artist);
        });
    }

    function renderGoalBarFromConfig() {
        if (typeof CONFIG === 'undefined') return;
        if (typeof Widgets === 'undefined' || !Widgets.initGoalBar) return;

        const goalEl = document.querySelector('.goal-bar');
        if (!goalEl) return;

        const current = CONFIG.subGoal?.current ?? 0;
        const target = CONFIG.subGoal?.target ?? 0;
        const label = CONFIG.subGoal?.label ?? '';

        Widgets.initGoalBar(goalEl, current, target, label);
    }

    function handleUpdate(data) {
        console.log('[ReverbClient] Overlay update:', data);

        if (data.type === 'alert-test' || data.source === 'admin-test') {
            if (typeof Alerts !== 'undefined') {
                Alerts.push(data);
            }
            return;
        }

        if (data.channel_name !== undefined) {
            if (typeof CONFIG !== 'undefined') {
                CONFIG.channel = data.channel_name;
            }
        }
        if (data.stream_title) {
            if (typeof CONFIG !== 'undefined') CONFIG.streamTitle = data.stream_title;
            document.querySelectorAll('.stream-title').forEach(el => {
                el.textContent = data.stream_title;
            });
        }
        if (data.stream_category) {
            if (typeof CONFIG !== 'undefined') CONFIG.streamCategory = data.stream_category;
            document.querySelectorAll('.stream-category').forEach(el => {
                el.textContent = data.stream_category;
            });
        }
        if (data.accent_color) {
            document.documentElement.style.setProperty('--c-accent', data.accent_color);
            document.documentElement.style.setProperty('--c-accent-rgb', hexToRGB(data.accent_color));
        }
        // Subs / goals
        const subGoalChanged = data.sub_goal !== undefined || data.sub_current !== undefined;
        if (subGoalChanged && typeof CONFIG !== 'undefined') {
            if (data.sub_goal !== undefined) CONFIG.subGoal.target = data.sub_goal;
            if (data.sub_current !== undefined) CONFIG.subGoal.current = data.sub_current;
        }
        if (subGoalChanged) renderGoalBarFromConfig();

        // Ticker messages (top/bottom)
        if (data.ticker_messages && typeof CONFIG !== 'undefined') {
            CONFIG.tickerMessages = data.ticker_messages;
            renderTickerFromConfig();
        }

        // Now playing
        const nowPlayingChanged = data.now_playing_track !== undefined || data.now_playing_artist !== undefined;
        if (nowPlayingChanged && typeof CONFIG !== 'undefined') {
            if (data.now_playing_track !== undefined) CONFIG.nowPlaying.track = data.now_playing_track;
            if (data.now_playing_artist !== undefined) CONFIG.nowPlaying.artist = data.now_playing_artist;
        }
        if (nowPlayingChanged) renderNowPlayingFromConfig();

        // Countdown (Starting Soon)
        const countdownChanged = data.countdown_minutes !== undefined || data.countdown_seconds !== undefined;
        if (countdownChanged && typeof CONFIG !== 'undefined') {
            if (data.countdown_minutes !== undefined) CONFIG.countdownMinutes = data.countdown_minutes;
            if (data.countdown_seconds !== undefined) CONFIG.countdownSeconds = data.countdown_seconds;
        }
        if (countdownChanged && typeof Widgets !== 'undefined' && Widgets.initCountdown) {
            const frame = document.getElementById('obs-frame');
            if (frame) Widgets.initCountdown(frame, CONFIG.countdownMinutes, CONFIG.countdownSeconds);
        }

        // BRB radial timer + message
        const brbDurationChanged = data.brb_duration_minutes !== undefined;
        if (brbDurationChanged && typeof CONFIG !== 'undefined') {
            CONFIG.brbDurationMinutes = data.brb_duration_minutes;
        }
        if (brbDurationChanged && typeof Widgets !== 'undefined' && Widgets.initRadialTimer) {
            const radialEl = document.getElementById('radial-timer');
            if (radialEl) Widgets.initRadialTimer(radialEl, CONFIG.brbDurationMinutes);
        }

        if (data.brb_message !== undefined) {
            if (typeof CONFIG !== 'undefined') CONFIG.brbMessage = data.brb_message;
            const brbTitleEl = document.getElementById('brb-title');
            if (brbTitleEl) brbTitleEl.textContent = data.brb_message;
        }

        if (data.starting_title !== undefined) {
            if (typeof CONFIG !== 'undefined') CONFIG.startingTitle = data.starting_title;
            const startingTitleEl = document.getElementById('starting-title');
            if (startingTitleEl) startingTitleEl.textContent = data.starting_title;
        }

        // Social links
        if (data.socials && typeof CONFIG !== 'undefined') {
            CONFIG.socials = data.socials;
            renderSocialLinksFromConfig();
        }

        // Next stream (Ending)
        if (data.next_stream !== undefined) {
            if (typeof CONFIG !== 'undefined') CONFIG.nextStream = data.next_stream;
            const nextEl = document.getElementById('next-stream-text');
            if (nextEl) nextEl.textContent = data.next_stream;
        }
    }

    // ── Handle Twitch events (alerts) ──
    function handleTwitchEvent(data) {
        console.log('[ReverbClient] Twitch event:', data);
        if (typeof Alerts === 'undefined') return;

        const type = data.type;
        const payload = data.payload;

        switch (type) {
            case 'channel.follow':
                if (Alerts.handleFollow) Alerts.handleFollow(payload);
                break;
            case 'channel.subscribe':
                if (Alerts.handleSub) Alerts.handleSub(payload);
                break;
            case 'channel.subscription.gift':
                if (Alerts.handleGiftSub) Alerts.handleGiftSub(payload);
                break;
            case 'channel.subscription.message':
                if (Alerts.handleResub) Alerts.handleResub(payload);
                break;
            case 'channel.cheer':
                if (Alerts.handleBits) Alerts.handleBits(payload);
                break;
            case 'channel.raid':
                if (Alerts.handleRaid) Alerts.handleRaid(payload);
                break;
            case 'channel.channel_points_custom_reward_redemption.add':
                if (Alerts.handleRedemption) Alerts.handleRedemption(payload);
                break;
            case 'channel.hype_train.begin':
                if (Alerts.handleHypeTrainBegin) Alerts.handleHypeTrainBegin(payload);
                break;
            case 'channel.hype_train.progress':
                if (Alerts.handleHypeTrainProgress) Alerts.handleHypeTrainProgress(payload);
                break;
            case 'channel.hype_train.end':
                if (Alerts.handleHypeTrainEnd) Alerts.handleHypeTrainEnd(payload);
                break;
            case 'stream.online':
                if (typeof Widgets !== 'undefined' && Widgets.setOnline) Widgets.setOnline(true);
                break;
            case 'stream.offline':
                if (typeof Widgets !== 'undefined' && Widgets.setOnline) Widgets.setOnline(false);
                break;
        }
    }

    // ── Handle chat messages ──
    function handleChatMessage(data) {
        console.log('[ReverbClient] Chat message:', data);
        if (typeof Chat !== 'undefined') {
            Chat.renderMessage(data);
        }
    }

    function hexToRGB(hex) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `${r}, ${g}, ${b}`;
    }

    // ── API: Fetch initial settings from backend ──
    // Use meta tag override (<meta name="api-base" content="...">) if present,
    // otherwise fall back to the document origin so deployments "just work".
    function getApiBase() {
        const meta = document.querySelector('meta[name="api-base"]');
        if (meta && meta.content) return meta.content.replace(/\/$/, '');
        return window.location.origin + '/api/overlay';
    }

    function fetchSettings() {
        const API_BASE = getApiBase();
        fetch(`${API_BASE}/settings`)
            .then(r => r.json())
            .then(data => {
                console.log('[ReverbClient] Fetched settings from API');
                handleUpdate(data);
            })
            .catch(err => {
                console.warn('[ReverbClient] Could not fetch settings from API:', err.message);
            });

        fetch(`${API_BASE}/schedule`)
            .then(r => r.json())
            .then(data => {
                if (typeof CONFIG !== 'undefined' && Array.isArray(data)) {
                    CONFIG.schedule = data.map(item => ({
                        time: item.time,
                        label: item.label,
                        active: item.is_active
                    }));
                    renderScheduleFromConfig();
                }
            })
            .catch(err => {
                console.warn('[ReverbClient] Could not fetch schedule from API:', err.message);
            });
    }

    function isConnected() {
        return connected;
    }

    function disconnect() {
        if (ws) ws.close();
        connected = false;
    }

    return { init, fetchSettings, isConnected, disconnect };
})();