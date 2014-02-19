<?php

require 'bootstrap.php';

/**
 * ExtendedgroupsPlugin.class.php
 *
 * ...
 *
 * @author  Florian Bieringer <florian.bieringer@uni-passau.de>
 * @version 0.1a
 */
class ExtendedgroupsPlugin extends StudIPPlugin implements StandardPlugin, SystemPlugin {

    public function __construct() {
        parent::__construct();

        /* $navigation = new AutoNavigation(_('ExtendedGroups'));
          $navigation->setURL(PluginEngine::GetURL($this, array(), 'show'));
          $navigation->setImage(Assets::image_path('blank.gif'));
          Navigation::addItem('/extendedgroupsplugin', $navigation); */

        PageLayout::addStylesheet($this->getPluginURL() . '/assets/style.css');
        PageLayout::addScript($this->getPluginURL() . '/assets/application.js');
    }

    public function initialize() {
        
    }

    public function getTabNavigation($course_id) {
        $tab = new AutoNavigation(_("Gruppen"), PluginEngine::getLink($this, array(), "show"));
        $tab->setImage(Assets::image_path("icons/16/white/group2"));
        $tab->setActiveImage("icons/16/black/group2");
        $this->tab = $tab;
        return array('teams' => $tab);
    }

    public function getNotificationObjects($course_id, $since, $user_id) {
        return array();
    }

    public function getIconNavigation($course_id, $last_visit, $user_id) {
        // ...
    }

    public function getInfoTemplate($course_id) {
        // ...
    }

    public function perform($unconsumed_path) {
        $this->setupAutoload();
        $dispatcher = new Trails_Dispatcher(
                $this->getPluginPath(), rtrim(PluginEngine::getLink($this, array(), null), '/'), 'show'
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }

    private function setupAutoload() {
        if (class_exists("StudipAutoloader")) {
            StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
        } else {
            spl_autoload_register(function ($class) {
                include_once __DIR__ . $class . '.php';
            });
        }
    }

}
