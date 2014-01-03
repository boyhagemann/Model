<?php

namespace Boyhagemann\Model\Subscriber;

use Illuminate\Events\Dispatcher as Events;
use Boyhagemann\Model\ModelBuilder;
use App, Str, Artisan;

class GenerateModelAndRepository
{
	/**
	 *
	 * Let's have the ModelBuilder interact with the FormBuilder.
	 *
	 * @param Events $events
	 */
	public function subscribe(Events $events)
	{
		$events->listen('modelbuilder.build', array($this, 'generate'));
	}

	/**
	 * @param ModelBuilder $mb
	 */
	public function generate(ModelBuilder $mb)
	{
		$class = $mb->getName();
		$file = null;

		// Get the location of the class if it exists
		if(in_array($class, get_declared_classes())) {
			$reflector = new \ReflectionClass($class);
			$file = $reflector->getFileName();
		}

		// Only allow resources to be generated if the are in the Application
		// folder or when the don't exist yet. The last is only the case if
		// the resource is newly created.
		if($file && strpos($file, app_path()) !== 0) {
			return;
		}

		$this->generateModel($mb);
		$this->generateRepository($mb);

	}

	/**
	 * @param ModelBuilder $mb
	 */
	protected function generateModel(ModelBuilder $mb)
	{
		/** @var \Boyhagemann\Model\Generator $me */
		$me = App::make('Boyhagemann\Model\Generator');
		$me->setBuilder($mb);
		$me->exportToFile();
	}

	/**
	 * @param ModelBuilder $mb
	 */
	protected function generateRepository(ModelBuilder $mb)
	{
		$template = file_get_contents(__DIR__ . '/../../../views/template/repository.txt');
		$template = str_replace('{repositoryClass}', Str::studly($mb->getName() . 'Repository'), $template);
		$template = str_replace('{modelClass}', Str::studly($mb->getName()), $template);

		$filename = app_path('repositories/' . Str::studly($mb->getName()) . 'Repository.php');

		// Write the new repository file to the models folder
		@file_put_contents($filename, $template);
	}



}