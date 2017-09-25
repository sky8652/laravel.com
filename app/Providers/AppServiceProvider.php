<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The default locale of application.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * The current request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->defaultLocale = $app['config']->get('app.locale');
    }

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

        $this->app->rebinding('request', function ($app, $request) {
            $this->handleLocaleForRequest($request);
        });
    }

    /**
     * Setup application configuration.
     *
     * @return void
     */
    protected function setupConfiguration()
    {
        if (! $this->app->configurationIsCached()) {
            config([
                'locales' => ['zh'],
            ]);
        }
    }

    /**
     * Remove locale prefix in URI, and set locale for application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function handleLocaleForRequest($request)
    {
        if ($this->request === $request) {
            return;
        }

        $this->request = $request;

        $uri = $request->server->get('REQUEST_URI');

        if ($locale = $this->getLocaleFromUri($uri, $prefix)) {
            $uri = '/'.ltrim(Str::replaceFirst($prefix, '', $uri), '/');

            $request->server->set('REQUEST_URI', $uri);
            $request->attributes->set('locale', $locale);

            app()->setLocale($locale);
            app('url')->forceRootUrl($request->root().$prefix);
        } else {
            app()->setLocale($this->defaultLocale);
            app('url')->forceRootUrl($request->root());
        }
    }

    /**
     * Get the locale segment from request URI.
     *
     * @param  string  $uri
     * @param  string|null &$prefix
     * @return string|null
     */
    protected function getLocaleFromUri($uri, &$prefix = null)
    {
        if ($locale = explode('/', $uri)[1] ?? null) {
            if (in_array($locale, config('locales'), true)) {
                $prefix = '/'.$locale;

                return $locale;
            }
        }
    }
}
