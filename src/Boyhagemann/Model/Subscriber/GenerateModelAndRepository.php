<?php

namespace Boyhagemann\Model\Subscriber;

use Illuminate\Events\Dispatcher as Events;
use Boyhagemann\Model\ModelBuilder;
use App, Str;

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
		$events->listen('model.builder.generate', array($this, 'generateModel'));
		$events->listen('model.builder.generate', array($this, 'generateRepository'));
	}

	/**
	 * @param ModelBuilder $builder
	 */
	public function generateModel(ModelBuilder $builder)
	{
		/** @var \Boyhagemann\Model\Generator $me */
		$me = App::make('Boyhagemann\Model\Generator');
		$me->setBuilder($builder);
		$me->exportToDb();
		$me->exportToFile();
	}

	/**
	 * @param ModelBuilder $builder
	 */
	public function generateRepository(ModelBuilder $builder)
	{
		$template = file_get_contents(__DIR__ . '/../../../views/template/repository.txt');
		$template = str_replace('{repositoryClass}', Str::studly($builder->getName() . 'Repository'), $template);
		$template = str_replace('{modelClass}', Str::studly($builder->getName()), $template);

		$filename = app_path('models/' . Str::studly($builder->getName()) . 'Repository.php');

		// Write the new repository file to the models folder
		file_put_contents($filename, $template);
	}



}