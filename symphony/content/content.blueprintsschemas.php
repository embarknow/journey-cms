<?php

use Embark\CMS\Fields\Controller as FieldController;
use Embark\CMS\Fields\FieldSchemaInterface;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Schemas\Schema;
use Embark\CMS\Schemas\FieldsList;

    require_once(LIB . '/class.administrationpage.php');
    require_once(LIB . '/class.messagestack.php');
    require_once(LIB . '/class.section.php');
    require_once(LIB . '/class.duplicator.php');
    require_once(LIB . '/class.entry.php');

    class ContentBlueprintsSchemas extends AdministrationPage {
        private $section;

        public function __viewIndex()
        {
            $callback = Administration::instance()->getPageCallback();
            $url = Administration::instance()->getCurrentPageURL();

            $links = $this->createElement('span');
            $links->appendChild(Widget::Anchor(
                __('Create New'), $url . '/new/', array(
                    'title' => __('Create a new section'),
                    'class' => 'create button'
                )
            ));

            $this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Schemas'))));
            $this->appendSubheading(__('Schemas'), $links);

            // Create table:
            $table = $this->createElement('table');
            $table->setAttribute('id', 'sections-list');
            $this->Form->appendChild($table);

            $table->appendChild(Widget::TableHead([
                array(__('Handle'), 'col'),
                array(__('Entries'), 'col')
            ]));

            $rows = $this->createElement('tbody');
            $table->appendChild($rows);

            foreach (SchemaController::findAll() as $section) {
                $row = $this->createElement('tr');
                $rows->appendChild($row);

                // Setup each cell:
                $row->appendChild(Widget::TableData(Widget::Anchor(
                    $section['resource']['handle'],
                    ADMIN_URL . "/blueprints/schemas/edit/{$section['resource']['handle']}/",
                    [
                        'class' => 'content'
                    ]
                )));
                $row->appendChild(Widget::TableData(Widget::Anchor(
                    (string)$section->countEntries(),
                    ADMIN_URL . "/blueprints/schemas/data/{$section['resource']['handle']}/"
                )));

                // Append checkbox:
                $row->firstChild->appendChild(Widget::Input(
                    "items[{$section['resource']['handle']}]", 'on', 'checkbox'
                ));
            }

            $actions = $this->createElement('div');
            $actions->setAttribute('class', 'actions');
            $this->Form->appendChild($actions);

            $actions->appendChild(Widget::Select('with-selected', [
                [null, false, __('With Selected...')],
                ['delete', false, __('Delete'), 'confirm'],
                ['delete-entries', false, __('Delete Entries'), 'confirm']
            ]));
            $actions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
        }

        public function __viewData()
        {
            $schema = SchemaController::read($this->_context[1]);

            $this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', [
                __('Symphony'),
                __('Schemas'),
                __('Data for %s', [$schema['resource']['handle']])
            ]));
            $this->appendSubheading(__('Data for %s', [$schema['resource']['handle']]));

            // Create table:
            $table = $this->createElement('table');
            $table->setAttribute('id', 'sections-list');
            $this->Form->appendChild($table);

            $head = Widget::TableHead([
                [__('Id'), 'col']
            ]);
            $table->appendChild($head);

            if ($schema['fields'] instanceof FieldsList) {
                foreach ($schema['fields']->findAll() as $field) {
                    $field['data']->appendColumns($head->firstChild);
                }
            }

            $rows = $this->createElement('tbody');
            $table->appendChild($rows);

            $actions = $this->createElement('div');
            $actions->setAttribute('class', 'actions');
            $this->Form->appendChild($actions);

            $actions->appendChild(Widget::Select('with-selected', [
                [null, false, __('With Selected...')],
                ['delete', false, __('Delete'), 'confirm'],
            ]));
            $actions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
        }

        public function __actionIndex()
        {
            var_dump('__actionIndex'); exit;

            $checked = is_array($_POST['items']) ? array_keys($_POST['items']) : null;

            if (is_array($checked) && !empty($checked)) {
                switch ($_POST['with-selected']) {
                    case 'delete':
                        $this->__actionDelete($checked, ADMIN_URL . '/blueprints/schemas/');
                        break;

                    case 'delete-entries':
                        $entries = Symphony::Database()->query(
                            sprintf(
                                "SELECT `id` FROM `entries` WHERE `section` IN ('%s')",
                                implode("', '", $checked)
                            )
                        );
                        if($entries->length() > 0){
                            foreach($entries as $e){
                                Entry::delete($e->id);
                            }
                        }
                        break;
                }
            }
        }

        public function __actionNew()
        {
            $this->__save();
        }

        public function __actionEdit()
        {
            if (isset($_POST['action']['save'])) {
                $this->__save();
            }

            if (isset($_POST['action']['delete'])) {
                $this->__delete();
            }
        }

        public function __delete()
        {
            $schema = SchemaController::read($this->_context[1]);

            if (SchemaController::delete($schema)) {
                redirect(ADMIN_URL . "/blueprints/schemas");
            }
        }

        public function __save()
        {
            $handle = Lang::createHandle($_POST['essentials']['handle']);
            $schema = new Schema();
            $action = 'created';
            $editing = false;
            $saved = false;

            if (isset($this->_context[1])) {
                $found = SchemaController::read($this->_context[1]);

                if ($found) {
                    $action = 'saved';
                    $schema = $found;
                    $editing = true;
                }
            }

            unset($schema['fields']);
            $schema->setDefaults();

            if (isset($_POST['fields'])) {
                foreach ($_POST['fields'] as $index => $fieldData) {
                    $field = FieldController::read($fieldData['type']);
                    $schema['fields'][$index] = $field;

                    if (empty($fieldData['guid'])) {
                        $fieldData['guid'] = $field->getGuid();
                    }

                    $field->setGuid($fieldData['guid']);

                    unset($fieldData['type'], $fieldData['guid']);

                    // Apply data:
                    foreach ($fieldData as $name => $value) {
                        $field[$name] = $value;
                    }

                    // Remove data that we do not want to save:
                    unset($field['resource']);
                    unset($field['name']);
                }
            }

            // Validate handle:
            if (0 === strlen($handle)) {
                $this->errors->handle = __('Handle is a required field.');
            }

            // No validation errors, save:
            if (!$this->errors->valid()) {
                if ($editing) {
                    $saved = SchemaController::update($schema, $handle);
                }

                else {
                    $saved = SchemaController::create($schema, $handle);
                }
            }

            // Save was a success:
            if ($saved) {
                $schema = SchemaController::read($handle);
                SchemaController::sync($schema);

                redirect(ADMIN_URL . "/blueprints/schemas/edit/{$handle}/:{$action}/");
            }
        }

        public function __actionDelete(array $sections, $redirect)
        {
            var_dump('__actionDelete'); exit;

            $success = true;

            $callback = Administration::instance()->getPageCallback();
            $current_page = $callback['pageroot'] . (isset($callback['context'][0]) && $callback['context'][0] != 'index' ? $callback['context'][0] . '/' : NULL);

            foreach($sections as $handle){
                try{
                    Section::delete(Section::loadFromHandle($handle));

                    ###
                    # Delegate: SectionPostDelete
                    Extension::notify(
                        'SectionPostDelete',
                        $current_page, array(
                            'handle' => $handle,
                        )
                    );

                }
                catch(SectionException $e){
                    $success = false;
                    $this->alerts()->append(
                        $e->getMessage(),
                        AlertStack::ERROR, $e
                    );
                }
                catch(Exception $e){
                    $success = false;
                    $this->alerts()->append(
                        __('An unknown error has occurred. <a class="more">Show trace information.</a>'),
                        AlertStack::ERROR, $e
                    );
                }
            }

            if ($success) {
                SectionIterator::clearCachedFiles();
                redirect($redirect);
            }
        }

        public function __viewNew()
        {
            if (!($this->section instanceof Schema)) {
                $this->section = new Schema;
            }

            $this->__form($this->section);
        }

        public function __viewEdit()
        {
            $this->__form(SchemaController::read($this->_context[1]), true);
        }

        protected function __form(Schema $schema, $editing = false)
        {
            // SchemaController::syncStats($schema);

            // Status message:
            $callback = Administration::instance()->getPageCallback();

            if (isset($callback['flag']) && !is_null($callback['flag'])) {
                switch($callback['flag']){
                    case 'saved':
                        $this->alerts()->append(
                            __(
                                'Schema updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all</a>',
                                [
                                    General::getTimeAgo(__SYM_TIME_FORMAT__),
                                    ADMIN_URL . '/blueprints/schemas/new/',
                                    ADMIN_URL . '/blueprints/schemas/',
                                ]
                            ),
                            AlertStack::SUCCESS
                        );
                        break;
                    case 'created':
                        $this->alerts()->append(
                            __(
                                'Schema created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all</a>',
                                [
                                    General::getTimeAgo(__SYM_TIME_FORMAT__),
                                    ADMIN_URL . '/blueprints/schemas/new/',
                                    ADMIN_URL . '/blueprints/schemas/',
                                ]
                            ),
                            AlertStack::SUCCESS
                        );
                        break;
                }
            }

            $layout = new Layout();
            $left = $layout->createColumn(Layout::SMALL);
            $right = $layout->createColumn(Layout::LARGE);

            if ($editing) {
                $this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', [
                    __('Symphony'),
                    __('Schemas'),
                    __('Edit %s', [$schema['resource']['handle']])
                ]));
                $this->appendSubheading(__('Edit %s', [$schema['resource']['handle']]));
            }

            else {
                $this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', [
                    __('Symphony'),
                    __('Schemas'),
                    __('New Schema')
                ]));
                $this->appendSubheading(__('New Schema'));
            }

            // Essentials:
            $fieldset = $this->createElement('fieldset');
            $fieldset->setAttribute('class', 'settings');
            $fieldset->appendChild(
                $this->createElement('h3', __('Essentials'))
            );

            $label = Widget::Label('Handle');
            $label->appendChild(Widget::Input('essentials[handle]', $schema['resource']['handle']));

            $fieldset->appendChild((
                isset($this->errors->handle)
                    ? Widget::wrapFormElementWithError($label, $this->errors->handle)
                    : $label
            ));

            $left->appendChild($fieldset);

            // Schema:
            $fieldset = $this->createElement('fieldset');
            $fieldset->setAttribute('class', 'settings');
            $fieldset->appendChild($this->createElement('h3', __('Schema')));

            $div = $this->createElement('div');
            $h3 = $this->createElement('h3', __('Fields'));
            $h3->setAttribute('class', 'label');
            $div->appendChild($h3);

            $duplicator = new Duplicator(__('Add Field'));
            $duplicator->setAttribute('id', 'section-duplicator');

            foreach (FieldController::findAll() as $field) {
                if (method_exists($field, 'appendSchemaSettings')) {
                    $item = $duplicator->createTemplate($field['name']);
                    $field->appendSchemaSettings($item, new MessageStack());
                }
            }

            if ($schema['fields'] instanceof FieldsList) {
                foreach ($schema['fields']->findAll() as $position => $field) {
                    if ($this->errors->{"field::{$position}"}) {
                        $messages = $this->errors->{"field::{$position}"};
                    }

                    else {
                        $messages = new MessageStack();
                    }

                    $item = $duplicator->createInstance($field['handle'], $field['name']);
                    $field->appendSchemaSettings($item, $messages);
                }
            }

            $duplicator->appendTo($fieldset);
            $right->appendChild($fieldset);

            $layout->appendTo($this->Form);

            $div = $this->createElement('div');
            $div->setAttribute('class', 'actions');
            $div->appendChild(
                Widget::Submit(
                    'action[save]',
                    (
                        $editing
                            ? __('Save Changes')
                            : __('Create Schema')
                    ),
                    [
                        'accesskey' => 's'
                    ]
                )
            );

            if ($this->_context[0] == 'edit') {
                $div->appendChild(
                    Widget::Submit(
                        'action[delete]', __('Delete'),
                        [
                            'class' => 'confirm delete',
                            'title' => __('Delete this section'),
                        ]
                    )
                );
            }

            $this->Form->appendChild($div);
        }
    }
