<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('lang', function ($expression) {
            return "<?php echo __($expression); ?>";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->setupConfiguration();

        if ($locale = $this->removeLocalePrefix()) {
            $this->app['url']->forceRootUrl(request()->root().'/'.$locale);
        }
    }

    protected function setupConfiguration()
    {
        if (! $this->app->configurationIsCached()) {
            config([
                'locales' => ['zh'],
            ]);
        }
    }

    /**
     * Remove the locale prefix of the request URI.
     * Return the locale prefix.
     *
     * @return string|null
     */
    protected function removeLocalePrefix()
    {
        if (preg_match('#/([a-z-_]+)#i', $uri = $_SERVER['REQUEST_URI'] ?? null, $matches)) {
            foreach (config('locales') as $locale) {
                if ($matches[1] === $locale) {
                    $uri = '/'.ltrim(Str::replaceFirst($matches[0], '', $uri), '/');
                    $_SERVER['REQUEST_URI'] = $uri;
                    request()->server->set('REQUEST_URI', $uri);

                    config(['app.locale' => $locale]);
                    request()->attributes->set('locale', $locale);

                    return $locale;
                }
            }
        }
    }
}
