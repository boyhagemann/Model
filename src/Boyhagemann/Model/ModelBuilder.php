<?php

namespace Boyhagemann\Model;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Boyhagemann\Form\Element\InputElement;
use Boyhagemann\Form\Element\ModelElement;
use Illuminate\Database\Schema\Blueprint;
use Event,
    DB,
    Schema,
    App;

class ModelBuilder
{
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
    protected $modelPath = 'app/models';
    
    /**
     * @var string
     */
    protected $presenter;    

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
        
        Event::listen('formBuilder.addElement.post', array($this, 'postAddElement'));
        Event::listen('formBuilder.buildElement.post', array($this, 'postBuildElement'));        
    }
    
    /**
     * 
     * @param type $name
     * @param type $element
     * @param type $formBuilder
     */
    public function postAddElement($name, InputElement $element, $formBuilder)
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
            
            case 'percent':  
            case 'integer':  
                $this->column($name, 'integer');
                break;
            
			case 'select':
			case 'modelSelect':
            case 'choice':
                
                if(!isset($options['multiple']) || $options['multiple'] == false) {
                    $this->column($name, 'integer');          
                }
                break;           
        }
        
    }
    
    /**
     * 
     * @param type $name
     * @param \Boyhagemann\Crud\FormBuilder\InputElement $element
     * @param type $formBuilder
     * @param type $formFactory
     */
    public function postBuildElement($name, $element, $formBuilder, $formFactory)
    {
        if ($element instanceof ModelElement && $element->getModel()) {

            if ($element->getOption('multiple')) {
                $this->createRelation($name, 'belongsToMany', $element->getModel());
            }
            else {
                $this->createRelation($name, 'belongsTo', $element->getModel());
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
     * @param string|Model $model
     * @return ModelBuilder
     */
    public function createRelation($alias, $type, $model)
    {                 
        if(is_string($model)) {
            $model = App::make($model);
        }
        
        $left = $this->buildNameFromClass($this->name);
        $right = $this->buildNameFromClass(get_class($model));
        
        $table = $left . '_' . $alias;
        
        $field = $left . '_id';        
        $field2 = $right . '_id';
                
        $relation = App::make('Boyhagemann\Model\Relation');
        $relation->table($table);
        $relation->setType($type);
        $relation->name(get_class($model));
                
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

        $this->relations[$alias] = $relation;
        
        return $this->relations[$alias];
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
     * Build the columns to the database
     */
    public function export()
    {
        // When there is no class name, no file has to be written to disk
        if(!$this->name) {
            return;
        }

        $this->getBlueprint()->build(DB::connection(), DB::connection()->getSchemaGrammar());

        $parts = explode('\\', $this->name);
        $filename = '../' . $this->modelPath;
        for ($i = 0; $i < count($parts); $i++) {
            $filename .= '/' . $parts[$i];
            if ($i < count($parts) - 1) {
                @mkdir($filename);
            }
        }
        $filename .= '.php';

        $contents = $this->buildFile();
        file_put_contents($filename, $contents);
        require_once $filename;

        foreach ($this->relations as $relation) {
            $relation->export();
        }
    }

    /**
     * @return Model
     */
    public function build()
    {
        if(!class_exists($this->name)) {
            $this->export();
        }
        
        return App::make($this->name);
    }

    /**
     * @return string
     */
    public function buildFile()
    {
        $file = $this->generator;
        $file->setClass($this->name);
        $class = current($file->getClasses());
        $class->setExtendedClass('\Eloquent');

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
        foreach ($this->relations as $alias => $relation) {

            $docblock = '@return \Illuminate\Database\Eloquent\Collection';
            $body = sprintf('return $this->%s(\'%s\', \'%s\');', $relation->getType(), $relation->getName(), $relation->getTable());
            $class->addMethod($alias, array(), null, $body, $docblock);
        }
        
        return $file->generate();
    }

}