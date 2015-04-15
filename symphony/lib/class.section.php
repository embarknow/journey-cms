<?php

require_once 'class.field.php';
require_once 'class.layout.php';

class SectionException extends Exception
{
}

class SectionFilterIterator extends FilterIterator
{
    public function __construct($path)
    {
        parent::__construct(new DirectoryIterator(realpath($path)));
    }

    public function accept()
    {
        if ($this->isDir() == false && preg_match('/^([^.]+)\.xml$/i', $this->getFilename())) {
            return true;
        }
        return false;
    }
}

class SectionIterator extends ArrayIterator
{
    protected static $cache;
    protected static $handles;
    protected static $objects;
    protected static $sections;

    public static function buildCache()
    {
        $cache = self::$cache = new Cache(Cache::SOURCE_CORE, 'sections');
        $handles = $cache->{'handles'};
        $sections = $cache->{'sections'};

        if (isset(self::$objects) === false) {
            self::$objects = array();
        }

        if (empty($handles) || empty($sections)) {
            $handles = $sections = [];
            $extensions = new ExtensionQuery();
            $extensions->setFilters(array(
                ExtensionQuery::STATUS =>    Extension::STATUS_ENABLED
            ));

            Profiler::begin('Discovering sections');

            foreach (new SectionFilterIterator(SECTIONS) as $file) {
                Profiler::begin('Discovered section %section');

                $path = $file->getPathName();
                $handle = basename($path, '.xml');
                $sections[$path] = true;
                $handles[$handle] = $path;

                Profiler::store('section', $handle, 'system/section');
                Profiler::store('location', $path, 'system/resource action/discovered');
                Profiler::notice('Section location cached for future use.');
                Profiler::end();
            }

            foreach ($extensions as $extension) {
                if (is_dir($extension->path . '/sections') === false) {
                    continue;
                }

                // Extension will tell us about it's own sections:
                if ($extension instanceof ExtensionWithSectionsInterface) {
                    foreach ($extension->includeSections() as $path) {
                        Profiler::begin('Discovered section %section');

                        $path = realpath($path);
                        $handle = basename($path, '.xml');
                        $sections[$path] = true;
                        $handles[$handle] = $path;

                        Profiler::store('section', $handle, 'system/section');
                        Profiler::store('location', $path, 'system/resource action/discovered');
                        Profiler::notice('Section location cached for future use.');
                        Profiler::end();
                    }
                }

                // Old style, do the work for the extension:
                else {
                    foreach (new SectionFilterIterator($extension->path . '/sections') as $file) {
                        Profiler::begin('Discovered section %section');

                        $path = $file->getPathName();
                        $handle = basename($path, '.xml');
                        $sections[$path] = true;
                        $handles[$handle] = $path;

                        Profiler::store('section', $handle, 'system/section');
                        Profiler::store('location', $path, 'system/resource action/discovered');
                        Profiler::notice('Section location cached for future use.');
                        Profiler::end();
                    }
                }
            }

            $cache->{'handles'} = $handles;
            $cache->{'sections'} = $sections;

            Profiler::end();
        }

        self::$handles = $handles;
        self::$objects = $objects;
        self::$sections = $sections;
    }

    public static function clearCachedFiles()
    {
        $cache = new Cache(Cache::SOURCE_CORE, 'sections');
        $cache->purge();

        self::$handles = array();
        self::$sections = array();
    }

    public function __construct()
    {
        if (empty(self::$sections)) {
            self::buildCache();
        }

        parent::__construct(self::$sections);
    }

    public function current()
    {
        $path = $index = parent::key();

        if (isset(self::$handles[$index])) {
            $path = self::$handles[$index];
        }

        if (isset(self::$objects[$path]) === false) {
            Profiler::begin('Loaded section %section');

            $section = Section::loadFromFile($path);
            self::$objects[$path] = $section;

            Profiler::store('section', basename($path, '.xml'), 'system/section');
            Profiler::store('location', $path, 'system/resource action/loaded');
            Profiler::end();
        }

        return self::$objects[$path];
    }

    public function offsetExists($index)
    {
        if (isset(self::$handles[$index])) {
            $index = self::$handles[$index];
        }

        return parent::offsetExists($index);
    }

    public function offsetGet($index)
    {
        $path = $index;

        if (isset(self::$handles[$index])) {
            $path = self::$handles[$index];
        }

        if (isset(self::$objects[$path]) === false) {
            Profiler::begin('Loaded section %section');

            $section = Section::loadFromFile($path);
            self::$objects[$path] = $section;

            Profiler::store('section', basename($path, '.xml'), 'system/section');
            Profiler::store('location', $path, 'system/resource action/loaded');
            Profiler::end();
        }

        return self::$objects[$path];
    }

    public function offsetSet($index, $value)
    {
        if (isset(self::$handles[$index])) {
            $index = self::$handles[$index];
        }

        self::$objects[$index] = $value;

        return parent::offsetSet($index, true);
    }

    public function offsetUnset($index)
    {
        if (isset(self::$handles[$index])) {
            $index = self::$handles[$index];

            unset(self::$handles[$index]);
        }

        if (isset(self::$objects[$index])) {
            unset(self::$objects[$index]);
        }

        return parent::offsetUnset($index);
    }
}


class Section
{
    const ERROR_SECTION_NOT_FOUND = 0;
    const ERROR_FAILED_TO_LOAD = 1;
    const ERROR_DOES_NOT_ACCEPT_PARAMETERS = 2;
    const ERROR_TOO_MANY_PARAMETERS = 3;

    const ERROR_MISSING_OR_INVALID_FIELDS = 4;
    const ERROR_FAILED_TO_WRITE = 5;

    protected static $sections = array();

    protected $parameters;
    public $fields;
    public $layout;

    public static function createGUID()
    {
        return uniqid();
    }

    public function __construct()
    {
        $this->parameters = new StdClass;
        $this->name = null;
        $this->fields = [];
        $this->layout = [];
        $this->{'navigation-group'} = null;
        $this->{'publish-order-handle'} = null;
        $this->{'publish-order-direction'} = null;
        $this->{'hidden-from-publish-menu'} = null;
    }

    public function __isset($name)
    {
        return isset($this->parameters->$name);
    }

    public function __get($name)
    {
        if ($name == 'guid' and !isset($this->guid)) {
            $this->parameters->guid = Section::createGUID();
        }

        return $this->parameters->$name;
    }

    public function __set($name, $value)
    {
        if ($name == 'name') {
            $this->parameters->handle = Lang::createHandle($value, '-', false, true, array('/^[^:_a-z]+/i' => null, '/[^:_a-z0-9\.-]/i' => null));
        }

        $this->parameters->$name = $value;
    }

    public function appendField(Field $field)
    {
        $field->section = $this->handle;
        $this->fields[] = $field;
    }

    public function appendFieldByType($type, array $data = null)
    {
        $field = Field::loadFromType($type);

        if (!is_null($data)) {
            $field->setPropertiesFromPostData($data);
        }

        $this->appendField($field);

        return $field;
    }

    public function removeAllFields()
    {
        $this->fields = array();
    }

    public function removeField($name)
    {
        foreach ($this->fields as $index => $f) {
            if ($f->{'publish-label'} == $name || $f->{'element-name'} == $name) {
                unset($this->fields[$index]);
            }
        }
    }

    /**
     * Given an field's element name, return an object of
     * that Field.
     *
     * @param $handle string
     * @return Field
     */
    public function fetchFieldByHandle($handle)
    {
        foreach ($this->fields as $field) {
            if ($field->{'element-name'} == $handle) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Given an field's element name, return an object of
     * that Field.
     *
     * @param $handle string
     * @return Field
     */
    public function fetchFieldsByType($type)
    {
        $fields = array();

        foreach ($this->fields as $field) {
            if ($field->{'type'} == $type) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public static function fetchUsedNavigationGroups()
    {
        $groups = array();
        foreach (new SectionIterator as $s) {
            $groups[] = $s->{'navigation-group'};
        }
        return General::array_remove_duplicates($groups);
    }

    public static function load($path)
    {
        $sections = new SectionIterator();

        // Try with given path:
        if (isset($sections[$path])) {
            $section = $sections[$path];
        }

        // Try with realpath:
        else {
            $real = realpath($path);

            if ($real !== false && isset($sections[$real]) !== false) {
                $section = $sections[$real];
            }
        }

        // Not found:
        if (isset($section) === false) {
            throw new ExtensionException('No sections found for ' . $path);
        }

        return $section;
    }

    public static function loadFromHandle($name)
    {
        $sections = new SectionIterator();

        if (isset($sections[$name]) === false) {
            throw new ExtensionException('No sections found for ' . $name);
        }

        return $sections[$name];
    }

    public static function loadFromFile($path)
    {
        $section = new Section();
        $section->handle = preg_replace('/\.xml$/', null, basename($path));
        $section->path = dirname($path);

        $doc = @simplexml_load_file($path);
        $section->document = $doc;

        if (($doc instanceof SimpleXMLElement) === false) {
            throw new SectionException(
                __('Failed to load section configuration file: %s', array($path)),
                Section::ERROR_FAILED_TO_LOAD
            );
        }

        foreach ($doc as $name => $value) {
            if ($name == 'fields' && isset($value->field)) {
                foreach ($value->field as $field) {
                    Profiler::begin('Loading %class field %field');

                    try {
                        $field = Field::loadFromXMLDefinition($field);
                        $section->appendField($field);
                    } catch (Exception $e) {
                        // Couldnt find the field. Ignore it for now
                        // TODO: Might need to more than just ignore it
                    }

                    Profiler::store('field', $field->{'element-name'}, 'system/field');
                    Profiler::store('class', get_class($field), 'system/class');
                    Profiler::store('location', $field->file, 'system/resource action/loaded');
                    Profiler::end();
                }
            } elseif ($name == 'layout' && isset($value->column)) {
                $data = array();

                foreach ($value->column as $column) {
                    if (isset($column->size) === false) {
                        $size = Layout::LARGE;
                    } else {
                        $size = (string)$column->size;
                    }

                    $data_column = (object)array(
                        'size' =>        $size,
                        'fieldsets' =>    array()
                    );

                    foreach ($column->fieldset as $fieldset) {
                        if (isset($fieldset->name) or trim((string)$fieldset->name) == '') {
                            $name = (string)$fieldset->name;
                        }

                        $data_fieldset = (object)array(
                            'name' =>        $name,
                            'collapsed' =>    (
                                                isset($fieldset->collapsed)
                                                    ? (string)$fieldset->collapsed
                                                    : 'no'
                                            ),
                            'fields' =>        array()
                        );

                        foreach ($fieldset->field as $field) {
                            $data_fieldset->fields[] = (string)$field;
                        }

                        $data_column->fieldsets[] = $data_fieldset;
                    }

                    $data[] = $data_column;
                }

                $section->layout = $data;
                $section->sanitizeLayout(true);
            } elseif (isset($value->item)) {
                $stack = array();

                foreach ($value->item as $item) {
                    array_push($stack, (string)$item);
                }

                $section->$name = $stack;
            } else {
                $section->$name = (string)$value;
            }
        }

        if (isset($doc->attributes()->guid)) {
            $section->guid = (string)$doc->attributes()->guid;
        }

        return $section;
    }

    public static function save(Section $section, MessageStack $messages, $essentials = null, $simulate = false)
    {
        $pathname = sprintf('%s/%s.xml', $section->path, $section->handle);

        // Check to ensure all the required section fields are filled
        if (!isset($section->name) || strlen(trim($section->name)) == 0) {
            $messages->append('name', __('This is a required field.'));
        }

        // Check for duplicate section handle
        elseif (file_exists($pathname)) {
            $existing = self::load($pathname);

            if (isset($existing->guid) and $existing->guid != $section->guid) {
                $messages->append('name', __('A Section with the name <code>%s</code> already exists', array($section->name)));
            }

            unset($existing);
        }

        ## Check to ensure all the required section fields are filled
        if (!isset($section->{'navigation-group'}) || strlen(trim($section->{'navigation-group'})) == 0) {
            $messages->append('navigation-group', __('This is a required field.'));
        }

        if (is_array($section->fields) && !empty($section->fields)) {
            foreach ($section->fields as $index => $field) {
                $field_stack = new MessageStack;

                if ($field->validateSettings($field_stack, false, false) != Field::STATUS_OK) {
                    $messages->append("field::{$index}", $field_stack);
                }
            }
        }

        if ($messages->length() > 0) {
            throw new SectionException(__('Section could not be saved. Validation failed.'), self::ERROR_MISSING_OR_INVALID_FIELDS);
        }

        if ($simulate) {
            return true;
        }

        $section->sanitizeLayout();

        return file_put_contents($pathname, (string)$section);
    }

    public static function syncroniseStatistics(Section $section)
    {
        $new_doc = new DOMDocument('1.0', 'UTF-8');
        $new_doc->formatOutput = true;
        $new_doc->loadXML((string)$section);
        $new_xpath = new DOMXPath($new_doc);
        $new_handle = $section->handle;

        $old = $new = array();
        $result = (object)array(
            'synced'    => true,
            'section'    => (object)array(
                'create'    => false,
                'rename'    => false,
                'old'        => (object)array(
                    'handle'    => $new_handle,
                    'name'        => $section->name
                ),
                'new'        => (object)array(
                    'handle'    => $new_handle,
                    'name'        => $section->name
                )
            ),
            'remove'    => array(),
            'rename'    => array(),
            'create'    => array(),
            'update'    => array()
        );

        $res = Symphony::Database()->query(
            '
				SELECT
					s.xml
				FROM
					`sections_sync` AS s
				WHERE
					s.section = "%s"
			',
            array(
                $section->guid
            )
        );

        // Found sync data:
        if ($res->valid()) {
            $old_doc = new DOMDocument('1.0', 'UTF-8');
            $old_doc->formatOutput = true;
            $old_doc->loadXML($res->current()->xml);
            $old_xpath = new DOMXPath($old_doc);
            $old_handle = $old_xpath->evaluate('string(/section/name/@handle)');

            if ($old_handle != $new_handle) {
                $result->synced = false;
                $result->section->rename = true;
                $result->section->old->handle = $old_handle;
                $result->section->old->name = $old_xpath->evaluate('string(/section/name)');
            }

            // Build array of old and new nodes for comparison:
            foreach ($old_xpath->query('/section/fields/field') as $node) {
                $type = $old_xpath->evaluate('string(type)', $node);
                $field = Field::loadFromType($type);
                $field->loadSettingsFromSimpleXMLObject(
                    simplexml_import_dom($node)
                );

                $old[$field->guid] = (object)array(
                    'label'        => $field->{'publish-label'},
                    'field'        => $field
                );
            }
        }

        // Creating the section:
        else {
            $result->synced = false;
            $result->section->create = true;
        }

        foreach ($new_xpath->query('/section/fields/field') as $node) {
            $type = $new_xpath->evaluate('string(type)', $node);
            $field = Field::loadFromType($type);
            $field->loadSettingsFromSimpleXMLObject(
                simplexml_import_dom($node)
            );

            $new[$field->guid] = (object)array(
                'label'        => $field->{'publish-label'},
                'field'        => $field
            );
        }

        foreach ($new as $guid => $data) {
            // Field is being created:
            if (array_key_exists($guid, $old) === false) {
                $result->create[$guid] = $data;
                continue;
            }

            // Field is being renamed:
            if ($result->section->rename or $old[$guid]->field->{'element-name'} != $data->field->{'element-name'}) {
                if ($old[$guid]->field->type == $data->field->type) {
                    $result->rename[$guid] = (object)array(
                        'label'        => $data->{'label'},
                        'old'        => $old[$guid]->field,
                        'new'        => $data->field
                    );
                }

                // Type has changed:
                else {
                    $result->remove[$guid] = $old[$guid];
                    $result->create[$guid] = $data;
                    continue;
                }
            }

            // Field definition has changed:
            if ($old[$guid]->field != $data->field) {
                if ($old[$guid]->field->type == $data->field->type) {
                    $result->update[$guid] = (object)array(
                        'label'        => $data->{'label'},
                        'old'        => $old[$guid]->field,
                        'new'        => $data->field
                    );
                }

                // Type has changed:
                else {
                    $result->remove[$guid] = $old[$guid];
                    $result->create[$guid] = $data;
                    continue;
                }
            }
        }

        foreach ($old as $guid => $data) {
            if (array_key_exists($guid, $new)) {
                continue;
            }

            $result->remove[$guid] = $data;
        }

        $result->synced = (
            $result->synced
            and empty($result->remove)
            and empty($result->rename)
            and empty($result->create)
            and empty($result->update)
        );

        return $result;
    }

    public static function synchronise(Section $section)
    {
        $stats = self::syncroniseStatistics($section);
        $new_handle = $stats->section->new->handle;
        $old_handle = $stats->section->old->handle;

        // Remove fields:
        foreach ($stats->remove as $guid => $data) {
            $data->field->remove();
        }

        // Rename fields:
        foreach ($stats->rename as $guid => $data) {
            $data->new->rename($data->old);
        }

        // Create fields:
        foreach ($stats->create as $guid => $data) {
            $data->field->create();
        }

        // Update fields:
        foreach ($stats->update as $guid => $data) {
            $data->new->update($data->old);
        }

        // Remove old sync data:
        Symphony::Database()->delete(
            'sections_sync',
            array($section->guid),
            '`section` = "%s"'
        );

        // Create new sync data:
        Symphony::Database()->insert('sections_sync', array(
            'section'    => $section->guid,
            'xml'        => (string)$section
        ));
    }

    public static function rename(Section $section, $old_handle)
    {
        /*
            TODO:
            Upon renaming a section, data-sources/events attached to it must update.
            Views will also need to update to ensure they still have references to the same
            data-sources/sections
        */

        return General::deleteFile($section->path . '/' . $old_handle . '.xml');
    }

    public static function delete(Section $section)
    {
        /*
            TODO:
            Upon deletion it should update all data-sources/events attached to it.
            Either by deleting them, or making section $unknown.

            I think deletion is best because if the section is renamed, the rename()
            function will take care of moving the dependancies, so there should be
            no data-sources/events to delete anyway.

            However, if you delete page accidentally (hm, even though you clicked
            confirm), do you really want your data-sources/events to just be deleted?

            Verdict?
        */

        // 	Remove fields:
        foreach ($section->fields as $field) {
            $field->remove();
        }

        // 	Remove sync data:
        Symphony::Database()->delete(
            'sections_sync',
            array($section->guid),
            '`section` = "%s"'
        );

        //	Remove entry metadata
        Symphony::Database()->delete(
            'entries',
            array($section->handle),
            '`section` = "%s"'
        );

        if (General::deleteFile($section->path . '/' . $section->handle . '.xml')) {
            //	Cleanup Datasources
            foreach (new DataSourceIterator as $datasource) {
                $ds = DataSource::load($datasource);

                if ($ds->parameters()->section == $section->handle) {
                    DataSource::delete($ds);
                }
            }

            //	Cleanup Events
            foreach (new EventIterator as $event) {
                $ev = Event::load($event);

                if ($ev->parameters()->source == $section->handle) {
                    Event::delete($ev);
                }
            }
        }
    }

    public function sanitizeLayout($loading = false)
    {
        $layout = $this->layout;
        $fields_used = array();
        $fields_available = array();

        // Find available fields:
        foreach ($this->fields as $field) {
            $fields_available[] = $field->{'element-name'};
        }

        // Make sure we have at least one column:
        if (!is_array($layout) or empty($layout)) {
            $layout = array(
                (object)array(
                    'size'        => Layout::LARGE,
                    'fieldsets'    => array()
                ),
                (object)array(
                    'size'        => Layout::SMALL,
                    'fieldsets'    => array()
                )
            );
        }

        // Make sure each column has a fieldset:
        foreach ($layout as &$column) {
            if (!isset($column->fieldsets) or !is_array($column->fieldsets)) {
                $column->fieldsets = array();
            }

            if (empty($column->fieldsets)) {
                $column->fieldsets = array(
                    (object)array(
                        'name'        => null,
                        'fields'    => array()
                    )
                );
            }

            foreach ($column->fieldsets as &$fieldset) {
                if (!isset($fieldset->fields) or !is_array($fieldset->fields)) {
                    $fieldset->fields = array();
                }

                if (empty($fieldset->fields)) {
                    $fieldset->fields = array();
                }

                foreach ($fieldset->fields as $index => $field) {
                    if (in_array($field, $fields_available)) {
                        $fields_used[] = $field;
                    } else {
                        unset($fieldset->fields[$index]);
                    }

                    $fields_used[] = $field;
                }
            }
        }

        $fields_unused = array_diff($fields_available, $fields_used);

        if (is_array($fields_unused)) {
            foreach ($fields_unused as $field) {
                $layout[0]->fieldsets[0]->fields[] = $field;
            }
        }

        $this->layout = $layout;
    }

    public function toDoc()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('section');
        $doc->appendChild($root);

        if (!isset($this->guid) || is_null($this->guid)) {
            $this->guid = uniqid();
        }

        $root->setAttribute('guid', $this->guid);

        $name = $doc->createElement('name', General::sanitize($this->name));
        $name->setAttribute('handle', $this->handle);

        $root->appendChild($name);
        $root->appendChild($doc->createElement('hidden-from-publish-menu', (
            isset($this->{'hidden-from-publish-menu'})
            && strtolower(trim($this->{'hidden-from-publish-menu'})) == 'yes'
                ? 'yes'
                : 'no'
        )));
        $root->appendChild($doc->createElement('navigation-group', General::sanitize($this->{'navigation-group'})));

        $root->appendChild($doc->createElement('publish-order-handle', General::sanitize($this->{'publish-order-handle'})));
        $root->appendChild($doc->createElement('publish-order-direction', General::sanitize($this->{'publish-order-direction'})));

        if (is_array($this->fields) && !empty($this->fields)) {
            $fields = $doc->createElement('fields');

            foreach ($this->fields as $index => $field) {
                $fields->appendChild($doc->importNode(
                    $field->toDoc()->documentElement, true
                ));
            }

            $root->appendChild($fields);
        }

        if (is_array($this->layout)) {
            $layout = $doc->createElement('layout');

            foreach ($this->layout as $data) {
                $column = $doc->createElement('column');

                if (!isset($data->size) or $data->size != Layout::LARGE) {
                    $data->size = Layout::SMALL;
                }

                $size = $doc->createElement('size', $data->size);
                $column->appendChild($size);

                if (is_array($data->fieldsets)) {
                    foreach ($data->fieldsets as $data) {
                        $fieldset = $doc->createElement('fieldset');

                        if (!isset($data->name) or trim($data->name) == '') {
                            $data->name = null;
                        }

                        $name = $doc->createElement('name', $data->name);
                        $fieldset->appendChild($name);

                        if (is_array($data->fields)) {
                            foreach ($data->fields as $data) {
                                if (!is_string($data) or trim($data) == '') {
                                    continue;
                                }

                                $fieldset->appendChild($doc->createElement('field', $data));
                            }
                        }

                        $column->appendChild($fieldset);
                    }
                }

                $layout->appendChild($column);
            }

            $root->appendChild($layout);
        }

        return $doc;
    }

    public function __toString()
    {
        try {
            return $this->toDoc()->saveXML();
        } catch (Exception $e) {
            var_dump($e);
            die();
        }
    }
}
