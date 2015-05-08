<?php

use StdClass;
use ReflectionObject;
use TextFormatter;
use TextFormatterIterator;
use Lang;
use Symphony;
use Exception;
use FieldException;
use FieldIterator;
use SimpleXMLElement;
use XMLDocument;
use MessageStack;
use SymphonyDOMElement;
use Widget;
use DOMElement;
use Entry;
use DatabaseException;
use DataSource;

use Embark\CMS\Datasource\Exception as DatabaseException;
use Embark\CMS\Database\Connection;
use Embark\CMS\Structures\ParameterPool;

abstract class Field
{
    protected static $key;
    protected static $loaded;

    protected $properties;

    protected $_handle;
    protected $_name;
    public $file;

    // Status codes
    const STATUS_OK = 'ok';
    const STATUS_ERROR = 'error';

    // Error codes
    const ERROR_MISSING = 'missing';
    const ERROR_INVALID = 'invalid';
    const ERROR_DUPLICATE = 'duplicate';
    const ERROR_CUSTOM = 'custom';
    const ERROR_INVALID_QNAME = 'invalid qname';
    const ERROR_WEAK = 'weak';
    const ERROR_SHORT = 'short';

    // Filtering Flags
    const FLAG_TOGGLEABLE = 'toggeable';
    const FLAG_UNTOGGLEABLE = 'untoggleable';
    const FLAG_FILTERABLE = 'filterable';
    const FLAG_UNFILTERABLE = 'unfilterable';
    const FLAG_ALL = 'all';

    public function __construct()
    {
        if (is_null(self::$key)) {
            self::$key = 0;
        }

        $reflection = new ReflectionObject($this);
        $this->file = $reflection->getFileName();

        $this->properties = new StdClass;

        $this->{'required'} = 'no';
        $this->{'show-column'} = 'yes';

        $this->_handle = (strtolower(get_class($this)) == 'field' ? 'field' : strtolower(substr(get_class($this), 5)));
    }

    public function __isset($name)
    {
        return isset($this->properties->$name);
    }

    public function __get($name)
    {
        if ($name == 'guid' and !isset($this->guid)) {
            $this->guid = Field::createGUID($this);
        }

        if ($name == "publish-label") {
            $this->{'publish-label'} = $this->properties->name;
        }

        if (!isset($this->properties->$name)) {
            return null;
        }

        return $this->properties->$name;
    }

    public function __set($name, $value)
    {
        if ($name == 'name') {
            $this->properties->{'element-name'} = Lang::createHandle($value, '-', false, true, array('/^[^:_a-z]+/i' => null, '/[^:_a-z0-9\.-]/i' => null));
        }

        $this->properties->$name = $value;
    }

    public function __clone()
    {
        $this->properties = new StdClass;
    }

    public function handle()
    {
        return $this->_handle;
    }

    public function name()
    {
        return ($this->_name ? $this->_name : $this->_handle);
    }

    public function allowDatasourceOutputGrouping()
    {
        return false;
    }

    public function allowDatasourceParamOutput()
    {
        return false;
    }

    public function mustBeUnique()
    {
        return false;
    }

    public function canFilter()
    {
        return false;
    }

    public function canImport()
    {
        return false;
    }

    public function canPrePopulate()
    {
        return false;
    }

    public function isSortable()
    {
        return false;
    }

    public function requiresSQLGrouping()
    {
        return false;
    }

    public function canToggleData()
    {
        return false;
    }

    public function getToggleStates()
    {
        return array();
    }

    public function update(Field $old)
    {
        return true;
    }

    public function fetchDataKey()
    {
        return 'entry_id';
    }

/*-------------------------------------------------------------------------
    Database Statements:
-------------------------------------------------------------------------*/

    public function create()
    {
        return Symphony::Database()->query(
            '
                CREATE TABLE IF NOT EXISTS `data_%s_%s` (
                    `id` int(11) unsigned NOT NULL auto_increment,
                    `entry_id` int(11) unsigned NOT NULL,
                    `handle` varchar(255) default NULL,
                    `value` varchar(255) default NULL,
                    PRIMARY KEY  (`id`),
                    KEY `entry_id` (`entry_id`),
                    KEY `value` (`value`)
                )
            ',
            array($this->section, $this->{'element-name'})
        );
    }

    public function remove()
    {
        try {
            Symphony::Database()->query(
                '
                    DROP TABLE
                        `data_%s_%s`
                ',
                array($this->section, $this->{'element-name'})
            );
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function rename(Field $old)
    {
        try {
            Symphony::Database()->query(
                '
                    ALTER TABLE
                        `data_%s_%s`
                    RENAME TO
                        `data_%s_%s`
                ',
                array(
                    $old->section,
                    $old->{'element-name'},
                    $this->section,
                    $this->{'element-name'}
                )
            );
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

/*-------------------------------------------------------------------------
    Load:
-------------------------------------------------------------------------*/

    public static function load($pathname)
    {
        if (is_array(self::$loaded) === false) {
            self::$loaded = array();
        }

        if (is_file($pathname) === false) {
            throw new FieldException(
                __('Could not find Field <code>%s</code>. If the Field was provided by an Extension, ensure that it is installed, and enabled.', array(basename($pathname)))
            );
        }

        if (isset(self::$loaded[$pathname]) === false) {
            self::$loaded[$pathname] = require($pathname);
        }

        $obj = new self::$loaded[$pathname];
        $obj->type = preg_replace('%^field\.|\.php$%', '', basename($pathname));

        return $obj;
    }

    public static function loadFromType($type)
    {
        $fields = new FieldIterator();

        return clone $fields[$type];
    }

    public static function loadFromXMLDefinition(SimpleXMLElement $xml)
    {
        if (isset($xml->type) === false) {
            throw new FieldException('Section XML contains fields with no type specified.');
        }

        $field = self::loadFromType((string)$xml->type);
        $field->loadSettingsFromSimpleXMLObject($xml);

        return $field;
    }

/*-------------------------------------------------------------------------
    Utilities:
-------------------------------------------------------------------------*/

    public static function createGUID(Field $field)
    {
        return uniqid();
    }

    public function cleanValue($value)
    {
        return html_entity_decode(Symphony::Database()->escape($value));
    }

    public function __toString()
    {
        $doc = $this->toDoc();

        return $doc->saveXML($doc->documentElement);
    }

    public function toDoc()
    {
        $doc = new XMLDocument;

        $root = $doc->createElement('field');
        $root->setAttribute('guid', $this->guid);

        foreach ($this->properties as $name => $value) {
            if ($name == 'guid') {
                continue;
            }

            $element = $doc->createElement($name);
            $element->setValue($value);

            $root->appendChild($element);
        }

        $doc->appendChild($root);
        return $doc;
    }

/*-------------------------------------------------------------------------
    Settings:
-------------------------------------------------------------------------*/

    public function loadSettingsFromSimpleXMLObject(SimpleXMLElement $xml)
    {
        foreach ($xml as $property_name => $property_value) {
            $data[(string)$property_name] = (string)$property_value;
        }

        // Set field GUID:
        if (isset($xml->attributes()->guid) and trim((string)$xml->attributes()->guid) != '') {
            $data['guid'] = (string)$xml->attributes()->guid;
        }

        $this->setPropertiesFromPostData($data);
    }

    // TODO: Rethink this function
    public function findDefaultSettings(array &$fields)
    {
    }

    public function validateSettings(MessageStack $messages, $checkForDuplicates = true)
    {
        $parent_section = $this->{'parent-section'};

        if (!isset($this->name) || strlen(trim($this->name)) == 0) {
            $messages->append('name', __('This is a required field.'));
        }

        if (!isset($this->{'element-name'}) || strlen(trim($this->{'element-name'})) == 0) {
            $messages->append('element-name', __('This is a required field.'));
        } elseif (!preg_match('/^[A-z]([\w\d-_\.]+)?$/i', $this->{'element-name'})) {
            $messages->append('element-name', __('Invalid element name. Must be valid QName.'));
        }

        /*
        TODO: Replace this with something:
        else if($checkForDuplicates) {
            $sql_id = ($this->id ? " AND f.id != '".$this->id."' " : '');

            $query = sprintf("
                    SELECT
                        f.*
                    FROM
                        `fields` AS f
                    WHERE
                        f.element_name = '%s'
                        %s
                        AND f.parent_section = '%s'
                    LIMIT
                        1
                ",
                $element_name,
                $sql_id,
                $parent_section
            );

            if (Symphony::Database()->query($query)->valid()) {
                $messages->append("field::{$index}::element-name", __('A field with that element name already exists. Please choose another.'));
            }
        }
        */

        if ($messages->length() > 0) {
            return Field::STATUS_ERROR;
        }

        return Field::STATUS_OK;
    }

    public function displaySettingsPanel(SymphonyDOMElement $wrapper, MessageStack $messages)
    {
        $document = $wrapper->ownerDocument;

        $group = $document->createElement('div');
        $group->setAttribute('class', 'group');

        $label = Widget::Label(__('Name'));
        $label->setAttribute('class', 'field-name');
        $label->appendChild(Widget::Input('name', $this->name));

        if ($messages->{'name'}) {
            $label = Widget::wrapFormElementWithError($label, $messages->{'name'});
        }

        $group->appendChild($label);


        $label = Widget::Label(__('Publish Label'));
        $label->appendChild($document->createElement('em', 'Optional'));
        $label->appendChild(Widget::Input('publish-label', $this->{'publish-label'}));

        if ($messages->{'publish-label'}) {
            $label = Widget::wrapFormElementWithError($label, $messages->{'publish-label'});
        }

        $group->appendChild($label);

        $wrapper->appendChild($group);


        if (isset($this->guid)) {
            $wrapper->appendChild(Widget::Input('guid', $this->guid, 'hidden'));
        }

        $wrapper->appendChild(Widget::Input('type', $this->type, 'hidden'));
    }

    public function setPropertiesFromPostData($data)
    {
        $data['required'] = (isset($data['required']) && $data['required'] == 'yes' ? 'yes' : 'no');
        $data['show-column'] = (isset($data['show-column']) && $data['show-column'] == 'yes' ? 'yes' : 'no');
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }


    public function appendRequiredCheckbox(SymphonyDOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;
        $item = $document->createElement('li');
        $item->appendChild(Widget::Input('required', 'no', 'hidden'));

        $label = Widget::Label(__('Make this a required field'));
        $input = Widget::Input('required', 'yes', 'checkbox');

        if ($this->required == 'yes') {
            $input->setAttribute('checked', 'checked');
        }

        $label->prependChild($input);
        $item->appendChild($label);
        $wrapper->appendChild($item);
    }

    public function appendShowColumnCheckbox(SymphonyDOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;
        $item = $document->createElement('li');
        $item->appendChild(Widget::Input('show-column', 'no', 'hidden'));

        $label = Widget::Label(__('Show column'));
        $label->setAttribute('class', 'meta');
        $input = Widget::Input('show-column', 'yes', 'checkbox');

        if ($this->{'show-column'} == 'yes') {
            $input->setAttribute('checked', 'checked');
        }

        $label->prependChild($input);
        $item->appendChild($label);
        $wrapper->appendChild($item);
    }

    public function appendFormatterSelect(SymphonyDOMElement $wrapper, $selected = null, $name = 'fields[format]', $label_value = null)
    {
        require_once(LIB . '/class.textformatter.php');

        if (!$label_value) {
            $label_value = __('Text Formatter');
        }

        $label = Widget::Label($label_value);
        $document = $wrapper->ownerDocument;
        $options = array();

        $options[] = array(null, false, __('None'));

        $iterator = new TextFormatterIterator;
        if ($iterator->length() > 0) {
            foreach ($iterator as $pathname) {
                $handle = TextFormatter::getHandleFromFilename(basename($pathname));
                $tf = TextFormatter::load($pathname);

                $options[] = array($handle, ($selected == $handle), constant(sprintf('%s::NAME', get_class($tf))));
            }
        }

        $label->appendChild(Widget::Select($name, $options));
        $wrapper->appendChild($label);
    }

    public function appendValidationSelect(SymphonyDOMElement $wrapper, $selected = null, $name = 'fields[validator]', $label_value = null, $type = 'input')
    {
        include(LIB . '/include.validators.php');

        if (is_null($label_value)) {
            $label_value = __('Validation Rule');
        }

        $label = Widget::Label($label_value);
        $document = $wrapper->ownerDocument;
        $rules = ($type == 'upload' ? $upload : $validators);

        $label->appendChild($document->createElement('em', __('Optional')));
        $label->appendChild(Widget::Input($name, $selected));
        $wrapper->appendChild($label);

        $ul = $document->createElement('ul', null, array('class' => 'tags singular'));

        foreach ($rules as $name => $rule) {
            $ul->appendChild(
                $document->createElement('li', $name, array('class' => $rule))
            );
        }

        $wrapper->appendChild($ul);
    }

/*-------------------------------------------------------------------------
    Publish:
-------------------------------------------------------------------------*/

    public function prepareTableValue(StdClass $data = null, DOMElement $link = null, Entry $entry = null)
    {
        $max_length = ($max_length ? $max_length : 75);

        $value = (!is_null($data) ? strip_tags($data->value) : null);

        if ($max_length < strlen($value)) {
            $lines = explode("\n", wordwrap($value, $max_length - 1, "\n"));
            $value = array_shift($lines);
            $value = rtrim($value, "\n\t !?.,:;");
            $value .= '…';
        }

        if ($max_length > 75) {
            $value = wordwrap($value, 75, '<br />');
        }

        if (strlen($value) == 0) {
            $value = __('None');
        }

        if (!is_null($link)) {
            $link->setValue($value);

            return $link;
        }

        return $value;
    }

    abstract public function displayPublishPanel(SymphonyDOMElement $wrapper, MessageStack $error, Entry $entry = null, $data = null);

/*-------------------------------------------------------------------------
    Input:
-------------------------------------------------------------------------*/

    public function loadDataFromDatabase(Entry $entry, $expect_multiple = false)
    {
        try {
            $rows = Symphony::Database()->query(
                "SELECT * FROM `data_%s_%s` WHERE `entry_id` = %s ORDER BY `id` ASC",
                array(
                    $entry->section,
                    $this->{'element-name'},
                    $entry->id
                )
            );

            if (!$expect_multiple) {
                return $rows->current();
            }

            $result = array();
            foreach ($rows as $r) {
                $result[] = $r;
            }

            return $result;
        } catch (DatabaseException $e) {
            // Oh oh....no data. oh well, have a smoke and then return
        }
    }

    public function loadDataFromDatabaseEntries($section, $entry_ids)
    {
        try {
            $rows = Symphony::Database()->query(
                "SELECT * FROM `data_%s_%s` WHERE `entry_id` IN (%s) ORDER BY `id` ASC",
                array(
                    $section,
                    $this->{'element-name'},
                    implode(',', $entry_ids)
                )
            );

            $result = array();
            foreach ($rows as $r) {
                $result[] = $r;
            }

            return $result;
        } catch (DatabaseException $e) {
            return array();
            // Oh oh....no data. oh well, have a smoke and then return
        }
    }

    /**
     * Can the field process this raw data?
     *
     * Used to determine if field data should be overwritten with the
     * result of processData when saving entries.
     *
     * @param    mixed    $data
     * @param    Entry    $entry
     *
     * @return    boolean
     */
    public function canProcessData($data, Entry $entry = null)
    {
        return $data !== null;
    }

    public function processData($data, Entry $entry = null)
    {
        if (isset($entry->data()->{$this->{'element-name'}})) {
            $result = $entry->data()->{$this->{'element-name'}};
        } else {
            $result = (object)array(
                'value' => null
            );
        }

        $result->value = $data;

        return $result;
    }

    public function validateData(MessageStack $errors, Entry $entry = null, $data = null)
    {
        if ($this->required == 'yes' && (!isset($data->value) || strlen(trim($data->value)) == 0)) {
            $errors->append(
                null,
                (object)array(
                    'message' => __("'%s' is a required field.", array($this->{'publish-label'})),
                    'code' => self::ERROR_MISSING
                )
            );
            return self::STATUS_ERROR;
        }
        return self::STATUS_OK;
    }

    // TODO: Support an array of data objects. This is important for
    // fields like Select box or anything that allows mutliple values
    public function saveData(MessageStack $errors, Entry $entry, $data = null)
    {
        if (is_array($data)) {
            $data = (object)$data;
        }
        if (isset($data->id) === false) {
            $data->id = null;
        }

        $data->entry_id = $entry->id;

        try {
            Symphony::Database()->insert(
                sprintf('data_%s_%s', $entry->section, $this->{'element-name'}),
                (array)$data,
                Connection::UPDATE_ON_DUPLICATE
            );
            return self::STATUS_OK;
        } catch (DatabaseException $e) {
            //    The great irony here is the the getMessage returns something a hell of a lot
            //    more useful than the getDatabaseErrorMessage. ie.
            //    getMessage: MySQL Error (1048): Column 'value' cannot be null in query {$query}
            //    getDatabaseErrorMessage: Column 'value' cannot be null
            $errors->append(
                null,
                (object)array(
                    'message' => $e->getMessage(),
                    'code' => $e->getDatabaseErrorCode()
                )
            );
        } catch (Exception $e) {
            $errors->append(
                null,
                (object)array(
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                )
            );
        }
        return self::STATUS_ERROR;
    }

/*-------------------------------------------------------------------------
    Output:
-------------------------------------------------------------------------*/

    public function appendFormattedElement(DOMElement $wrapper, $data, $encode = false, $mode = null, Entry $entry = null)
    {
        if (is_null($data->value)) {
            return;
        }

        $wrapper->appendChild(
            $wrapper->ownerDocument->createElement(
                $this->{'element-name'},
                ($encode ? General::sanitize($this->prepareTableValue($data)) : $this->prepareTableValue($data))
            )
        );
    }

    public function getParameterOutputValue($data, Entry $entry = null)
    {
        if (is_null($data->value)) {
            return;
        }

        return $this->prepareTableValue($data);
    }

/*-------------------------------------------------------------------------
    Filtering:
-------------------------------------------------------------------------*/

    public function getFilterTypes($data)
    {
        return array(
            array('is', false, 'Is'),
            array('is-not', $data->type == 'is-not', 'Is not'),
            array('contains', $data->type == 'contains', 'Contains'),
            array('does-not-contain', $data->type == 'does-not-contain', 'Does not Contain'),
            array('regex-search', $data->type == 'regex-search', 'Regex Search')
        );
    }

    public function processFilter($data)
    {
        $defaults = (object)array(
            'allow-null' =>        false,
            'type' =>            'is',
            'value' =>            ''
        );

        if (empty($data)) {
            $data = $defaults;
        }

        $data = (object)$data;

        if (isset($data->{'allow-null'}) === false) {
            $data->{'allow-null'} = $defaults->{'allow-null'};
        }

        if (isset($data->type) === false) {
            $data->type = $defaults->type;
        }

        if (isset($data->value) === false) {
            $data->value = '';
        }

        return $data;
    }

    public function displayDatasourceFilterPanel(SymphonyDOMElement $wrapper, $data = null, MessageStack $errors = null)
    {
        $data = $this->processFilter($data);
        $document = $wrapper->ownerDocument;

        $type_label = Widget::Label(__('Type'));
        $type_label->setAttribute('class', 'small');
        $type_label->appendChild(Widget::Select(
            sprintf('type', $this->{'element-name'}),
            $this->getFilterTypes($data)
        ));
        $wrapper->appendChild($type_label);

        $label = Widget::Label(__('Value'));
        $label->appendChild(Widget::Input(
            sprintf('value', $this->{'element-name'}),
            $data->value
        ));

        $label->appendChild(Widget::Input(
            'element-name',
            $this->{'element-name'},
            'hidden'
        ));

        $wrapper->appendChild(Widget::Group(
            $type_label,
            $label
        ));
    }

    public function buildJoinQuery(&$joins)
    {
        $db = Symphony::Database();

        $table = $db->prepareQuery(sprintf(
            '`data_%s_%s`',
            $this->section,
            $this->{'element-name'},
            ++self::$key
        ));
        $handle = sprintf(
            '`data_%s_%s_%d`',
            $this->section,
            $this->{'element-name'},
            self::$key
        );
        $joins .= sprintf(
            "\nRIGHT JOIN %s AS %s ON (e.id = %2\$s.entry_id)",
            $table,
            $handle
        );

        return $handle;
    }

    public function buildFilterJoin(&$joins)
    {
        return $this->buildJoinQuery($joins);
    }

    public function buildFilterQuery($filter, &$joins, array &$where, ParameterPool $parameter_output)
    {
        $filter = $this->processFilter($filter);
        $filter_join = DataSource::FILTER_OR;
        $db = Symphony::Database();

        $values = DataSource::prepareFilterValue($filter->value, $parameter_output, $filter_join);

        if (is_array($values) === false) {
            $values = array();
        }

        // Exact matches:
        if ($filter->type == 'is' or $filter->type == 'is-not') {
            $statements = array();

            if ($filter_join == DataSource::FILTER_OR) {
                $handle = $this->buildFilterJoin($joins);
            }

            foreach ($values as $index => $value) {
                if ($filter_join != DataSource::FILTER_OR) {
                    $handle = $this->buildFilterJoin($joins);
                }

                $statements[] = $db->prepareQuery(
                    "'%s' IN ({$handle}.value)",
                    array($value)
                );

                $statements[] = $db->prepareQuery(
                    "'%s' IN ({$handle}.handle)",
                    array(lang::createHandle($value))
                );
            }

            if (empty($statements)) {
                return true;
            }

            if ($filter_join == DataSource::FILTER_OR) {
                $statement = "(\n\t" . implode("\n\tOR ", $statements) . "\n)";
            } else {
                $statement = "(\n\t" . implode("\n\tAND ", $statements) . "\n)";
            }

            if ($filter->type == 'is-not') {
                $statement = 'NOT ' . $statement;
            }

            $where[] = $statement;
        } elseif ($filter->type == 'is-null') {
            $handle = $this->buildFilterJoin($joins);
            $where[] = $db->prepareQuery("{$handle}.value IS NULL");
        } elseif ($filter->type == 'is-not-null') {
            $handle = $this->buildFilterJoin($joins);
            $where[] = $db->prepareQuery("{$handle}.value IS NOT NULL");
        } elseif ($filter->type == 'contains' or $filter->type == 'does-not-contain') {
            $statements = array();

            if ($filter_join == DataSource::FILTER_OR) {
                $handle = $this->buildFilterJoin($joins);
            }

            foreach ($values as $index => $value) {
                $value = '%' . $value . '%';

                if ($filter_join != DataSource::FILTER_OR) {
                    $handle = $this->buildFilterJoin($joins);
                }

                $statements = array(
                    $db->prepareQuery("{$handle}.value LIKE '%s'", array($value)),
                    $db->prepareQuery("{$handle}.handle LIKE '%s'", array($value))
                );
            }

            if (empty($statements)) {
                return true;
            }

            if ($filter_join == DataSource::FILTER_OR) {
                $statement = "(\n\t" . implode("\n\tOR ", $statements) . "\n)";
            } else {
                $statement = "(\n\t" . implode("\n\tAND ", $statements) . "\n)";
            }

            if ($filter->type == 'does-not-contain') {
                $statement = 'NOT ' . $statement;
            }

            $where[] = $statement;
        }

        // Regex search:
        elseif ($filter->type == 'regex-search') {
            $handle = $this->buildFilterJoin($joins);
            $value = trim($filter->value);
            $statements = array(
                $db->prepareQuery("{$handle}.value REGEXP '%s'", array($value)),
                $db->prepareQuery("{$handle}.handle REGEXP '%s'", array($value))
            );

            $where[] = "(\n\t" . implode("\n\tOR ", $statements) . "\n)";
        }

        return true;
    }

    public function buildDSFilterSQL()
    {
        // TODO: Cleanup before release.
        throw new Exception('Field->buildDSFilterSQL() is obsolete, use buildFilterQuery instead.');
    }

/*-------------------------------------------------------------------------
    Grouping:
-------------------------------------------------------------------------*/

    public function groupRecords($records)
    {
        throw new FieldException(
            __('Data source output grouping is not supported by the <code>%s</code> field', array($this->handle))
        );
    }

/*-------------------------------------------------------------------------
    Sorting:
-------------------------------------------------------------------------*/

    public function buildSortingJoin(&$joins)
    {
        return $this->buildJoinQuery($joins);
    }

    public function buildSortingQuery(&$joins, &$order)
    {
        $handle = $this->buildSortingJoin($joins);
        $order = "{$handle}.value %1\$s";
    }

    public function buildSortingSQL()
    {
        // TODO: Cleanup before release.
        throw new Exception('Field->buildSortingSQL() is obsolete, use buildSortingQuery instead.');
    }
}
