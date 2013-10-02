<?php

namespace Boyhagemann\Model;

use Illuminate\Database\Schema\Blueprint;

class Relation
{    
	protected $alias;
	protected $model;
	protected $type;

	protected $builder;
	protected $blueprint;

	public function __construct(Builder $builder, $alias, $type)
	{
		$this->builder = $builder;
		$this->alias = $alias;
		$this->type = $type;

		$this->model = ucfirst($alias);
	}

	/**
	 * @param mixed $model
	 * @return $this
	 */
	public function model($model)
	{
		$this->model = $model;
		return $this;
	}

	/**
	 * @return Blueprint|null
	 */
	public function getBlueprint()
	{
		if($this->hasPivotTable()) {

			$left = $this->buildLeftColumnName();
			$right = $this->getColumn();
			$table = $this->getTable();

			$blueprint = new Blueprint($table);
			$blueprint->increments('id');
			$blueprint->integer($left);
			$blueprint->integer($right);

			return $blueprint;
		}
	}

	/**
	 * @return bool
	 */
	public function hasPivotTable()
	{
		switch($this->type) {
			case 'hasMany': return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->builder->getTable() . '_' . $this->buildColumnNameFromModel($this->model);
	}

	/**
	 * @return string
	 */
	protected function buildLeftColumnName()
	{
		return $this->builder->getTable() . '_id';
	}

	/**
	 * @return string
	 */
	public function getColumn()
	{
		return $this->buildColumnNameFromModel($this->model) . '_id';
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 *
	 * @param mixed $model
	 * @return string
	 */
	protected function buildColumnNameFromModel($model)
	{
		if(is_object($model)) {
			$model = get_class($model);
		}

		$nameParts = explode('\\', $model);
		return strtolower(end($nameParts));
	}
}