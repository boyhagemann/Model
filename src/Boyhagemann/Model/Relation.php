<?php

namespace Boyhagemann\Model;

use Schema, DB;

class Relation extends ModelBuilder
{    
    protected $type;    
    protected $alias;
   
    public function getType()
    {
        return $this->type;
    }

    public function type($type)
    {
        $this->type = $type;
    }

    public function getAlias()
    {        
        if(!$this->alias) {
            return lcfirst(str_replace('\\', '', $this->table));
        }
        
        return $this->alias;
    }

    public function alias($alias)
    {
        $this->alias = $alias;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function export()
    {
        if($this->getType() == 'hasMany') {
            return;
        }
        
        if($this->getType() == 'belongsTo' && Schema::hasTable($this->table)) {
            return;
        }
        
        $this->getBlueprint()->build(DB::connection(), DB::connection()->getSchemaGrammar());    
    }

}