<?php namespace Rvwoens\Gompie;

use Collective\Html\HtmlFacade;
use Collective\Html\HtmlServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Class GompieServiceProvider
 * @package Rvwoens\Gompie
 * @version 1.0
 * @Author Ronald vanWoensel <rvw@cosninix.com>
 */
class GompieServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->loadViewsFrom(__DIR__.'/resources/views', 'gompie');
		// install additional (default) routes
		$this->loadRoutesFrom(__DIR__.'/routes/web.php');
		$this->loadMigrationsFrom(__DIR__.'/database/migrations');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		// Bind HTML package
		$this->app->register(HtmlServiceProvider::class);
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('HTML', HtmlFacade::class);

		$this->app->bind('gompie', function($app) {
			return new Gompie();
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return [];
	}

}