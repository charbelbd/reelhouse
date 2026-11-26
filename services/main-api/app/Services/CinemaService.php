<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class CinemaService
{
    public ?string $lastError = null;

    public function nearby(float $lat, float $lon, int $radiusMeters = 15000): ?array
    {
        $key = 'cinemas:'.round($lat, 2).':'.round($lon, 2).':'.$radiusMeters;

        try {
            return Cache::remember($key, now()->addHours(6), function () use ($lat, $lon, $radiusMeters) {
                $dLat = $radiusMeters / 111320;
                $dLon = $radiusMeters / (111320 * max(cos(deg2rad($lat)), 0.01));
                [$s, $w, $n, $e] = [$lat - $dLat, $lon - $dLon, $lat + $dLat, $lon + $dLon];
                $query = "[out:json][timeout:20][bbox:$s,$w,$n,$e];nwr[\"amenity\"=\"cinema\"];out center 40;";

                $servers = array_values(array_unique(array_filter([
                    config('services.overpass.url'),
                    'https://overpass-api.de/api/interpreter',
                    'https://overpass.private.coffee/api/interpreter',
                ])));
                $elements = null;
                foreach ($servers as $server) {
                    try {
                        $try = Http::timeout(22)->connectTimeout(5)->withHeaders(['User-Agent' => 'Reelhouse/1.0 (student project)'])
                            ->get($server, ['data' => $query]);
                        if ($try->successful() && is_array($try->json('elements'))) {
                            $elements = collect($try->json('elements'))->map(function ($el) {
                                $tags = $el['tags'] ?? [];

                                return [
                                    'id' => $el['type'].'/'.$el['id'],
                                    'name' => $tags['name'] ?? 'Cinema',
                                    'lat' => $el['lat'] ?? $el['center']['lat'] ?? null,
                                    'lon' => $el['lon'] ?? $el['center']['lon'] ?? null,
                                    'address' => trim(($tags['addr:street'] ?? '').' '.($tags['addr:housenumber'] ?? '').' '.($tags['addr:city'] ?? '')) ?: null,
                                    'website' => $tags['website'] ?? null,
                                ];
                            })->all();
                            break;
                        }
                        $this->lastError = "$server answered HTTP ".$try->status();
                    } catch (Throwable $ex) {
                        $this->lastError = "$server: ".substr($ex->getMessage(), 0, 120);
                    }
                }

                if ($elements === null) {
                    try {
                        $try = Http::timeout(15)->connectTimeout(5)->withHeaders(['User-Agent' => 'Reelhouse/1.0 (student project)'])
                            ->get('https://nominatim.openstreetmap.org/search', [
                                'q' => 'cinema', 'format' => 'jsonv2', 'limit' => 40, 'bounded' => 1,
                                'viewbox' => "$w,$n,$e,$s", 'addressdetails' => 0,
                            ]);
                        if ($try->successful() && is_array($try->json())) {
                            $elements = collect($try->json())->map(fn ($r) => [
                                'id' => ($r['osm_type'] ?? 'x').'/'.($r['osm_id'] ?? uniqid()),
                                'name' => $r['name'] ?? explode(',', $r['display_name'] ?? 'Cinema')[0],
                                'lat' => (float) $r['lat'],
                                'lon' => (float) $r['lon'],
                                'address' => isset($r['display_name']) ? implode(',', array_slice(explode(',', $r['display_name']), 1, 3)) : null,
                                'website' => null,
                            ])->all();
                        } else {
                            $this->lastError .= ' | nominatim HTTP '.$try->status();
                        }
                    } catch (Throwable $ex) {
                        $this->lastError .= ' | nominatim: '.substr($ex->getMessage(), 0, 120);
                    }
                }

                if ($elements === null) {
                    throw new \RuntimeException('no cinema server answered');
                }

                return collect($elements)->filter(fn ($c) => $c['lat'] !== null)
                    ->map(function ($c) use ($lat, $lon) {
                        $c['distance_km'] = round($this->distance($lat, $lon, $c['lat'], $c['lon']), 1);

                        return $c;
                    })->sortBy('distance_km')->values()->all();
            });
        } catch (Throwable $e) {
            error_log('[cinemas] lookup failed: '.$e->getMessage());

            return null;
        }
    }

    private function distance($lat1, $lon1, $lat2, $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
