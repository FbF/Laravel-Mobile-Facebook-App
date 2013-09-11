<?php namespace Fbf\LaravelMobileFacebookApp;

use Illuminate\Support\ServiceProvider;
use Session;

class LaravelMobileFacebookAppServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->package('fbf/laravel-mobile-facebook-app');

		include __DIR__.'/../../filter.php';

		\App::register('Thomaswelton\LaravelFacebook\LaravelFacebookServiceProvider');

		// Shortcut so developers don't need to add an Alias in app/config/app.php
		$this->app->booting(function()
		{
			$loader = \Illuminate\Foundation\AliasLoader::getInstance();
			$loader->alias('Facebook', 'Thomaswelton\LaravelFacebook\Facades\Facebook');
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}