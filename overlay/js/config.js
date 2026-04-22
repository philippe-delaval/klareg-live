// ═══════════════════════════════════════════════
// KLAREG OVERLAY — Configuration
// ═══════════════════════════════════════════════

const CONFIG = {
    // ── Channel ──
    channel: "Klareg",

    // ── Scenes ──
    sceneType: "starting-soon",          // starting-soon | just-chatting | gaming | screen-share | brb | ending | alert-layer

    // ── Starting Soon ──
    countdownMinutes: 5,
    countdownSeconds: 0,

    // ── BRB ──
    brbDurationMinutes: 5,

    // ── Schedule ──
    schedule: [
        { time: "18:00", label: "Just Chatting / Échauffement", active: true },
        { time: "19:00", label: "Ranked Grind", active: true },
        { time: "22:00", label: "Jeux Communautaires", active: false }
    ],

    // ── Ticker Messages ──
    tickerMessages: [
        "Bienvenue sur le live ! Installez-vous confortablement.",
        "Au programme aujourd'hui : Ranked Grind & Jeux Communautaires.",
        "N'oubliez pas le follow pour être notifié des prochains lives !",
        "Tapez !commands dans le chat pour voir les interactions disponibles.",
        "Abonnez-vous pour des emotes et des avantages !"
    ],

    // ── Sub Goal ──
    subGoal: { current: 142, target: 200, label: "Sub Goal" },

    // ── Follower Goal ──
    followerGoal: { current: 3840, target: 5000, label: "Follower Goal" },

    // ── Social Links ──
    socials: [
        { platform: "twitch", url: "https://twitch.tv/Klareg", label: "Klareg" },
        { platform: "twitter", url: "https://x.com/Klareg", label: "@Klareg" },
        { platform: "youtube", url: "https://youtube.com/@Klareg", label: "Klareg" },
        { platform: "discord", url: "https://discord.gg/Klareg", label: "Discord" }
    ],

    // ── Now Playing ──
    nowPlaying: { track: "Synthwave Radio", artist: "Chill Beats" },

    // ── Stream Title / Category ──
    streamTitle: "Ranked Grind & Soirée Communautaire",
    streamCategory: "Just Chatting",

    // ── Next Stream ──
    nextStream: "Demain à 18h00 CET",

    // ── Design Tokens (mirrors CSS custom properties) ──
    accentColor: "#5B7FFF",
    accentColorRGB: "91, 127, 255",

    // Alerts config
    alertsConfig: {
        follow:    { enabled: true, duration: 6000 },
        sub:       { enabled: true, duration: 6000 },
        resub:     { enabled: true, duration: 6000 },
        giftsub:   { enabled: true, duration: 6000 },
        bits:      { enabled: true, duration: 6000, minAmount: 1 },
        raid:      { enabled: true, duration: 6000, minViewers: 1 },
        donation:  { enabled: true, duration: 6000 },
        hype_train:{ enabled: true },
    },
    // Chat config
    chatEnabled: true,
    chatMaxMessages: 50,
    // Goal toggles
    goalSubEnabled: true,
    goalFollowerEnabled: true,
    nowPlayingEnabled: true,
};