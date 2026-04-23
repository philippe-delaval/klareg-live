// ═══════════════════════════════════════════════
// KLAREG — Starting Soon animated backgrounds
// Switches between 5 variants based on [data-bg-style]
// ═══════════════════════════════════════════════

const StartingSoonBg = (() => {
    const VALID = ['none', 'aurora', 'warp', 'constellation', 'mission', 'synthwave'];
    let currentStyle = null;
    let constellationRaf = null;
    let missionIntervals = [];

    function apply(style) {
        if (!VALID.includes(style)) style = 'none';
        if (style === currentStyle) return;

        // Teardown previous animation
        teardown();

        const frame = document.getElementById('obs-frame');
        if (!frame) return;
        frame.setAttribute('data-bg-style', style);
        currentStyle = style;

        switch (style) {
            case 'none':          /* no animation */ break;
            case 'aurora':        initAurora(); break;
            case 'warp':          initWarp(); break;
            case 'constellation': initConstellation(); break;
            case 'mission':       initMission(); break;
            case 'synthwave':     /* pure CSS */ break;
        }
    }

    function teardown() {
        if (constellationRaf !== null) {
            cancelAnimationFrame(constellationRaf);
            constellationRaf = null;
        }
        missionIntervals.forEach(clearInterval);
        missionIntervals = [];
    }

    // ───── AURORA ─────
    function initAurora() {
        const container = document.querySelector('.aurora-particles');
        if (!container) return;
        container.textContent = '';
        const count = 28;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = 1 + Math.random() * 2.5;
            const duration = 12 + Math.random() * 18;
            const delay = -Math.random() * duration;
            const drift = (Math.random() * 120 - 60) + 'px';
            p.style.left = (Math.random() * 100) + '%';
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            p.style.animationDuration = duration + 's';
            p.style.animationDelay = delay + 's';
            p.style.setProperty('--drift', drift);
            if (Math.random() < 0.25) {
                p.style.background = '#a78bff';
                p.style.boxShadow = '0 0 6px rgba(167,139,255,0.7)';
            }
            container.appendChild(p);
        }
    }

    // ───── WARP ─────
    function initWarp() {
        const container = document.querySelector('.warp-stars');
        if (!container) return;
        container.textContent = '';
        const count = 140;
        for (let i = 0; i < count; i++) {
            const s = document.createElement('div');
            s.className = 'warp-star';
            const angle = Math.random() * 360;
            const duration = 1.2 + Math.random() * 2.2;
            const delay = -Math.random() * duration;
            const length = 60 + Math.random() * 120;
            s.style.width = length + 'px';
            s.style.setProperty('--angle', angle + 'deg');
            s.style.animationDuration = duration + 's';
            s.style.animationDelay = delay + 's';
            if (Math.random() < 0.2) {
                s.style.background = 'linear-gradient(90deg, transparent, #fff 40%, #a78bff 100%)';
                s.style.boxShadow = '0 0 8px #a78bff';
            }
            container.appendChild(s);
        }
    }

    // ───── CONSTELLATION ─────
    function initConstellation() {
        const canvas = document.getElementById('constellation-canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        function resize() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }
        resize();
        window.addEventListener('resize', resize);

        // Read the current accent color from CSS vars (theme-aware).
        // Re-read on every frame so live theme/accent changes apply instantly.
        function accentRgb() {
            const raw = getComputedStyle(document.documentElement)
                .getPropertyValue('--c-accent-rgb').trim();
            // Fallback to default blue if the var is missing/malformed
            if (!raw || !/^\d+\s*,\s*\d+\s*,\s*\d+$/.test(raw)) return '91,127,255';
            return raw;
        }

        const W = () => canvas.getBoundingClientRect().width;
        const H = () => canvas.getBoundingClientRect().height;
        const NODE_COUNT = 70;
        const LINK_DIST = 160;
        const nodes = [];
        for (let i = 0; i < NODE_COUNT; i++) {
            nodes.push({
                x: Math.random() * W(),
                y: Math.random() * H(),
                vx: (Math.random() - 0.5) * 0.35,
                vy: (Math.random() - 0.5) * 0.35,
                r: 1.2 + Math.random() * 1.8,
                pulse: Math.random() * Math.PI * 2,
            });
        }

        function draw() {
            const w = W(), h = H();
            ctx.clearRect(0, 0, w, h);

            const rgb = accentRgb();

            // Update nodes
            for (const n of nodes) {
                n.x += n.vx; n.y += n.vy;
                if (n.x < 0 || n.x > w) n.vx *= -1;
                if (n.y < 0 || n.y > h) n.vy *= -1;
                n.pulse += 0.03;
            }

            // Links
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const a = nodes[i], b = nodes[j];
                    const dx = a.x - b.x, dy = a.y - b.y;
                    const d = Math.sqrt(dx * dx + dy * dy);
                    if (d < LINK_DIST) {
                        const alpha = (1 - d / LINK_DIST) * 0.35;
                        ctx.strokeStyle = `rgba(${rgb},${alpha.toFixed(3)})`;
                        ctx.lineWidth = 0.6;
                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.stroke();
                    }
                }
            }

            // Nodes
            ctx.fillStyle = `rgba(${rgb},0.9)`;
            ctx.shadowColor = `rgba(${rgb},0.8)`;
            ctx.shadowBlur = 8;
            for (const n of nodes) {
                const pulseScale = 1 + 0.4 * Math.sin(n.pulse);
                const r = n.r * pulseScale;
                ctx.beginPath();
                ctx.arc(n.x, n.y, r, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.shadowBlur = 0;

            constellationRaf = requestAnimationFrame(draw);
        }
        draw();
    }

    // ───── MISSION CONTROL ─────
    function initMission() {
        const tl = document.getElementById('telemetry-tl');
        const tr = document.getElementById('telemetry-tr');
        const bl = document.getElementById('telemetry-bl');
        const br = document.getElementById('telemetry-br');

        const randHex = (n) => Array.from({ length: n }, () =>
            Math.floor(Math.random() * 16).toString(16).toUpperCase()).join('');
        const randFloat = (min, max, d = 2) =>
            (min + Math.random() * (max - min)).toFixed(d);

        function updateTL() {
            if (!tl) return;
            const lines = [
                `SYS.STATUS.......... [ READY ]`,
                `NODE.ID............. 0x${randHex(8)}`,
                `UPLINK.............. ${randFloat(95, 100, 1)}%`,
                `LATENCY............. ${randFloat(8, 24, 1)}ms`,
                `CHANNEL............. KLAREG.LIVE`,
                `ENC................. AES-256-GCM`,
            ];
            tl.textContent = lines.join('\n');
        }
        function updateTR() {
            if (!tr) return;
            const now = new Date();
            const utc = now.toISOString().split('.')[0].replace('T', ' ');
            const lines = [
                `MISSION TIME: T-00:00:${String(Math.floor(Math.random() * 60)).padStart(2, '0')}`,
                `UTC: ${utc}`,
                `LAT: ${randFloat(48.84, 48.87, 5)}°N`,
                `LON: ${randFloat(2.33, 2.36, 5)}°E`,
                `ALT: ${randFloat(35, 42, 2)}m`,
                `HDG: ${randFloat(0, 360, 1)}°`,
            ];
            tr.textContent = lines.join('\n');
        }
        function updateBL() {
            if (!bl) return;
            const codes = ['ALL', 'NOM', 'GO', 'RDY', 'OK', 'GRN'];
            const lines = [
                `> SUBSYSTEMS.................`,
                `  CAM........ [${codes[Math.floor(Math.random() * codes.length)]}]`,
                `  AUDIO...... [${codes[Math.floor(Math.random() * codes.length)]}]`,
                `  STREAM..... [${codes[Math.floor(Math.random() * codes.length)]}]`,
                `  NET........ [${codes[Math.floor(Math.random() * codes.length)]}]`,
                `  CHAT....... [${codes[Math.floor(Math.random() * codes.length)]}]`,
            ];
            bl.textContent = lines.join('\n');
        }
        function updateBR() {
            if (!br) return;
            const lines = [
                `CPU ${randFloat(12, 42, 1)}%   MEM ${randFloat(30, 60, 1)}%`,
                `GPU ${randFloat(18, 55, 1)}%   NET ${randFloat(2, 12, 2)}Mb/s`,
                `FPS ${Math.round(+randFloat(58, 60, 0))}    DROP ${randFloat(0, 0.4, 2)}%`,
                `BITRATE ${randFloat(5.8, 6.2, 2)}Mb/s`,
                `VIEWERS ~${Math.floor(Math.random() * 50)}`,
                `BUILD 2026.04.23+${randHex(4)}`,
            ];
            br.textContent = lines.join('\n');
        }

        updateTL(); updateTR(); updateBL(); updateBR();
        missionIntervals.push(setInterval(updateTL, 2500));
        missionIntervals.push(setInterval(updateTR, 1000));
        missionIntervals.push(setInterval(updateBL, 1800));
        missionIntervals.push(setInterval(updateBR, 900));
    }

    return { apply };
})();
