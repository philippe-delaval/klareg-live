// ═══════════════════════════════════════════════
// KLAREG — Theme manager
// Toggles [data-theme] on <html> to switch between base and studio themes
// ═══════════════════════════════════════════════

const ThemeManager = (() => {
    const VALID = ['default', 'studio'];

    function detectScene() {
        // Derive scene from URL path (e.g. /overlay/brb.html → "brb")
        const path = window.location.pathname;
        const m = path.match(/\/([^\/]+)\.html$/);
        if (!m) return '';
        const name = m[1];
        const map = {
            'starting-soon': '',
            'brb': 'brb',
            'gaming': 'gaming',
            'just-chatting': 'chatting',
            'screen-share': 'screen',
            'ending': 'ending',
            'alert-layer': '',
        };
        return map[name] ?? '';
    }

    function apply(theme) {
        if (!VALID.includes(theme)) theme = 'default';
        document.documentElement.setAttribute('data-theme', theme);
        const scene = detectScene();
        if (scene) {
            document.documentElement.setAttribute('data-scene', scene);
        } else {
            document.documentElement.removeAttribute('data-scene');
        }
    }

    function init() {
        // URL ?theme=X override for local testing
        const urlTheme = new URLSearchParams(window.location.search).get('theme');
        const initial = urlTheme || (typeof CONFIG !== 'undefined' && CONFIG.overlayTheme) || 'default';
        apply(initial);
    }

    return { init, apply };
})();

// Auto-init as early as possible (before DOMContentLoaded) to avoid flash
ThemeManager.init();
