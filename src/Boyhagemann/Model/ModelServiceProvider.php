<?php namespace Boyhagemann\Model;

use Illuminate\Support\ServiceProvider;
use App, Event, Redirect;

class ModelServiceProvider extends ServiceProvider {

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
      	$this->package('model', 'model');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
	}


	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('model');
	}

}