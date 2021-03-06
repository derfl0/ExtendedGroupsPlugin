<?php

class ShowController extends StudipController {

    public function __construct($dispatcher) {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;
    }

    /**
     * {@inheritdoc }
     */
    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);

        if (Request::submitted('abort')) {
            $this->redirect('show/index');
        }

        $this->user_id = $GLOBALS['user']->user_id;

        // Set pagelayout
        PageLayout::setHelpKeyword("Basis.Allgemeines");
        PageLayout::setTitle(_("Verwaltung von Funktionen und Gruppen"));
        Navigation::activateItem('/course/groups');

        $this->setType();

        // encode
        if (Request::isXhr()) {
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->set_layout(null);
            $this->group = new ExtendedStatusgroup(Request::get('group'));
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
            PageLayout::addScript(Assets::javascript_path('app_admin_statusgroups.js'));
        }
    }

    /**
     * Basic display of the groups
     */
    public function index_action() {
        $this->checkForChangeRequests();
        $this->updateTermine();

        // Do some basic layouting
        PageLayout::addScript('jquery/jquery.tablednd.js');
        PageLayout::addStylesheet('jquery-nestable.css');
        PageLayout::addScript('jquery/jquery.nestable.js');
        $this->setInfobox();
        $this->setAjaxPaths();

        // Collect all groups
        $this->loadGroups();

        // Check if the viewing user should get the admin interface
        $this->tutor = $this->type['edit']($this->user_id);

        // If we have a tutor we need to find all users without statusgroup
        if ($this->tutor) {
            $stmt = DBManager::get()->prepare("SELECT a.username FROM seminar_user
JOIN auth_user_md5 a USING (user_id)
WHERE seminar_id = ?
AND (status = 'autor' OR status = 'user')
AND user_id NOT IN
(SELECT user_id FROM statusgruppen st
JOIN statusgruppe_user su USING (statusgruppe_id)
WHERE st.range_id = ?)");
            $stmt->execute(array(Course::findCurrent()->id, Course::findCurrent()->id));
            $informUserLink = URLHelper::getLink('sms_send.php', array('filter' => 'send_sms_to_all',
                        'rec_uname' => $stmt->fetchAll(PDO::FETCH_COLUMN, 0)));
            if ($stmt->rowCount()) {
                $this->addToInfobox(_('Aktionen'), "<a title='" . _('Teilnehmenden ohne Gruppe eine Nachricht schicken') . "' href='" . $informUserLink . "'>" . _('Teilnehmenden ohne Gruppe eine Nachricht schicken') . " (" . $stmt->rowCount() . ")</a>", 'icons/16/black/mail.png');
                $this->addToInfobox(_('Aktionen'), "<a title='" . _('Teilnehmenden ohne Gruppe aus Veranstaltung entfernen') . "' class='modal' href='" . $this->url_for('show/removeUsersWithoutGroup') . "'>" . _('Teilnehmenden ohne Gruppe aus Veranstaltung entfernen') . " (" . $stmt->rowCount() . ")</a>", 'icons/16/black/trash.png');
            }
        }
    }

    public function termine_action($group_id) {
        $db = DBManager::get();
        $this->termine = $db->fetchAll('SELECT t.*, GROUP_CONCAT(statusgruppe_id) as statusgroups FROM termine t LEFT JOIN termin_related_groups USING (termin_id) WHERE range_id = ? GROUP BY termin_id ORDER BY date', array($_SESSION['SessionSeminar']));
        foreach ($this->termine as &$termin) {
            $termin['display'] = strftime('%a. %d.%m.%y %H:%M', $termin['date']) . "-" . strftime('%H:%M', $termin['end_time']);
            if (!$termin['statusgroups'] || strpos($termin['statusgroups'], $group_id) !== false) {
                $termin['checked'] = 'checked';
            }
        }
        $this->group = $group_id;

        // Redirect to viewonly if not editable
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar'])) {
            $this->render_action('showTermine');
        }
    }

    public function removeUsersWithoutGroup_action() {

        if (!$GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException(_('Keine Berechtigung'));
        }
        
        // Mission abort
        if (Request::submitted('abort')) {
            $this->redirect('show/index');
            return 0;
        }

        $stmt = DBManager::get()->prepare("SELECT a.* FROM seminar_user
JOIN auth_user_md5 a USING (user_id)
WHERE seminar_id = ?
AND (status = 'autor' OR status = 'user')
AND user_id NOT IN
(SELECT user_id FROM statusgruppen st
JOIN statusgruppe_user su USING (statusgruppe_id)
WHERE st.range_id = ?)");
        $stmt->execute(array(Course::findCurrent()->id, Course::findCurrent()->id));

        // It's execution time
        if (Request::submitted('execute')) {
            while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                CourseMember::deleteBySQL('seminar_id = ? AND user_id = ?', array(Course::findCurrent()->id, $result['user_id']));
            }
            $this->redirect('show/index');
            return 0;
        }

        $this->users = array();
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->users[] = User::import($result);
        }
    }

    private function updateTermine() {

        // If we have an updaterequest
        if (Request::submitted('termine')) {

            CSRFProtection::verifySecurityToken();

            // Cache group id
            $group = Request::get('group');

            if (!$GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar'])) {
                throw new AccessDeniedException();
            }

            // Prepare SQL
            $db = DBManager::get();
            $hasEntry = $db->prepare('SELECT 1 FROM termin_related_groups WHERE termin_id = ? LIMIT 1');
            $insertEntry = $db->prepare('REPLACE INTO termin_related_groups (termin_id, statusgruppe_id) VALUES (?,?)');
            $deleteEntry = $db->prepare('DELETE FROM termin_related_groups WHERE termin_id = ? AND statusgruppe_id = ?');
            $insertOthers = $db->prepare('INSERT INTO termin_related_groups (SELECT ? as termin_id, statusgruppe_id FROM statusgruppen WHERE range_id = ? AND statusgruppe_id != ? )');

            // Work all Termine
            foreach (Request::getArray('termine') as $termin => $type) {
                if ($type == 1) {
                    $hasEntry->execute(array($termin));
                    if ($hasEntry->fetch(PDO::FETCH_COLUMN)) {
                        $insertEntry->execute(array($termin, $group));
                    }
                } else {
                    $deleteEntry->execute(array($termin, $group));
                    $hasEntry->execute(array($termin));
                    if (!$hasEntry->fetch(PDO::FETCH_COLUMN)) {
                        $insertOthers->execute(array($termin, $_SESSION['SessionSeminar'], $group));
                    }
                }
            }

            // Fetch all termine that have not been send
        }
    }

    /**
     * Interface to edit a group or create a new one.
     * 
     * @param string group id
     */
    public function editGroup_action($group_id = null) {
        $this->group = new ExtendedStatusgroup($group_id);
        $this->loadGroups();
    }

    /**
     * Interface to sort groups
     */
    public function sortGroups_action() {
        PageLayout::addStylesheet('jquery-nestable.css');
        PageLayout::addScript('jquery/jquery.nestable.js');
        $this->loadGroups();
    }

    /**
     * Action to add multiple members to a group.
     * 
     * @param string group id
     */
    public function memberAdd_action($group_id = null) {
        // load selected group
        $this->group = new ExtendedStatusgroup($group_id);

        // set infobox
        $this->setInfoBoxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Aktionen'), "<a href='" . $this->url_for('show') . "'>" . _('Zur�ck') . "</a>", 'icons/16/black/arr_1left.png');

        // load current group members on first call
        $this->selectedPersons = array();
        if (!Request::get('not_first_call')) {
            $this->currentGroupMembers = array();
            $this->selectedPersons = User::findMany($this->group->members->pluck('user_id'));
        } else {
            // Load selected persons
            $this->selectedPersonsHidden = unserialize(studip_utf8decode(Request::get('search_persons_selected_hidden')));
            $this->selectedPersons = User::findMany($this->selectedPersonsHidden);
        }

        // Search
        $this->search = Request::isXHR() ? studip_utf8decode(Request::get('freesearch')) : Request::get('freesearch');
        $lastSearch = Request::isXHR() ? studip_utf8decode(Request::get('last_search_hidden')) : Request::get('last_search_hidden');
        $this->searchPreset = Request::get('search_preset');
        $lastSearchPreset = Request::isXHR() ? studip_utf8decode(Request::get('last_search_preset')) : Request::get('last_search_preset');
        if (($this->searchPreset == "inst" && $lastSearchPreset != "inst") || !Request::get('not_first_call')) { // ugly
            // search with preset
            $this->selectablePersons = User::findMany(Course::find($_SESSION['SessionSeminar'])->members->pluck('user_id'));
            // reset search input, because a preset is used
            $this->search = "";
        } elseif ($this->search != $lastSearch || Request::submitted('submit_search')) {
            // search with free text input
            $result = PermissionSearch::get('user')->getResults($this->search, array('permission' => array('autor', 'tutor', 'dozent'), 'exclude_user' => array()));
            $this->selectablePersons = User::findMany($result);
            // reset preset
            $this->searchPreset = "";
        } else {
            // otherwise restore selectable persons
            $this->selectablePersonsHidden = unserialize(studip_utf8decode(Request::get('search_persons_selectable_hidden')));
            foreach ($this->selectablePersonsHidden as $user_id) {
                $this->selectablePersons[] = new User($user_id);
            }
        }

        // select person
        if (Request::submitted('search_persons_add')) {
            foreach (Request::optionArray('search_persons_selectable') as $user_id) {
                $this->selectedPersons[] = new User($user_id);
            }
        }

        // deselect person
        if (Request::submitted('search_persons_remove')) {
            foreach (Request::optionArray('search_persons_selected') as $user_id) {
                foreach ($this->selectedPersons as $key => $value) {
                    if ($value->id == $user_id) {
                        unset($this->selectedPersons[$key]);
                    }
                }
                $this->selectablePersons[] = new User($user_id);
            }
        }

        // remove already selected persons from selectable
        foreach ($this->selectedPersons as $user) {
            foreach ($this->selectablePersons as $key => $value) {
                if ($value->id == $user->id) {
                    // delete from selectable persons
                    unset($this->selectablePersons[$key]);
                }
            }
        }

        // save changes
        if (Request::submitted('save')) {

            $this->countRemoved = 0;
            CSRFProtection::verifyUnsafeRequest();

            // delete users from group if removed
            $currentMembers = array();
            foreach ($this->group->members as $member) {
                $isRemoved = true;
                foreach ($this->selectedPersons as $user) {
                    if ($member->user_id == $user->id) {
                        $isRemoved = false;
                    }
                }

                if ($isRemoved) {
                    //exit("DELETED");
                    $this->group->removeUser($member->user_id);
                    $this->type['after_user_delete']($member->user_id);
                    //$this->afterFilter();
                    $this->countRemoved++;
                }
            }

            // add new users
            $this->countNew = 0;

            foreach ($this->selectedPersons as $user) {
                if (!$this->group->isMember($user->id)) {
                    //exit("ADDED");
                    $new_user = new StatusgruppeUser(array($this->group->id, $user->id));
                    $new_user->store();
                    $this->type['after_user_add']($user->id);
                    $this->countNew++;
                }
            }

            $this->selectedPersons = array();
            $this->selectablePersons = array();

            // reload current group members
            $this->group = new ExtendedStatusgroup($group_id);
            $this->currentGroupMembers = array();
            foreach ($this->group->members as $member) {
                $user = new User($member->user_id);
                $this->selectedPersons[] = $user;
            }
            PageLayout::postMessage(MessageBox::success(_('Die Mitglieder wurden gespeichert.')));
            $this->redirect('show/index#group-' . $group_id);
        }


        // abort changes
        if (Request::submitted('abort')) {
            $this->redirect('show/index');
        }

        $this->selectablePersons = new SimpleCollection($this->selectablePersons);
        $this->selectedPersons = new SimpleCollection($this->selectedPersons);
        // generate hidden form data to remember current state
        $this->selectablePersonsHidden = $this->selectablePersons->pluck('id');
        $this->selectedPersonsHidden = $this->selectedPersons->pluck('id');
        $this->selectablePersons->orderBy('nachname, vorname');
        $this->selectedPersons->orderBy('nachname, vorname');
        // set layout
        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            $this->title = _('Mitglieder verwalten');
        }
    }

    public function leaveGroup_action($id) {
        global $user;
        $group = ExtendedStatusgroup::find($id);
        if ($group->selfassign) {
            $group->removeUser($user->user_id);
        }
        $this->redirect('show/index');
    }

    public function joinGroup_action($id) {
        global $user, $perm;
        $group = ExtendedStatusgroup::find($id);
        if ($perm->have_studip_perm('autor', $_SESSION['SessionSeminar'])) {
            $group->addUser($user->user_id);
        }
        $this->redirect('show/index');
    }

    /**
     * Ajax action to move a user
     */
    public function move_action() {
        $this->check('edit');
        $GLOBALS['perm']->check('tutor');
        $group = Request::get('group');
        $user_id = Request::get('user');
        $pos = Request::get('pos');

        // Load statusgroup
        $statusgroup = new ExtendedStatusgroup($group);

        /*
         * If we dragged the poor guy into the waitinglist, we need to decrease
         * his position by one because the header is not a real place
         */
        if ($pos > $statusgroup->size) {
            $pos--;
        }

        // Nobody should be dragged to a zero place
        $pos = max(array(0, $pos));

        $statusgroup->moveUser($user_id, $pos);
        $this->type['after_user_move']($user_id);
        $this->users = $statusgroup->members;
        $this->afterFilter();
    }

    /**
     * Ajaxaction to add a user
     */
    public function add_action() {
        $this->check('edit');
        $group = Request::get('group');
        $user_id = Request::get('user');
        $user = new StatusgruppeUser(array($group, $user_id));
        $user->store();
        $statusgroup = new ExtendedStatusgroup($group);
        $this->users = $statusgroup->members;
        $this->type['after_user_add']($user_id);
        $this->afterFilter();
    }

    /**
     * Ajaxaction to delete a user
     */
    public function delete_action($group_id, $user_id) {
        $this->check('edit');
        $this->group = new ExtendedStatusgroup($group_id);
        $this->user = new User($user_id);
        if (Request::submitted('confirm')) {
            $this->group->removeUser($user_id);
            $this->type['after_user_delete']($user_id);
            $this->afterFilter();
        }
    }

    /**
     * Delete a group
     */
    public function deleteGroup_action($group_id) {
        $this->check('edit');
        $this->group = new ExtendedStatusgroup($group_id);
        if (Request::submitted('confirm')) {
            CSRFProtection::verifySecurityToken();

            // move all subgroups to the parent
            $children = SimpleORMapCollection::createFromArray($this->group->children);
            $children->setValue('range_id', $this->group->range_id);
            $children->store();

            //remove users
            $this->group->removeAllUsers();

            //goodbye group
            $this->group->delete();
            $this->redirect('show/index');
        }
    }

    /**
     * Delete a group
     */
    public function sortAlphabetic_action($group_id) {
        $this->check('edit');
        $this->group = new ExtendedStatusgroup($group_id);
        if (Request::submitted('confirm')) {
            CSRFProtection::verifySecurityToken();
            $this->group->sortMembersAlphabetic();
            $this->redirect('show/index');
        }
    }

    /**
     * Action to select institute. This should be put somewhere else since we
     * have to do this on EVERY institute page
     */
    public function selectInstitute_action() {
        
    }

    /**
     * Action to truncate a group
     * @param type $id the group id
     */
    public function truncate_action($id) {
        $this->check('edit');
        $this->group = new ExtendedStatusgroup($id);
        if (Request::submitted('confirm')) {
            CSRFProtection::verifySecurityToken();
            $this->group->removeAllUsers();
            $this->redirect('show/index');
        }
    }

    /*     * ********************************
     * ***** PRIVATE HELP FUNCTIONS ****
     * ******************************** */

    /*
     * Loads groups from the database.
     */

    private function loadGroups() {
        $this->groups = ExtendedStatusgroup::findBySQL('range_id = ? ORDER BY position', array($_SESSION['SessionSeminar']));
    }

    /*
     * Updates groups recursivly.
     */

    private function updateRecoursive($obj, $parent) {
        $i = 0;
        if ($obj) {
            foreach ($obj as $group) {
                $statusgroup = new ExtendedStatusgroup($group->id);
                $statusgroup->range_id = $parent;
                $statusgroup->position = $i;
                $statusgroup->store();
                $this->updateRecoursive($group->children, $group->id);
                $i++;
            }
        }
    }

    /*
     * Renders an action (ajax) or redirects to the statusgroup index page (no ajax).
     */

    private function afterFilter() {

        // Load rights
        $this->tutor = $this->type['edit']($this->user_id);

        if (Request::isXhr()) {
            $this->render_action('_members');
        } else {
            $this->redirect('show');
        }
    }

    /*
     * Sets the urls for ajax calls.
     */

    private function setAjaxPaths() {
        $this->path['ajax_move'] = $this->url_for('show/move');
        $this->path['ajax_add'] = $this->url_for('show/add');
        $this->path['ajax_search'] = $this->url_for('show/search');
    }

    /*
     * Since we dont want an ugly tree display but we want numberation we
     * "unfold" the groups tree
     */

    private function unfoldGroup(&$list, $groups) {
        if (is_array($groups)) {
            $groups = SimpleORMapCollection::createFromArray($groups);
        }
        foreach ($groups->orderBy('position') as $group) {
            $list[] = $group;
            $this->unfoldGroup($list, $group->children, $newpre);
        }
    }

    /*
     * Sets the content of the infobox.
     */

    private function setInfoBox() {
        $this->setInfoBoxImage('infobox/groups.jpg');

        if ($this->type['edit']($this->user_id)) {
            $this->addToInfobox(_('Aktionen'), "<a title='" . _('Neue Gruppe anlegen') . "' class='modal' href='" . $this->url_for("show/editGroup") . "'>" . _('Neue Gruppe anlegen') . "</a>", 'icons/16/black/add/group3.png');
            $this->addToInfobox(_('Aktionen'), "<a title='" . _('Gruppenreihenfolge �ndern') . "' class='modal' href='" . $this->url_for("show/sortGroups") . "'>" . _('Gruppenreihenfolge �ndern') . "</a>", 'icons/16/black/arr_2down.png');
            $this->addToInfobox(_('Icons'), _("Diese Gruppe ist f�r Benutzer sichtbar"), 'icons/16/grey/visibility-visible.png');
            $this->addToInfobox(_('Icons'), _("Diese Gruppe ist f�r Benutzer unsichtbar"), 'icons/16/grey/visibility-invisible.png');
        }
        $this->addToInfobox(_('Icons'), _("Diese Gruppe ist offen. Benutzer k�nnen sich jederzeit eintragen"), 'icons/16/grey/lock-unlocked.png');
        $this->addToInfobox(_('Icons'), _("Diese Gruppe ist geschlossen. Benutzer k�nnen sich nicht selbst�ndig in dieser Gruppe anmelden"), 'icons/16/grey/lock-locked.png');
        $this->addToInfobox(_('Icons'), _("Diese Gruppe ist exklusiv. Benutzer k�nnen sich maximal in einer exklusiven Gruppe anmelden"), 'icons/16/grey/star.png');
        $this->addToInfobox(_('Icons'), _("Diese Gruppe verf�gt �ber eine Warteliste. Benutzer k�nnen sich �ber die eigentliche Gruppengr��e hinaus eintragen und r�cken automatisch nach"), 'icons/16/grey/log.png');
    }

    /*
     * Checks if a group should be updated from a request
     */

    private function checkForChangeRequests() {
        if (Request::submitted('save')) {
            $this->check('edit');
            $group = new ExtendedStatusgroup(Request::get('id'));
            if ($group->isNew()) {
                $group->range_id = $_SESSION['SessionSeminar'];
            }
            $group->name = Request::get('name');
            $group->name_w = Request::get('name_w');
            $group->name_m = Request::get('name_m');
            $group->size = Request::get('size');
            $group->range_id = Request::get('range_id') ? : $group->range_id;
            $group->position = Request::get('position') ? : $group->position;
            $group->selfassign = Request::get('selfassign');
            $group->additional->waitinglist = Request::submitted('waitinglist');
            $group->additional->visible = Request::submitted('visible');
            $group->store();
            $group->setDatafields(Request::getArray('datafields') ? : array());
        }
        if (Request::submitted('order')) {
            $this->check('edit');
            $newOrder = json_decode(Request::get('ordering'));
            $this->updateRecoursive($newOrder, $_SESSION['SessionSeminar']);
        }
    }

    /*
     * Checks if the current user has the specific $rights
     */

    private function check($rights) {
        if (!$this->type[$rights]($this->user_id)) {
            die;
        }
    }

    /*
     * This sets the type of statusgroup. By now it only supports
     * Inst statusgroup but could be extended
     */

    private function setType() {
        $_SESSION['SessionSeminar'] = Request::option('admin_inst_id') ? : $_SESSION['SessionSeminar'];
        if (get_object_type($_SESSION['SessionSeminar'], array('inst', 'fak'))) {
            $type = 'inst';
        }
        $types = $this->types();
        if (!$type || Request::submitted('type') && $type != Request::get('type')) {
            $types[Request::get('type')]['redirect']();
        } else {
            $this->type = $types[$type];
        }
    }

    /*
     * This is the rest of the idea we could use statusgroups on other pages.
     * navigation and redirect to selection page must move here if the
     * statusgroupspage is reused
     *
     * @return type
     */

    private function types() {
        return array(
            'inst' => array(
                'name' => _('Institut'),
                'after_user_add' => function ($user_id) {
            $newInstUser = new CourseMember(array($user_id, $_SESSION['SessionSeminar']));
            if ($newInstUser->isNew()) {
                $user = new User($user_id);
                $newInstUser->status = 'autor';
                if ($newInstUser->store()) {
                    StudipLog::SEM_USER_ADD($_SESSION['SessionSeminar'], $user->id, 'autor');
                }
            }
        },
                'after_user_delete' => function ($user_id) {
            null;
        },
                'after_user_move' => function ($user_id) {
            null;
        },
                'view' => function ($user_id) {
            return true;
        },
                'needs_size' => true,
                'needs_self_assign' => true,
                'edit' => function ($user_id) {
            return $GLOBALS['perm']->have_studip_perm('dozent', $_SESSION['SessionSeminar']) && !LockRules::Check($_SESSION['SessionSeminar'], 'groups');
        },
                'redirect' => function () {
            $GLOBALS['view_mode'] = "inst";
            require_once 'lib/admin_search.inc.php';
            include 'lib/include/html_head.inc.php';
            include 'lib/include/header.php';
            include 'lib/include/admin_search_form.inc.php';  // will not return
            die(); //must not return
        },
                'groups' => array(
                    'members' => array(
                        'name' => _('Mitglieder'),
                    ))
            )
        );
    }

    // customized #url_for for plugins
    function url_for($to) {
        $args = func_get_args();

        # find params
        $params = array();
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        # urlencode all but the first argument
        $args = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->dispatcher->plugin, $params, join('/', $args));
    }

}
