<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TileProxyController extends Controller
{
    /**
     * Proxy OSM tile requests to protect user IP addresses.
     * @param Request $request
     * @param int $z Zoom level
     * @param int $x Tile x coordinate
     * @param int $y Tile y coordinate
     * 
     * @see https://operations.osmfoundation.org/policies/tiles/
     */
    public function proxy(Request $request, int $z, int $x, int $y)
    {
        if ($z < 0 || $z > 19 || $x < 0 || $y < 0) {
            return response()->json(['error' => 'Ungültige Koordinaten'], 400);
        }

        $appUrl = config('app.url');
        
        if (empty($appUrl)) {
            Log::error('TileProxy: APP_URL not configured in .env');
            return response()->json(['error' => 'Serverkonfigurationsfehler'], 500);
        }

        // Build OSM tile URL
        $tileUrl = "https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png";

        try {
            // OSM Tile Usage Policy 3.1 requires identification via User-Agent
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => $appUrl,
                ])
                ->get($tileUrl);

            if (!$response->successful()) {
                Log::warning("TileProxy: OSM returned status {$response->status()} for tile {$z}/{$x}/{$y}");
                return response('', $response->status());
            }

            return response($response->body(), 200)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'public, max-age=604800'); // Cache for 1 week
                
        } catch (\Exception $e) {
            Log::error("TileProxy: Failed to fetch tile {$z}/{$x}/{$y}: " . $e->getMessage());
            return response()->json(['error' => 'Fehler beim Abrufen der Kachel'], 500);
        }
    }
}
