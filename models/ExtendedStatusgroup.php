<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExtendedStatusgroup
 *
 * @author intelec
 */
class ExtendedStatusgroup extends Statusgruppen {
    
    public function __construct($id = null) {
        
        parent::__construct($id);
    }

    // Seminar groups have no datafields
    public function getDatafields() {
        return array();
    }

    /**
     * Function of parent is broken ;)
     */
    public function hasSpace() {
        return $this->selfassign && (!$this->size || count($this->members) < $this->size);
    }

}
