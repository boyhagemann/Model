<?php

namespace Boyhagemann\Model;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Schema, DB;

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
	 * @param Builder $builder
	 */
	public function setBuilder(Builder $builder)
	{
		$this->builder = $builder;
	}

	/**
	 * @return Builder
	 */
	public function getBuilder()
	{
		return $this->builder;
	}

	public function exportToDb()
	{
		foreach($this->getBuilder()->buildBlueprints() as $blueprint) {

			if (Schema::hasTable($blueprint->getTable())) {
				Schema::drop($blueprint->getTable());
			}

			$blueprint->create();

			$blueprint->build(DB::connection(), DB::connection()->getSchemaGrammar());
		}
	}

	/**
	 * @return $this
	 */
	public function exportToFile()
	{
		$filename = $this->buildFilename();
		$contents = $this->buildFile();

		$parts = explode('\\', $filename);
		for ($i = 0; $i < count($parts); $i++) {
			$filename .= '/' . $parts[$i];
			if ($i < count($parts) - 1) {
				@mkdir($filename);
			}
		}

		file_put_contents($filename, $contents);

//		require_once $filename;

		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function buildFilename()
	{
		return app_path() .  '/' . trim($this->getBuilder()->getPath(), '/'). '.php';
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
		$class->setExtendedClass('\\' . ltrim($builder->getParentClass(), '\\'));

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

			if($relation->getType() == 'belongsToMany') {
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