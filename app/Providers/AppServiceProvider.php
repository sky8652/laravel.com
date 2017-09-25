<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Events\LocaleUpdated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The default locale of application.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->defaultLocale = $app->getLocale();
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

        $this->registerEventListeners();

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
     * Register event listeners.
     *
     * @return void
     */
    protected function registerEventListeners()
    {
        $localeUpdated = class_exists(LocaleUpdated::class) ?
            LocaleUpdated::class : 'locale.changed';

        Event::listen($localeUpdated, function ($locale) {
            if (is_object($locale)) {
                $locale = $locale->locale;
            }

            $this->setRootUrlForLocale($locale);
        });
    }

    /**
     * Set root URL to UrlGenerator for the given locale.
     *
     * @param  string  $locale
     */
    protected function setRootUrlForLocale($locale)
    {
        $rootUrl = $this->app['request']->root();
        if ($locale != $this->defaultLocale) {
            $rootUrl .= '/'.$locale;
        }

        $this->app['url']->forceRootUrl($rootUrl);
    }

    /**
     * Remove locale prefix in URI, and set locale for application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function handleLocaleForRequest($request)
    {
        $uri = $request->server->get('REQUEST_URI');

        if ($locale = $this->getLocaleFromUri($uri, $prefix)) {
            $uri = '/'.ltrim(Str::replaceFirst($prefix, '', $uri), '/');

            $request->server->set('REQUEST_URI', $uri);
            $request->attributes->set('locale', $locale);

            $this->app->setLocale($locale);
        } else {
            $this->app->setLocale(
                $request->attributes->get('locale') ?: $this->defaultLocale
            );
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
