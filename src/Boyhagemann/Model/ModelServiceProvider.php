<?php namespace Boyhagemann\Model;

use Illuminate\Support\ServiceProvider;
use App, Event;

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
            
//            App::resolving(function($instance) {
//                
//                if(is_object($instance) && get_class($instance) === 'Boyhagemann\Model\ModelBuilder') {
//                    Event::listen('formBuilder.addElement.post', array($instance, 'postAddElement'));
//                    Event::listen('formBuilder.buildElement.post', array($instance, 'postBuildElement'));  
//                }
//            });
	}
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{            
            $this->register('Robbo\Presenter\PresenterServiceProvider');
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