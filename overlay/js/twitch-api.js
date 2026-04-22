// ═══════════════════════════════════════════════
// KLAREG OVERLAY — Twitch API Engine (STUB)
// Twitch connections are now handled server-side.
// Events arrive via Reverb (reverb-client.js).
// This stub preserves backward compatibility.
// ═══════════════════════════════════════════════

const TwitchAPI = (() => {
    function init() {
        console.log('[TwitchAPI] Server-side mode — Twitch events arrive via Reverb');
    }

    return { init };
})();