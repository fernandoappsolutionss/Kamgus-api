<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuoteEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.google_maps.server_key' => null,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        $this->createTypesTransportTable();
        $this->seedTypesTransportTable();
    }

    public function testItQuotesWithProvidedDurationAndDistanceWithoutCallingGoogle()
    {
        Http::fake();

        $response = $this->postJson('/api/v2/quote', [
            'id_tipo_camion' => 1,
            'duration_seconds' => 1800,
            'distance_meters' => 12000,
            'precio_cliente' => 50,
        ]);

        $response->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id_tipo_camion', 1)
            ->assertJsonPath('data.nombre_camion', 'Panel')
            ->assertJsonPath('data.duration_seconds', 1800)
            ->assertJsonPath('data.distance_meters', 12000)
            ->assertJsonPath('data.precio_sugerido', 63.3)
            ->assertJsonPath('data.precio_cliente_banda', 'yellow');

        Http::assertNothingSent();
    }

    public function testItUsesDirectionsWhenDurationIsMissingAndGoogleKeyIsConfigured()
    {
        config(['services.google_maps.server_key' => 'test-key']);

        Http::fake([
            'https://maps.googleapis.com/maps/api/directions/json*' => Http::response($this->directionsOk(), 200),
        ]);

        $response = $this->postJson('/api/v2/quote', $this->directionsPayload([
            'id_tipo_camion' => 2,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.id_tipo_camion', 2)
            ->assertJsonPath('data.duration_seconds', 1800)
            ->assertJsonPath('data.distance_meters', 12000)
            ->assertJsonPath('data.precio_sugerido', 61.5);

        Http::assertSentCount(1);
    }

    public function testItReturns422WhenDirectionsTimesOut()
    {
        config(['services.google_maps.server_key' => 'test-key']);

        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        $response = $this->postJson('/api/v2/quote', $this->directionsPayload([
            'id_tipo_camion' => 1,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('error', true);
    }

    public function testItReturns422WhenDirectionsReturnsAnError()
    {
        config(['services.google_maps.server_key' => 'test-key']);

        Http::fake([
            'https://maps.googleapis.com/maps/api/directions/json*' => Http::response([
                'status' => 'ZERO_RESULTS',
                'routes' => [],
            ], 200),
        ]);

        $response = $this->postJson('/api/v2/quote', $this->directionsPayload([
            'id_tipo_camion' => 1,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('error', true);
    }

    public function testItCachesDirectionsForTenMinutes()
    {
        config(['services.google_maps.server_key' => 'test-key']);

        Http::fake([
            'https://maps.googleapis.com/maps/api/directions/json*' => Http::response($this->directionsOk(), 200),
        ]);

        $payload = $this->directionsPayload(['id_tipo_camion' => 1]);

        $this->postJson('/api/v2/quote', $payload)->assertOk();
        $this->postJson('/api/v2/quote', $payload)->assertOk();

        Http::assertSentCount(1);
    }

    public function testItRateLimitsAtThirtyRequestsPerMinute()
    {
        $payload = [
            'id_tipo_camion' => 1,
            'duration_seconds' => 1800,
            'distance_meters' => 12000,
        ];

        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/v2/quote', $payload)->assertOk();
        }

        $this->postJson('/api/v2/quote', $payload)->assertStatus(429);
    }

    public function testItReturns404ForUnknownTransportType()
    {
        $response = $this->postJson('/api/v2/quote', [
            'id_tipo_camion' => 999,
            'duration_seconds' => 1800,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error', true);
    }

    public function testItReturns422WhenNoDurationOrDirectionsInputIsAvailable()
    {
        $response = $this->postJson('/api/v2/quote', [
            'id_tipo_camion' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', true);
    }

    private function createTypesTransportTable()
    {
        Schema::create('types_transports', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->double('m3', 5, 2)->nullable();
            $table->double('peso', 5, 2)->nullable();
            $table->double('precio_minuto', 5, 2);
            $table->double('precio_ayudante', 5, 2);
            $table->integer('tiempo');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    private function seedTypesTransportTable()
    {
        DB::table('types_transports')->insert([
            ['id' => 1, 'nombre' => 'Panel', 'precio_minuto' => 0.47, 'precio_ayudante' => 15.00, 'tiempo' => 1, 'estado' => 1],
            ['id' => 2, 'nombre' => 'Pick up', 'precio_minuto' => 0.45, 'precio_ayudante' => 15.00, 'tiempo' => 1, 'estado' => 1],
            ['id' => 3, 'nombre' => 'Camion Pequeno', 'precio_minuto' => 0.75, 'precio_ayudante' => 30.00, 'tiempo' => 2, 'estado' => 1],
            ['id' => 4, 'nombre' => 'Camion Grande', 'precio_minuto' => 0.85, 'precio_ayudante' => 40.00, 'tiempo' => 2, 'estado' => 1],
            ['id' => 5, 'nombre' => 'Moto', 'precio_minuto' => 0.05, 'precio_ayudante' => 3.50, 'tiempo' => 1, 'estado' => 0],
            ['id' => 6, 'nombre' => 'Sedan', 'precio_minuto' => 0.08, 'precio_ayudante' => 5.00, 'tiempo' => 1, 'estado' => 1],
        ]);
    }

    private function directionsPayload(array $overrides = [])
    {
        return array_merge([
            'origin' => ['lat' => 9.0, 'lng' => -79.5],
            'destination' => ['lat' => 9.1, 'lng' => -79.6],
        ], $overrides);
    }

    private function directionsOk()
    {
        return [
            'status' => 'OK',
            'routes' => [
                [
                    'legs' => [
                        [
                            'duration' => ['value' => 1800],
                            'distance' => ['value' => 12000],
                        ],
                    ],
                ],
            ],
        ];
    }
}
