<?php

namespace Boyhagemann\Model;

use Way\Generators\Generators\MigrationGenerator as BaseGenerator;
use Schema, Str, Artisan;

class MigrationGenerator extends BaseGenerator
{
	protected $mb;

	public function setModelBuilder(ModelBuilder $mb)
	{
		$this->mb = $mb;
	}


	public function generateFile()
	{
		$command = 'generate:migration';
		$table = $this->mb->getTable();



		$new = array();
		$fields = array();
		foreach($this->mb->getColumns() as $name => $column) {

			if(!Schema::hasColumn($table, $name)) {
				$new[] = $name;
				$format = sprintf('%s:%s', $name, $column->getType());

				if($column->isNullable()) {
					$format .= ':nullable';
				}
				$fields[] = $format;
			}

		}


		if(Schema::hasTable($table)) {
			$name = sprintf('add_%s_to_%s_table', implode('_', $new), Str::snake($table));

			if(!$fields) {
				return;
			}

		}
		else {
			$name = sprintf('create_%s_table', Str::snake($table));
		}


		$file = ucwords($name) . '.php';
		$path =  app_path() . '/database/migrations' . '/' . $file;
		$fields = implode(', ', $fields);

		$created = $this->parse($name, $fields)->make($path, null);


	}

}