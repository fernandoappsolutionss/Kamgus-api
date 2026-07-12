<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function call($method, $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        $this->assertStringNotContainsString('sk_', $response->getContent());

        return $response;
    }
}
