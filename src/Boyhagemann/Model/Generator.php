<?php

namespace Boyhagemann\Model;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Schema, DB, Event;

class Generator
{
	/**
	 * @var ClassGenerator
	 */
	protected $generator;

	/**
	 * @var Builder
	 */
	protected $builder;

	/**
	 * @param FileGenerator $generator
	 */
	public function __construct(FileGenerator $generator)
	{
		$this->generator = $generator;
	}

	/**
	 * @param ModelBuilder $builder
	 */
	public function setBuilder(ModelBuilder $builder)
	{
		$this->builder = $builder;
	}

	/**
	 * @return ModelBuilder
	 */
	public function getBuilder()
	{
		return $this->builder;
	}

	public function exportToDb()
	{
		$builder = $this->getBuilder();

		foreach($builder->buildBlueprints() as $blueprint) {

//			if (Schema::hasTable($blueprint->getTable())) {
//				Schema::drop($blueprint->getTable());
//			}

			if (!Schema::hasTable($blueprint->getTable())) {
				$blueprint->create();
			}

			$blueprint->build(DB::connection(), DB::connection()->getSchemaGrammar());

			// Trigger an event
			Event::fire('modelBuilder.generator.export', compact('blueprint', 'builder'));
		}
	}

	/**
	 * @return $this
	 */
	public function exportToFile()
	{
		$filename = $this->buildFilename();
		$contents = $this->buildFile();

		@mkdir(dirname($filename), 0755, true);
		file_put_contents($filename, $contents);

		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function buildFilename()
	{
		$filename = app_path() .  '/' . trim($this->getBuilder()->getPath(), '/');
		$filename .= '/' . $this->getBuilder()->getName() . '.php';
		$filename = str_replace('\\', '/', $filename);
		return $filename;
	}

	/**
	 * @return string
	 */
	public function buildFile()
	{
		$builder = $this->getBuilder();
		$file = $this->generator;
		$file->setClass($builder->getName());
		$class = current($file->getClasses());
		$class->setExtendedClass($builder->getParentClass());
		$class->addUse('Eloquent');

		// Set the table name
		$class->addProperty('table', $builder->getTable(), PropertyGenerator::FLAG_PROTECTED);

		$class->addProperty('timestamps', $builder->hasTimestamps());

		// Set the rules
		$class->addProperty('rules', $builder->getRules());

		$class->addProperty('guarded', array('id'), PropertyGenerator::FLAG_PROTECTED);

		$fillable = array_keys($builder->getColumns());
		$class->addProperty('fillable', $fillable, PropertyGenerator::FLAG_PROTECTED);


		// Add elements, only for relationships
		foreach ($builder->getRelations() as $relation) {

			if($relation->getType() == 'hasMany') {
				$docblock = '@return \Illuminate\Database\Eloquent\Collection';
				$body = sprintf('return $this->%s(\'%s\', \'%s\');', $relation->getType(), $relation->getModel(), $relation->getTable());
			}
			else {
				$docblock = '@return \\' . $relation->getModel();
				$body = sprintf('return $this->%s(\'%s\');', $relation->getType(), $relation->getModel());
			}

			$class->addMethod($relation->getAlias(), array(), null, $body, $docblock);
		}

		return $file->generate();
	}
}