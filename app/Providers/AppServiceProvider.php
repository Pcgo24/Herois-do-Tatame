<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(fn () => route('admin.dashboard'));

        $this->forceHttpsBehindProxy();
    }

    /**
     * Atrás de um proxy que termina o TLS — o Render em produção — o PHP recebe
     * a requisição em http e o UrlGenerator cacheia esse esquema antes de o
     * TrustProxies rodar. Sem isto, a página sai em https referenciando CSS e JS
     * em http, e o navegador bloqueia tudo como conteúdo misto.
     *
     * Só force para cima, nunca para baixo: a versão anterior fazia
     * URL::forceScheme(esquema de APP_URL), e com APP_URL em http derrubava
     * ativamente URLs que deveriam ser https.
     */
    private function forceHttpsBehindProxy(): void
    {
        $appUrlIsHttps = str_starts_with((string) config('app.url'), 'https://');
        $proxyReportsHttps = $this->app['request']->server('HTTP_X_FORWARDED_PROTO') === 'https';

        if ($appUrlIsHttps || $proxyReportsHttps) {
            URL::forceScheme('https');
        }
    }
}
