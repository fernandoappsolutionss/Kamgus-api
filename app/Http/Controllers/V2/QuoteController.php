<?php

namespace App\Http\Controllers\V2;

use App\Classes\PriceCalculator;
use App\Http\Controllers\Controller;
use App\Models\TypeTransport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class QuoteController extends Controller
{
    private $calculator;

    public function __construct(PriceCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'id_tipo_camion' => 'required|integer|min:1',
            'duration_seconds' => 'nullable|numeric|min:0',
            'distance_meters' => 'nullable|numeric|min:0',
            'precio_cliente' => 'nullable|numeric|min:0',
            'valor' => 'nullable|numeric|min:0',
            'origin.lat' => 'nullable|numeric|between:-90,90',
            'origin.lng' => 'nullable|numeric|between:-180,180',
            'destination.lat' => 'nullable|numeric|between:-90,90',
            'destination.lng' => 'nullable|numeric|between:-180,180',
            'origin_lat' => 'nullable|numeric|between:-90,90',
            'origin_lng' => 'nullable|numeric|between:-180,180',
            'destination_lat' => 'nullable|numeric|between:-90,90',
            'destination_lng' => 'nullable|numeric|between:-180,180',
        ]);

        $typeTransport = TypeTransport::find($request->input('id_tipo_camion'));
        if (!$typeTransport) {
            return response()->json([
                'error' => true,
                'msg' => 'Tipo de transporte no encontrado',
            ], self::HTTP_NOT_FOUND);
        }

        $metrics = $this->resolveRouteMetrics($request);
        if ($metrics === null) {
            return response()->json([
                'error' => true,
                'msg' => 'duration_seconds es requerido cuando Directions no esta disponible',
            ], self::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pk = PriceCalculator::DEFAULT_PK;
        $suggestedPrice = $this->calculator->suggestedPrice(
            $metrics['duration_seconds'],
            $metrics['distance_meters'],
            $typeTransport->precio_minuto,
            $typeTransport->precio_ayudante,
            $typeTransport->tiempo,
            $pk
        );
        $listPreviewPrice = $this->calculator->listPreviewPrice(
            $metrics['duration_seconds'],
            $typeTransport->precio_minuto,
            $typeTransport->precio_ayudante,
            $typeTransport->tiempo
        );
        $clientPrice = $request->input('precio_cliente', $request->input('valor'));

        return response()->json([
            'error' => false,
            'msg' => 'Cotizacion calculada',
            'data' => [
                'id_tipo_camion' => (int) $typeTransport->id,
                'nombre_camion' => $typeTransport->nombre,
                'duration_seconds' => (int) $metrics['duration_seconds'],
                'duration_minutes' => ((int) $metrics['duration_seconds']) / 60,
                'distance_meters' => (float) $metrics['distance_meters'],
                'distance_km' => (float) $metrics['distance_meters'] / 1000,
                'precio_minuto' => (float) $typeTransport->precio_minuto,
                'precio_ayudante' => (float) $typeTransport->precio_ayudante,
                'tiempo' => (float) $typeTransport->tiempo,
                'pk' => $pk,
                'precio_sugerido' => $suggestedPrice,
                'precio_listado_spa' => $listPreviewPrice,
                'precio_cliente' => $clientPrice === null ? null : (float) $clientPrice,
                'precio_cliente_banda' => $clientPrice === null ? null : $this->calculator->priceBand($clientPrice, $suggestedPrice),
                'bandas_precio' => [
                    'red' => '<= 0.7 * precio_sugerido',
                    'yellow' => '> 0.7 * precio_sugerido && < 0.86 * precio_sugerido',
                    'green' => '>= 0.86 * precio_sugerido',
                ],
                'source' => $metrics['source'],
            ],
        ], self::HTTP_OK);
    }

    private function resolveRouteMetrics(Request $request)
    {
        if ($request->filled('duration_seconds')) {
            return [
                'duration_seconds' => (int) $request->input('duration_seconds'),
                'distance_meters' => (float) $request->input('distance_meters', 0),
                'source' => 'request',
            ];
        }

        $serverKey = config('services.google_maps.server_key');
        $origin = $this->coordinate($request, 'origin');
        $destination = $this->coordinate($request, 'destination');

        if (!$serverKey || !$origin || !$destination) {
            return null;
        }

        $cacheKey = 'quote:directions:'.sha1(json_encode([
            'origin' => $origin,
            'destination' => $destination,
        ]));

        $metrics = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($serverKey, $origin, $destination) {
            return $this->fetchDirectionsMetrics($serverKey, $origin, $destination);
        });

        if ($metrics === null) {
            return null;
        }

        $metrics['source'] = 'directions';

        return $metrics;
    }

    private function coordinate(Request $request, $name)
    {
        $nested = $request->input($name);
        $lat = is_array($nested) && array_key_exists('lat', $nested) ? $nested['lat'] : $request->input($name.'_lat');
        $lng = is_array($nested) && array_key_exists('lng', $nested) ? $nested['lng'] : $request->input($name.'_lng');

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    private function fetchDirectionsMetrics($serverKey, array $origin, array $destination)
    {
        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => $origin['lat'].','.$origin['lng'],
                'destination' => $destination['lat'].','.$destination['lng'],
                'mode' => 'driving',
                'key' => $serverKey,
            ]);
        } catch (Throwable $exception) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $payload = $response->json();
        if (!is_array($payload) || ($payload['status'] ?? null) !== 'OK') {
            return null;
        }

        $leg = $payload['routes'][0]['legs'][0] ?? null;
        $durationSeconds = $leg['duration']['value'] ?? null;
        $distanceMeters = $leg['distance']['value'] ?? 0;

        if (!is_numeric($durationSeconds)) {
            return null;
        }

        return [
            'duration_seconds' => (int) $durationSeconds,
            'distance_meters' => is_numeric($distanceMeters) ? (float) $distanceMeters : 0.0,
        ];
    }
}
