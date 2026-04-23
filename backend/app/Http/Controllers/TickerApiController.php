<?php

namespace App\Http\Controllers;

use App\Events\BroadcastOverlayUpdate;
use App\Models\TickerSetting;
use App\Services\TickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TickerApiController extends Controller
{
    public function __construct(private readonly TickerService $ticker) {}

    public function index(Request $request): JsonResponse
    {
        $scene = $request->query('scene');
        $cacheKey = 'ticker:compiled:'.($scene ?? 'all');

        $items = Cache::remember($cacheKey, 15, fn () => $this->ticker->compile($scene ?: null));

        return response()->json([
            'items' => $items,
            'emergency' => TickerSetting::current()->emergency_enabled,
        ]);
    }

    public function emergency(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $setting = TickerSetting::current();
        $setting->update([
            'emergency_enabled' => true,
            'emergency_message' => $validated['message'],
            'emergency_color' => $validated['color'] ?? '#FF4444',
        ]);

        Cache::forget('ticker:compiled:all');

        BroadcastOverlayUpdate::dispatch([
            'type' => 'ticker_emergency',
            'enabled' => true,
            'message' => $validated['message'],
            'color' => $validated['color'] ?? '#FF4444',
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function clearEmergency(): JsonResponse
    {
        TickerSetting::current()->update(['emergency_enabled' => false]);

        Cache::forget('ticker:compiled:all');

        BroadcastOverlayUpdate::dispatch([
            'type' => 'ticker_emergency',
            'enabled' => false,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function push(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'expires_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $setting = TickerSetting::current();
        $priority = $setting->priority_messages ?? [];
        $priority[] = [
            'message' => $validated['message'],
            'expires_at' => now()->addMinutes((int) ($validated['expires_minutes'] ?? 30))->toIso8601String(),
        ];
        $setting->update(['priority_messages' => $priority]);

        Cache::forget('ticker:compiled:all');

        BroadcastOverlayUpdate::dispatch([
            'type' => 'ticker_push',
            'message' => $validated['message'],
            'icon' => 'ph:bell-ringing',
        ]);

        return response()->json(['status' => 'ok']);
    }
}
