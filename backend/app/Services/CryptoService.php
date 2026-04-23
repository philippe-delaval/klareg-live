<?php

namespace App\Services;

use App\Models\ServiceConnection;
use Illuminate\Support\Facades\Http;

class CryptoService
{
    private const ID_MAP = [
        'BTC'   => 'bitcoin',
        'ETH'   => 'ethereum',
        'SOL'   => 'solana',
        'ADA'   => 'cardano',
        'XRP'   => 'ripple',
        'BNB'   => 'binancecoin',
        'DOGE'  => 'dogecoin',
        'MATIC' => 'matic-network',
        'DOT'   => 'polkadot',
        'AVAX'  => 'avalanche-2',
    ];

    public function getPrices(array $symbols): array
    {
        $ids = array_values(array_filter(
            array_map(fn ($s) => self::ID_MAP[strtoupper($s)] ?? null, $symbols)
        ));

        if (empty($ids)) {
            return [];
        }

        try {
            $apiKey = ServiceConnection::current()->coingecko_api_key;

            if (! $apiKey) {
                return [];
            }

            $response = Http::timeout(5)
                ->withHeaders(['x_cg_demo_api_key' => $apiKey])
                ->get('https://api.coingecko.com/api/v3/simple/price', [
                    'ids'                 => implode(',', $ids),
                    'vs_currencies'       => 'eur',
                    'include_24hr_change' => 'true',
                ]);

            if (! $response->ok()) {
                return [];
            }

            $data = $response->json();
            $results = [];

            foreach ($symbols as $symbol) {
                $id = self::ID_MAP[strtoupper($symbol)] ?? null;
                if (! $id || ! isset($data[$id])) {
                    continue;
                }

                $price = (float) ($data[$id]['eur'] ?? 0);
                $change = round((float) ($data[$id]['eur_24h_change'] ?? 0), 1);
                $arrow = $change >= 0 ? '▲' : '▼';
                $formatted = $price >= 1000
                    ? number_format($price, 0, ',', ' ')
                    : number_format($price, 2, ',', ' ');

                $results[] = strtoupper($symbol).' '.$formatted.' € '.$arrow.' '.abs($change).'%';
            }

            return $results;
        } catch (\Throwable) {
            return [];
        }
    }
}
