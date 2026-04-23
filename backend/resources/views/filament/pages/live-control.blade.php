<x-filament-panels::page>
@php $s = $this->getStats(); @endphp

<div wire:poll.15s class="space-y-4">

    {{-- ── URGENCE ACTIVE ─────────────────────────────────────────── --}}
    @if ($s['emergency_enabled'])
    <div class="flex items-center justify-between gap-3 rounded-xl border border-danger-300 dark:border-danger-700 bg-danger-50 dark:bg-danger-950/30 px-4 py-3">
        <div class="flex items-center gap-2.5 min-w-0">
            <x-filament::icon icon="heroicon-s-exclamation-triangle" class="h-4 w-4 text-danger-500 shrink-0" />
            <div class="min-w-0">
                <span class="text-sm font-semibold text-danger-600 dark:text-danger-400">Mode urgence actif</span>
                <span class="text-sm text-danger-500/80 ml-2 truncate">{{ $s['emergency_message'] }}</span>
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            <x-filament::button wire:click="mountAction('emergency')" color="danger" size="sm" outlined>Modifier</x-filament::button>
            <x-filament::button wire:click="mountAction('clearEmergency')" color="gray" size="sm" outlined>Effacer</x-filament::button>
        </div>
    </div>
    @endif

    {{-- ── STATS ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Viewers</p>
            <p class="mt-1 text-3xl font-bold tracking-tight">{{ $s['viewers'] }}</p>
            <p class="mt-1 text-xs text-gray-400 flex items-center gap-1">
                <span class="inline-block h-1.5 w-1.5 rounded-full {{ $s['viewers'] > 0 ? 'bg-success-500' : 'bg-gray-400' }}"></span>
                {{ $s['viewers'] > 0 ? 'Live actif' : 'Hors ligne' }}
            </p>
        </div>

        <div class="rounded-xl border {{ $s['ticker_enabled'] ? 'border-success-200 dark:border-success-800 bg-success-50 dark:bg-success-950/30' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5' }} p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Bandeau</p>
            <p class="mt-1 text-lg font-bold {{ $s['ticker_enabled'] ? 'text-success-600 dark:text-success-400' : 'text-gray-400' }}">
                {{ $s['ticker_enabled'] ? 'Actif' : 'Inactif' }}
            </p>
            <p class="mt-1 text-xs text-gray-400 flex items-center gap-1">
                <span class="inline-block h-1.5 w-1.5 rounded-full {{ $s['ticker_enabled'] ? 'bg-success-500' : 'bg-gray-400' }}"></span>
                Ticker overlay
            </p>
        </div>

        <div class="rounded-xl border {{ $s['twitch_events_enabled'] ? 'border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-950/30' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5' }} p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Alertes Twitch</p>
            <p class="mt-1 text-lg font-bold {{ $s['twitch_events_enabled'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}">
                {{ $s['twitch_events_enabled'] ? 'Actives' : 'Inactives' }}
            </p>
            <p class="mt-1 text-xs text-gray-400 flex items-center gap-1">
                <span class="inline-block h-1.5 w-1.5 rounded-full {{ $s['twitch_events_enabled'] ? 'bg-primary-500' : 'bg-gray-400' }}"></span>
                Follows &amp; subs
            </p>
        </div>

        <div class="rounded-xl border {{ $s['now_playing'] ? 'border-success-200 dark:border-success-800 bg-success-50 dark:bg-success-950/30' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5' }} p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Musique</p>
            @if ($s['now_playing'])
                <p class="mt-1 text-xs font-semibold text-success-600 dark:text-success-400 leading-snug line-clamp-2">{{ $s['now_playing'] }}</p>
            @else
                <p class="mt-1 text-lg font-bold text-gray-400">{{ $s['music_enabled'] ? '—' : 'Off' }}</p>
            @endif
            <p class="mt-1 text-xs text-gray-400 flex items-center gap-1">
                <span class="inline-block h-1.5 w-1.5 rounded-full {{ $s['now_playing'] ? 'bg-success-500' : 'bg-gray-400' }}"></span>
                {{ $s['now_playing'] ? 'En lecture' : 'Rien en cours' }}
            </p>
        </div>

    </div>

    {{-- ── ACTIONS + TOGGLES ──────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">

        {{-- Actions ──────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
            <p class="text-sm font-semibold mb-1">Actions</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Contrôles immédiats sur l'overlay</p>
            <div class="space-y-2">
                {{-- Bloc urgence inline --}}
                <div class="rounded-lg border border-danger-200 dark:border-danger-800 bg-danger-50 dark:bg-danger-950/30 p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 text-danger-500 shrink-0" />
                        <p class="text-sm font-medium text-danger-700 dark:text-danger-300 flex-1">Mode urgence</p>
                        @if ($s['emergency_enabled'])
                            <x-filament::badge color="danger" size="sm">Actif</x-filament::badge>
                        @endif
                    </div>
                    <textarea
                        wire:model="emergencyInput"
                        placeholder="Tape ton message d'urgence ici…"
                        rows="2"
                        class="w-full rounded-md border border-danger-200 dark:border-danger-700 bg-white dark:bg-danger-950/40 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-danger-500 resize-none"
                    ></textarea>
                    <div class="flex items-center justify-between mt-2 gap-2">
                        <x-filament::button
                            wire:click="publishEmergency"
                            color="danger"
                            size="sm"
                            icon="heroicon-o-paper-airplane"
                        >
                            Publier l'urgence
                        </x-filament::button>
                        @if ($s['emergency_enabled'])
                            <x-filament::button
                                wire:click="clearEmergencyInline"
                                color="gray"
                                size="sm"
                                outlined
                            >
                                Effacer
                            </x-filament::button>
                        @endif
                    </div>
                </div>

                <button wire:click="mountAction('pushMessage')"
                    class="w-full flex items-center gap-3 rounded-lg border border-warning-200 dark:border-warning-800 bg-warning-50 dark:bg-warning-950/30 hover:bg-warning-100 dark:hover:bg-warning-950/50 px-3.5 py-3 text-left transition-colors">
                    <x-filament::icon icon="heroicon-o-bell-alert" class="h-4 w-4 text-warning-500 shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-warning-700 dark:text-warning-300">Push message</p>
                        <p class="text-xs text-warning-500/70 mt-0.5">Message prioritaire dans le bandeau</p>
                    </div>
                </button>
            </div>
        </div>

        {{-- Toggles ──────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
            <p class="text-sm font-semibold mb-1">Contrôles</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Activer ou désactiver en un clic</p>
            <div class="space-y-1.5">
                @php $rows = [
                    ['action'=>'toggleTicker',      'label'=>'Bandeau défilant', 'icon'=>'heroicon-o-queue-list',  'on'=>$s['ticker_enabled']],
                    ['action'=>'toggleMusic',        'label'=>'Musique',          'icon'=>'heroicon-o-musical-note', 'on'=>$s['music_enabled']],
                    ['action'=>'toggleTwitchEvents', 'label'=>'Alertes Twitch',   'icon'=>'heroicon-o-user-group',   'on'=>$s['twitch_events_enabled']],
                    ['action'=>'toggleStats',        'label'=>'Stats viewers',    'icon'=>'heroicon-o-eye',           'on'=>$s['stats_enabled']],
                ]; @endphp

                @foreach ($rows as $r)
                <button wire:click="mountAction('{{ $r['action'] }}')"
                    class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors text-left">
                    <x-filament::icon :icon="$r['icon']" class="h-4 w-4 shrink-0 {{ $r['on'] ? 'text-primary-500' : 'text-gray-400' }}" />
                    <span class="flex-1 text-sm {{ $r['on'] ? 'font-medium' : 'text-gray-400 dark:text-gray-500' }}">{{ $r['label'] }}</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $r['on'] ? 'bg-success-100 dark:bg-success-950/50 text-success-700 dark:text-success-400' : 'bg-gray-100 dark:bg-white/5 text-gray-400' }}">
                        {{ $r['on'] ? 'ON' : 'OFF' }}
                    </span>
                </button>
                @endforeach
            </div>
        </div>

    </div>

    <p class="text-right text-xs text-gray-400">↻ {{ now()->format('H:i:s') }}</p>

</div>

<x-filament-actions::modals />
</x-filament-panels::page>
