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
		$events->listen('modelbuilder.build', array($this, 'generateModel'));
		$events->listen('modelbuilder.build', array($this, 'generateRepository'));
	}

	/**
	 * @param ModelBuilder $mb
	 */
	public function generateModel(ModelBuilder $mb)
	{
		/** @var \Boyhagemann\Model\Generator $me */
		$me = App::make('Boyhagemann\Model\Generator');
		$me->setBuilder($mb);
//		$me->exportToDb();
		$me->exportToFile();
	}

	/**
	 * @param ModelBuilder $mb
	 */
	public function generateRepository(ModelBuilder $mb)
	{
		$template = file_get_contents(__DIR__ . '/../../../views/template/repository.txt');
		$template = str_replace('{repositoryClass}', Str::studly($mb->getName() . 'Repository'), $template);
		$template = str_replace('{modelClass}', Str::studly($mb->getName()), $template);

		$filename = app_path('repositories/' . Str::studly($mb->getName()) . 'Repository.php');

		// Write the new repository file to the models folder
		file_put_contents($filename, $template);
	}



}