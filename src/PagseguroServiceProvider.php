<?php

namespace Nagahshi\Pagseguro;
use Illuminate\Support\ServiceProvider;
use Nagahshi\Pagseguro\Gateway;
class PagseguroServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . "/config/pagseguro.php" => config_path('pagseguro.php')]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
      $this->app->singleton('Nagahshi\Pagseguro\Gateway', function ($app) {
        $email = config('pagseguro.email');
        $token = config('pagseguro.token');
        $redirect_url = config('pagseguro.redirect_url');
        $gateway = new Gateway();
        $gateway->setCredentials($email,$token,$redirect_url);        
        return $gateway;
      });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Nagahshi\Pagseguro\Gateway'];
    }
}
