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
    
    // Seminar groups have no datafields
    public function getDatafields() {
        return array();
    }
    
    // Leave a statusgroup
    public function leave() {
        
    }
}
