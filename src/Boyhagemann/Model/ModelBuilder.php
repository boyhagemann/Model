<?php

namespace Boyhagemann\Model;

use Illuminate\Database\Schema\Blueprint;
use Event, App, Schema;

class ModelBuilder
{
	protected $name;
	protected $table;
	protected $path = '/models';
	protected $parentClass = 'Eloquent';
	protected $timestamps = true;
	protected $columns = array();
	protected $relations = array();

	protected $autoGenerate = false;

	/**
	 * @param string $name
	 * @return $this
	 */
	public function name($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param string $table
	 * @uses Blueprint
	 * @return $this
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function path($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 *
	 * @param bool $timestamps
	 * @return $this
	 */
	public function timestamps($timestamps = true)
	{
		$this->timestamps = $timestamps;
		return $this;
	}

	/**
	 * @param string $name
	 * @return Column
	 */
	public function column($name)
	{
		$column = new Column($name);
		$this->columns[$name] = $column;
		return $column;
	}

	/**
	 * @param $column
	 * @return Element\ElementInterface|null
	 */
	public function get($column)
	{
		if(!isset($this->columns[$column])) {
			return;
		}

		return $this->columns[$column];
	}

	/**
	 * @param string $parentClass
	 * @return $this
	 */
	public function parentClass($parentClass)
	{
		$this->parentClass = $parentClass;
		return $this;
	}

	public function hasMany($alias)
	{
		return $this->relation($alias, 'hasMany');
	}

	public function hasOne($alias)
	{
		return $this->relation($alias, 'hasOne');
	}

	/**
	 * @param string $alias
	 * @param boolean $type
	 * @return Relation
	 */
	protected function relation($alias, $type)
	{
		$relation = new Relation($this, $alias, $type);
		$this->relations[$alias] = $relation;
		return $relation;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		if($this->table) {
			return $this->table;
		}

		return $this->buildTableNameFromModel($this->model);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getParentClass()
	{
		return $this->parentClass;
	}

	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 * @return array
	 */
	public function getRelations()
	{
		return $this->relations;
	}

	/**
	 * @return bool
	 */
	public function hasTimestamps()
	{
		return $this->timestamps;
	}

	/**
	 * @return array
	 */
	public function getRules()
	{
		$rules = array();
		foreach($this->columns as $name => $column) {
			if($column->getRules()) {
				$rules[$name] = $column->getRules();
			}
		}

		return $rules;
	}

	/**
	 * @param bool $autoGenerate
	 * @return $this
	 */
	public function autoGenerate($autoGenerate = true)
	{
		$this->autoGenerate = $autoGenerate;
		return $this;
	}

	public function build()
	{
		if($this->autoGenerate) {
			Event::fire('model.builder.generate', $this);
		}

		return App::make($this->name);
	}

	/**
	 * @return array
	 */
	public function buildBlueprints()
	{
		$blueprint = new Blueprint($this->table);

		if(!Schema::hasTable($this->table)) {
			$blueprint->increments('id');
			if($this->timestamps) {
				$blueprint->timestamps();
			}
		}

		$blueprints[] = $blueprint;

		// Only add column that doesn't exist in the database yet
		foreach($this->columns as $name => $column) {
			if(!Schema::hasColumn($this->table, $name)) {
				$blueprint->{$column->getType()}($name);
			}
		}

		foreach($this->relations as $relation) {
			if($relation->hasPivotTable()) {
				$blueprints[] = $relation->getBlueprint();
			}
			else {
				$blueprint->integer($relation->getColumn());
			}
		}

		return $blueprints;
	}

	/**
	 *
	 * @param mixed $model
	 * @return string
	 */
	protected function buildTableNameFromModel($model)
	{
		if(is_object($model)) {
			$model = get_class($model);
		}

		$nameParts = explode('\\', $model);
		return strtolower(end($nameParts));
	}
}