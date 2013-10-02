<?php

namespace Boyhagemann\Model;

class Column
{
	protected $name;
	protected $type;
	protected $size;
	protected $rules = '';

	/**
	 * @param $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * @param $type
	 * @return $this
	 */
	public function type($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @param $size
	 * @return $this
	 */
	public function size($size)
	{
		$this->size = $size;
		return $this;
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
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * @param $rule
	 * @return bool
	 */
	public function hasRule($rule)
	{
		$rules = explode('|', $this->rules);
		return in_array($rule, $rules);
	}

	/**
	 * @param $rules
	 */
	public function validate($rules)
	{
		if(!$this->rules) {
			$this->rules = $rules;
			return $this;
		}

		$rules = array_merge(explode('|', $this->rules), explode('|', $rules));
		$this->rules = implode('|', $rules);
		return $this;
	}
}