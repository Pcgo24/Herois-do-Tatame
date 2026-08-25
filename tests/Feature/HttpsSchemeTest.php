<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsSchemeTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceScheme(null);

        parent::tearDown();
    }

    private function bootProvider(): void
    {
        URL::forceScheme(null);
        (new AppServiceProvider($this->app))->boot();
    }

    public function test_https_is_forced_when_app_url_is_https(): void
    {
        config()->set('app.url', 'https://herois-do-tatame.onrender.com');

        $this->bootProvider();

        $this->assertStringStartsWith('https://', url('/qualquer'));
        $this->assertStringStartsWith('https://', asset('build/app.css'));
    }

    public function test_https_is_forced_when_the_proxy_reports_https(): void
    {
        config()->set('app.url', 'http://localhost');
        $this->app['request']->server->set('HTTP_X_FORWARDED_PROTO', 'https');

        $this->bootProvider();

        $this->assertStringStartsWith('https://', asset('build/app.css'));
    }

    public function test_http_is_left_alone_in_local_development(): void
    {
        config()->set('app.url', 'http://localhost');
        $this->app['request']->server->remove('HTTP_X_FORWARDED_PROTO');

        $this->bootProvider();

        $this->assertStringStartsWith('http://', asset('build/app.css'));
    }
}
