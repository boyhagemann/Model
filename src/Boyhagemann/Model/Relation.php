<?php

namespace Boyhagemann\Model;

use Schema, DB;

class Relation extends ModelBuilder
{    
    protected $type;
    
    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
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
        if($this->getType() == 'belongsToMany' && Schema::hasTable($this->table)) {
            return;
        }
        
        $this->getBlueprint()->build(DB::connection(), DB::connection()->getSchemaGrammar());    
    }

}