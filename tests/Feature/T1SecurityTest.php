<?php

namespace Tests\Feature;

use App\Classes\StripeCustomClass;
use Tests\TestCase;

class T1SecurityTest extends TestCase
{
    public function testStripeKeysAreSeparated(): void
    {
        config([
            'services.stripe.key' => 'pk_test_contract',
            'services.stripe.secret' => 'sk_test_contract',
        ]);

        $stripe = StripeCustomClass::getInstance();

        $this->assertSame('pk_test_contract', $stripe->getPublishableKey());
        $this->assertSame('sk_test_contract', $stripe->getSecretKey());
        $this->assertSame('pk_test_contract', $stripe->getPublicKey());
    }

    public function testServerSideStripeCallSiteUsesSecretKey(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/PaymentController.php'));

        $this->assertStringContainsString('StripeCustomClass::getInstance()->getSecretKey()', $source);
        $this->assertStringNotContainsString('setApiKey(StripeCustomClass::getInstance()->getPublicKey())', $source);
    }

    public function testDangerousDebugRoutesAreNotRegistered(): void
    {
        $routes = collect(app('router')->getRoutes())->map(function ($route) {
            return implode('|', $route->methods()) . ' ' . $route->uri();
        })->all();

        $blocked = [
            'GET test_command',
            'GET generate_link',
            'GET api/v2/migrate_v1/services',
            'GET api/v2/migrate_v1/driver_vehicles',
            'GET api/v2/test',
            'GET api/v2/db/{id}',
            'GET api/v2/finish_service',
            'GET api/v2/driver/notify/{id}',
            'GET api/v2/invited/services/test_refund',
        ];

        foreach ($blocked as $route) {
            $this->assertNotContains($route, $routes);
        }
    }

    public function testDangerousDebugUrlsReturnNotFound(): void
    {
        foreach ([
            '/test_command',
            '/generate_link',
            '/api/v2/migrate_v1/services',
            '/api/v2/test',
            '/api/v2/db/1',
            '/api/v2/finish_service',
            '/api/v2/driver/notify/1',
            '/api/v2/invited/services/test_refund',
        ] as $url) {
            $this->get($url)->assertStatus(404);
        }
    }

    public function testAmountValidationRulesRejectZeroAndNegativeValues(): void
    {
        $sources = [
            base_path('app/Http/Controllers/V2/Inviteds/ServiceController.php'),
            base_path('app/Http/Controllers/V2/Customers/ServiceController.php'),
            base_path('app/Http/Controllers/V2/Drivers/ServicesController.php'),
            base_path('app/Http/Controllers/ServiceController.php'),
            base_path('app/Http/Controllers/Dashboard/ServiceController.php'),
        ];

        $combined = implode("\n", array_map('file_get_contents', $sources));

        foreach (['valor', 'precio_sugerido', 'precio', 'precio_real'] as $field) {
            $this->assertMatchesRegularExpression(
                "/['\"]{$field}['\"]\\s*=>\\s*['\"][^'\"]*numeric[^'\"]*gt:0/",
                $combined,
                "{$field} must validate numeric|gt:0"
            );
        }

        $this->assertStringNotContainsString("'valor' => 'required|numeric|max:255'", $combined);
        $this->assertStringNotContainsString("'precio' => 'required|max:255'", $combined);
    }
}
