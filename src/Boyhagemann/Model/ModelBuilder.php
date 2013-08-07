<?php

namespace Boyhagemann\Model;

use Boyhagemann\Form\FormBuilder;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Boyhagemann\Form\Element\InputElement;
use Boyhagemann\Form\Element\ModelElement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;
use Str,
    DB,
    Schema,
    App;

class ModelBuilder
{
    /**
     *
     * @var Model
     */
    protected $model;
    
    protected $formBuilder;


    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $modelPath = '/models';
    
    /**
     * @var string
     */
    protected $presenter;    

    /**
     *
     * @var type 
     */
    protected $parentClass = 'Eloquent';


    /**
     * @var Blueprint
     */
    protected $blueprint;

    /**
     * @var ClassGenerator
     */
    protected $generator;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var array
     */
    protected $relations = array();
    
    /**
     *
     * @var bool 
     */
    protected $timestamps = false;

    /**
     * @var array
     */
    public $rules = array();

    /**
     * @param FileGenerator $generator
     */
    public function __construct(FileGenerator $generator)
    {
        $this->generator = $generator;      
    }
    
    public function setFormBuilder(FormBuilder $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }
    
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }


    /**
     * 
     * @param type $name
     * @param type $element
     */
    public function postAddElement($name, InputElement $element)
    {        
		$options = $element->getOptions();
		$type = $element->getType();
   

        switch($type) {
            
            case 'text':                
                $this->column($name, 'string');
                break;
            
            case 'textarea':  
                $this->column($name, 'text');
                break;
            
            case 'checkbox':
            case 'percent':  
            case 'integer':  
                $this->column($name, 'integer');
                break;

			case 'select':
				if($this->hasRule($name, 'integer')) {
					$this->column($name, 'integer');
				}
				else {
					$this->column($name, 'string');
				}
				break;

			case 'modelSelect':
            	$this->column($name, 'integer');
			break;
         
        }
        
    }
    
    /**
     * 
     * @param type $name
     * @param \Boyhagemann\Form\Element\InputElement $element
     */
    public function postBuildElement($name, $element)
    {
        if ($element instanceof ModelElement && $element->getModel()) {
        
            if ($element->getOption('multiple')) {
                $this->createRelation($name, 'belongsToMany', $element->getModel(), $element->getAlias());
            }
            else {
                $this->createRelation($name, 'belongsTo', $element->getModel(), $element->getAlias());
            }
        }
        
        if ($element->getRules()) {
            $this->validate($name, $element->getRules());
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
            return $this->name;
    }

	/**
	 * @param $column
	 * @return array
	 */
	public function getRules($column)
	{
		if(!isset($this->rules[$column])) {
			return array();
		}

		return explode('|', $this->rules[$column]);
	}

	/**
	 * @param $column
	 * @param $rule
	 * @return bool
	 */
	public function hasRule($column, $rule)
	{
		return in_array($rule, $this->getRules($column));
	}

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
     * 
     * @param sting $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        $this->blueprint = new Blueprint($table);

        if (!Schema::hasTable($table)) {
            $this->blueprint->create();          
            $this->blueprint->increments('id');       
        }
        
        return $this;
    }
    
    /**
     * @param string $presenter
     * @return $this
     */
    public function presenter($presenter)
    {
        $this->presenter = $presenter;
        return $this;
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
    
    /**
     * 
     * @return $this
     */
    public function timestamps($timestamps = true)
    {
        $this->timestamps = $timestamps;
        if($timestamps && !Schema::hasTable($this->table)) {
            $this->blueprint->timestamps();
        }
        return $this;
    }

    /**
     * 
     * @param string $alias
     * @return ModelBuilder
     */
    public function relation($alias)
    {  
        return $this->relations[$alias];        
    }

    /**
     * 
     * @param string $name
     * @param string $type
     * @param string $model
     * @param string $alias
     * @return ModelBuilder
     */
    public function createRelation($name, $type, $model, $alias = null)
    {                         
        if(isset($this->relations[$name])) {
            return $this;
        }

        
        if(is_object($model)) {
            $model = get_class($model);
        }
        
        $left = $this->buildNameFromClass($this->name);
        $right = $this->buildNameFromClass($model);
        
        $table = $left . '_' . $name;
        
        $field = $left . '_id';        
        $field2 = $right . '_id';
                
        $relation = App::make('Boyhagemann\Model\Relation');
        $relation->table($table);
        $relation->type($type);
        $relation->name($model);
        
        if($alias) {
            $relation->alias($alias);
        }
                
        $blueprint = $relation->getBlueprint();
        
        switch($type) {

            case 'belongsToMany':
                if(!Schema::hasColumn($table, $field)) {
                    $blueprint->unsignedInteger($field); 
                    $blueprint->index($field);
                }
                if(!Schema::hasColumn($table, $field2)) {
                    $blueprint->unsignedInteger($field2);       
                    $blueprint->index($field2); 
                }
                break;
            
        }

        $this->relations[$name] = $relation;
        
        return $this->relations[$name];
    }
    
    /**
     * 
     * @param type $model
     * @return $this
     */
    public function hasMany($model)
    {
        return $this->createRelation($model, 'hasMany', $model);
    }


    /**
     * 
     * @param string $class
     * @return string
     */
    protected function buildNameFromClass($class)
    {
        $nameParts = explode('\\', $class);
        return strtolower(end($nameParts));
    }


    /**
     * @return Blueprint
     */
    public function getBlueprint()
    {
        return $this->blueprint;
    }
    
    /**
     * 
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param $field
     * @param $rules
     */
    public function validate($field, $rules)
    {
        $this->rules[$field] = $rules;
    }

    /**
     * @param string $name
     * @param string $type
     * @return $this
     */
    public function column($name, $type)
    {
        $this->columns[$name] = $type;

        if (!Schema::hasColumn($this->table, $name)) {
            $column = $this->getBlueprint()->$type($name);
            if (!isset($this->rules[$name]) || false !== strpos('required', $this->rules[$name])) {
                $column->nullable();
            }
        }
        
        return $this;
    }

    /**
     * @return Model
     */
    public function build()
    {
//        if(!file_exists($this->buildFilename()) || !Schema::hasTable($this->table)) {
//            $this->export();
//        }
        
        $this->model = App::make($this->name);
        
        return $this->model;
    }

    /**
     * Build the columns to the database
	 * @return $this
     */
    public function export()
    {      
        // When there is no class name, no file has to be written to disk
        if(!$this->name) {
            return;
        }
        
        if($this->getFormBuilder()) {
            foreach($this->getFormBuilder()->getElements() as $name => $element) {
                $this->postAddElement($name, $element);
                $this->postBuildElement($name, $element);
            }
        }
        
        $this->getBlueprint()->build(DB::connection(), DB::connection()->getSchemaGrammar());

        
        foreach ($this->relations as $relation) {
            $relation->export();
        }

		return $this;
    }

	/**
	 * @return $this
	 */
	public function exportFile()
	{
		$filename = $this->buildFilename();
		$contents = $this->buildFile();
		file_put_contents($filename, $contents);

		require_once $filename;

		return $this;
	}
    
    /**
     * 
     * @return string
     */
    public function buildFilename()
    {        
        $filename = app_path() .  '/' . trim($this->modelPath, '/');
        
        $parts = explode('\\', $this->name);
        for ($i = 0; $i < count($parts); $i++) {
            $filename .= '/' . $parts[$i];
            if ($i < count($parts) - 1) {
                @mkdir($filename);
            }
        }
        $filename .= '.php';
        
        return $filename;
    }
    
    /**
     * @return string
     */
    public function buildFile()
    {
        $file = $this->generator;
        $file->setClass($this->name);
        $class = current($file->getClasses());
        $class->setExtendedClass('\\' . ltrim($this->parentClass, '\\'));

        // Set the table name
        $class->addProperty('table', $this->table, PropertyGenerator::FLAG_PROTECTED);

        $class->addProperty('timestamps', $this->timestamps);

        // Set the rules
        $class->addProperty('rules', $this->rules);

        $class->addProperty('guarded', array('id'), PropertyGenerator::FLAG_PROTECTED);

        $fillable = array_keys($this->columns);
        $class->addProperty('fillable', $fillable, PropertyGenerator::FLAG_PROTECTED);

        if($this->presenter) {
            $file->setNamespace($class->getNamespaceName());
            $file->setUse('Robbo\Presenter\PresentableInterface');
            $class->setImplementedInterfaces(array('PresentableInterface'));
            
            $docblock = '@return \\' . $this->presenter;
            $body = sprintf('return new \%s($this);', $this->presenter);
            $class->addMethod('getPresenter', array(), null, $body, $docblock);
        }
        
        
        // Add elements, only for relationships
        foreach ($this->relations as $relation) {

            $name = $relation->getAlias();
            if($relation->getType() == 'belongsToMany') {
                $docblock = '@return \Illuminate\Database\Eloquent\Collection';
                $body = sprintf('return $this->%s(\'%s\', \'%s\');', $relation->getType(), $relation->getName(), $relation->getTable());
            }
            else {
                $docblock = '@return \\' . $relation->getName();
                $body = sprintf('return $this->%s(\'%s\');', $relation->getType(), $relation->getName());           
            }
            $class->addMethod($name, array(), null, $body, $docblock);
        }
        
        return $file->generate();
    }

}