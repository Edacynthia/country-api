<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Carbon\Carbon;

class CountryController extends Controller
{
    public function refresh()
    {
        try {
            // ✅ Correct REST Countries v3.1 API
            $countriesResponse = Http::timeout(30)->get('https://restcountries.com/v2/all?fields=name,capital,region,population,flag,currencies');

            if ($countriesResponse->failed()) {
                return response()->json([
                    'error' => 'External data source unavailable',
                    'details' => 'Could not fetch data from restcountries.com'
                ], 503);
            }

            $countries = $countriesResponse->json();

            // ✅ Exchange Rates
            $exchangeResponse = Http::timeout(30)->get('https://open.er-api.com/v6/latest/USD');

            if ($exchangeResponse->failed()) {
                return response()->json([
                    'error' => 'External data source unavailable',
                    'details' => 'Could not fetch data from open.er-api.com'
                ], 503);
            }

            $rates = $exchangeResponse->json()['rates'] ?? [];

            $now = now();
            $savedCount = 0;

            foreach ($countries as $countryData) {

    $name = $countryData['name'] ?? null;
    $capital = $countryData['capital'] ?? null;
    $region = $countryData['region'] ?? null;
    $population = $countryData['population'] ?? null;
    $flagUrl = $countryData['flag'] ?? null;

    if (!$name || !$population) continue;

    $currencyCode = null;
    if (!empty($countryData['currencies']) && isset($countryData['currencies'][0]['code'])) {
        $currencyCode = $countryData['currencies'][0]['code'];
    }

    $exchangeRate = $currencyCode && isset($rates[$currencyCode])
        ? $rates[$currencyCode]
        : null;

    $estimatedGdp = $exchangeRate
        ? ($population * rand(1000, 2000)) / $exchangeRate
        : null;

    Country::updateOrCreate(
        ['name' => $name],
        [
            'capital' => $capital,
            'region' => $region,
            'population' => $population,
            'currency_code' => $currencyCode,
            'exchange_rate' => $exchangeRate,
            'estimated_gdp' => $estimatedGdp,
            'flag_url' => $flagUrl,
            'last_refreshed_at' => $now,
        ]
    );

    $savedCount++;
}

            // ✅ Generate summary image
            $this->generateSummaryImage();

            return response()->json([
                'message' => 'Countries refreshed successfully',
                'total_countries' => $savedCount,
                'last_refreshed_at' => $now->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Refresh failed: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function index(Request $request)
    {
        $query = Country::query();

        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        if ($request->filled('currency')) {
            $query->where('currency_code', $request->currency);
        }

        if ($request->input('sort') === 'gdp_desc') {
            $query->orderByDesc('estimated_gdp');
        }

        return response()->json($query->get());
    }

    public function show($name)
    {
        $country = Country::where('name', 'LIKE', "%{$name}%")->first();

        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }

        return response()->json($country);
    }

    public function destroy($name)
    {
        $country = Country::where('name', 'LIKE', "%{$name}%")->first();

        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }

        $country->delete();

        return response()->json(['message' => 'Country deleted successfully']);
    }

    public function status()
    {
        return response()->json([
            'total_countries' => Country::count(),
            'last_refreshed_at' => Country::max('last_refreshed_at'),
        ]);
    }

    public function image()
    {
        $path = public_path('storage/summary.png');

        if (!file_exists($path)) {
            return response()->json(['error' => 'Summary image not found'], 404);
        }

        return response()->file($path);
    }


    // ✅ Image Generation
    private function generateSummaryImage()
    {
        $top5 = Country::orderByDesc('estimated_gdp')->take(5)->get();
        $timestamp = now()->format('Y-m-d H:i:s');

        $manager = new ImageManager(new Driver());
        $img = $manager->create(800, 600)->fill('#1a1a1a');

        $img->text('Country GDP Summary', 400, 50, fn($font) =>
            $font->size(36)->color('#ffffff')->align('center')
        );

        $img->text("Total Countries: " . Country::count(), 400, 120, fn($font) =>
            $font->size(28)->color('#4ade80')->align('center')
        );

        $img->text('Top 5 by Estimated GDP', 400, 180, fn($font) =>
            $font->size(24)->color('#60a5fa')->align('center')
        );

        foreach ($top5 as $index => $country) {
            $gdp = $country->estimated_gdp
                ? number_format($country->estimated_gdp / 1_000_000_000, 2) . 'B'
                : 'N/A';

            $img->text(($index+1).". {$country->name} — $".$gdp, 400, 230 + ($index * 40), fn($font) =>
                $font->size(20)->color('#e5e7eb')->align('center')
            );
        }

        $img->text("Generated: {$timestamp}", 400, 550, fn($font) =>
            $font->size(18)->color('#9ca3af')->align('center')
        );

        $path = public_path('storage/summary.png');
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $img->save($path);
    }
}
