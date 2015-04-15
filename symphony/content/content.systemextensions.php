<?php

require_once(LIB . '/class.administrationpage.php');

class contentSystemExtensions extends AdministrationPage
{
    protected $lists;

    protected static $status_translation = array(
        Extension::STATUS_ENABLED => 'Enabled',
        Extension::STATUS_DISABLED => 'Disabled',
        Extension::STATUS_NOT_INSTALLED => 'Not Installed',
        Extension::STATUS_REQUIRES_UPDATE => 'Requires Update',
    );

    public function prepare()
    {
        ExtensionIterator::clearCachedFiles();
        SectionIterator::clearCachedFiles();

        parent::prepare();
    }

    public function view()
    {
        $this->lists = (object)array(
            'status' => array(
                Extension::STATUS_ENABLED => array(),
                Extension::STATUS_DISABLED => array(),
                Extension::STATUS_NOT_INSTALLED => array(),
                Extension::STATUS_REQUIRES_UPDATE => array(),

            ),
            'type' => array(),
            'extensions' => array()
        );

    ## Setup page

        $filter = ($this->_context[0] == 'type' || $this->_context[0] == 'status' ? $this->_context[0] : null);
        $value = (isset($this->_context[1]) ? $this->_context[1] : null);

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Extensions'))));
        $this->appendSubheading(__('Extensions'));

        $path = ADMIN_URL . '/system/extensions/';
        $this->Form->setAttribute('action', Administration::instance()->getCurrentPageURL());

    ## Define layout

        $layout = new Layout();
        $left = $layout->createColumn(Layout::SMALL);
        $left->setAttribute('class', 'column small filters');
        $right = $layout->createColumn(Layout::LARGE);

    ## Process extensions and build lists

        foreach (new ExtensionIterator as $extension) {
            $status = Extension::status($extension->handle);

            // List of extensions
            $this->lists->extensions[$extension->handle] = array(
                'object' =>        $extension,
                'handle' =>        $extension->handle,
                'path' =>        $path,
                'status' =>        $status
            );

            // List of extension handles grouped by status
            if (is_array($this->lists->status[$status]) === false) {
                $this->lists->status[$status] = array();
            }

            $this->lists->status[$status][] = $extension->handle;

            // List of extension handles grouped by type
            if (
                isset($extension->about()->type)
                && is_array($extension->about()->type)
                && empty($extension->about()->type) === false
            ) {
                foreach ($extension->about()->type as $t) {
                    if (isset($this->lists->type[$t]) === false) {
                        $this->lists->type[$t] = array();
                    }

                    $this->lists->type[$t][] = $extension->handle;
                }
            }
        }

    ## Build status filter menu

        $h4 = $this->createElement('h4', __('Filter by Status'));
        $left->appendChild($h4);

        $ul = $this->createElement('ul');

        ## Main status overview
        $li = $this->createElement('li', Widget::Anchor(__('Overview'), $path));
        if (is_null($filter)) {
            $li->setAttribute('class', 'active');
        }
        $ul->appendChild($li);

        foreach ($this->lists->status as $status => $extensions) {
            if (!empty($extensions)) {
                $li = $this->createElement('li', Widget::Anchor(self::$status_translation[$status], "{$path}status/{$status}"));
                if ($value == $status) {
                    $li->setAttribute('class', 'active');
                }

                $count = $this->createElement('span', (string)count($extensions));

                $li->appendChild($count);

                $ul->appendChild($li);
            }
        }

        $left->appendChild($ul);

    ## Build type filter menu

        $h4 = $this->createElement('h4', __('Filter by Type'));
        $left->appendChild($h4);

        $ul = $this->createElement('ul');

        foreach ($this->lists->type as $type => $extensions) {
            if (!empty($extensions)) {
                $li = $this->createElement('li', Widget::Anchor(ucwords($type), "{$path}type/{$type}"));
                if ($value == $type) {
                    $li->setAttribute('class', 'active');
                }

                $count = $this->createElement('span', (string)count($extensions));

                $li->appendChild($count);

                $ul->appendChild($li);
            }
        }

        $left->appendChild($ul);

    ## If a filter and value are specified...
        if (!is_null($filter) && !is_null($value)) {
            ## If there are extensions in the list, build the table
            if (isset($this->lists->{$filter}[$value])) {
                $right->appendChild($this->buildTable($this->lists->{$filter}[$value]));
            } else {
                ## Otherwise pass an empty array so we get the
                ## 'No Records Found' message
                $right->appendChild($this->buildTable());
            }

        ## and append table actions

            $tableActions = $this->createElement('div');
            $tableActions->setAttribute('class', 'actions');

            $options = array(
                array(null, false, __('With Selected...')),
                array('enable', false, __('Enable')),
                array('disable', false, __('Disable')),
                array('uninstall', false, __('Uninstall'), 'confirm'),
            );

            $tableActions->appendChild(Widget::Select('with-selected', $options));
            $tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

            $right->appendChild($tableActions);

    ## Otherwise, build the overview
        } else {
            ## Requires Update
            if (!empty($this->lists->status[Extension::STATUS_REQUIRES_UPDATE])) {
                $count = count($this->lists->status[Extension::STATUS_REQUIRES_UPDATE]);

                $div = $this->createElement('div');
                $div->setAttribute('class', 'tools');
                $h4 = $this->createElement('h4', __('Updates'));
                $message = __('%s %s %s updates available.', array(
                    $count,
                    ($count > 1 ? 'extensions' : 'extension'),
                    ($count > 1 ? 'have' : 'has')
                ));
                $p = $this->createElement('p', $message);
                $view = Widget::Anchor(__('View Details'), sprintf('%sstatus/%s/', $path, Extension::STATUS_REQUIRES_UPDATE));
                $view->setAttribute('class', 'button');

                $div->appendChild($view);
                $div->appendChild($h4);
                $div->appendChild($p);

                $right->appendChild($div);
            }

            ## Not Installed
            if (!empty($this->lists->status[Extension::STATUS_NOT_INSTALLED])) {
                $count = count($this->lists->status[Extension::STATUS_NOT_INSTALLED]);

                $div = $this->createElement('div');
                $div->setAttribute('class', 'tools');
                $h4 = $this->createElement('h4', __('Not Installed'));
                $message = __('%s %s %s not installed.', array(
                    $count,
                    ($count > 1 ? 'extensions' : 'extension'),
                    ($count > 1 ? 'are' : 'is')
                ));
                $p = $this->createElement('p', $message);
                $install = $this->createElement('button', __('Install All'));
                $install->setAttribute('class', 'create');
                $view = Widget::Anchor(__('View Details'), sprintf('%sstatus/%s/', $path, Extension::STATUS_NOT_INSTALLED));
                $view->setAttribute('class', 'button');

                $div->appendChild($install);
                $div->appendChild($view);
                $div->appendChild($h4);
                $div->appendChild($p);

                $right->appendChild($div);
            }

            ## Disabled
            if (!empty($this->lists->status[Extension::STATUS_DISABLED])) {
                $count = count($this->lists->status[Extension::STATUS_DISABLED]);

                $div = $this->createElement('div');
                $div->setAttribute('class', 'tools');
                $h4 = $this->createElement('h4', __('Disabled'));
                $message = __('%s %s %s disabled.', array(
                    $count,
                    ($count > 1 ? 'extensions' : 'extension'),
                    ($count > 1 ? 'are' : 'is')
                ));
                $p = $this->createElement('p', $message);
                $install = $this->createElement('button', __('Install All'));
                $install->setAttribute('class', 'create');
                $uninstall = $this->createElement('button', __('Uninstall All'));
                $uninstall->setAttribute('class', 'delete');
                $view = Widget::Anchor(__('View Details'), sprintf('%sstatus/%s/', $path, Extension::STATUS_DISABLED));
                $view->setAttribute('class', 'button');

                $div->appendChild($install);
                $div->appendChild($uninstall);
                $div->appendChild($view);
                $div->appendChild($h4);
                $div->appendChild($p);

                $right->appendChild($div);
            }

            ## Nothing to show
            if (
                empty($this->lists->status[Extension::STATUS_DISABLED])
                && empty($this->lists->status[Extension::STATUS_NOT_INSTALLED])
            ) {
                $div = $this->createElement('div');
                $div->setAttribute('class', 'tools');
                $p = $this->createElement('p', __('All of your extensions are installed and enabled.'));
                $view = $this->createElement('button', __('View Details'));

                $div->appendChild($view);
                $div->appendChild($p);

                $right->appendChild($div);
            }
        }

        $layout->appendTo($this->Form);
    }

    private function buildTable(array $extensions = null, $prefixes = false)
    {
        $aTableHead = array(
            array(__('Name'), 'col'),
            array(__('Version'), 'col'),
            array(__('Author'), 'col'),
            array(__('Actions'), 'col', array('class' => 'row-actions'))
        );

        $aTableBody = array();
        $colspan = count($aTableHead);

        if (is_null($extensions)) {
            $aTableBody = array(Widget::TableRow(
                array(
                    Widget::TableData(__('None found.'), array(
                            'class' => 'inactive',
                            'colspan' => $colspan
                        )
                    )
                ),
                array(
                    'class' => 'odd'
                )
            ));
        } else {
            foreach ($extensions as $handle) {
                $about = $this->lists->extensions[$handle]['object']->about();

                $fragment = $this->createDocumentFragment();

                if (!empty($about->{'table-link'}) && $this->lists->extensions[$handle]['status'] == Extension::STATUS_ENABLED) {
                    $fragment->appendChild(
                        Widget::Anchor($about->{'name'}, Administration::instance()->getCurrentPageURL() . '/extension/' . trim($about->{'table-link'}, '/'))
                    );
                } else {
                    $fragment->appendChild(
                        new DOMText($about->{'name'})
                    );
                }

                if ($prefixes && isset($about->{'type'})) {
                    $fragment->appendChild(
                        $this->createElement('span', ' &middot; ' . $about->{'type'}[0])
                    );
                }

                ## Setup each cell
                $td1 = Widget::TableData($fragment);
                $td2 = Widget::TableData($about->{'version'});

                $link = $about->author->name;

                if (isset($about->author->website)) {
                    $link = Widget::Anchor($about->author->name, General::validateURL($about->author->website));
                } elseif (isset($about->author->email)) {
                    $link = Widget::Anchor($about->author->name, 'mailto:' . $about->author->email);
                }

                $td3 = Widget::TableData($link);

                $td3->appendChild(Widget::Input('items['.$handle.']', 'on', 'checkbox'));


                switch ($this->lists->extensions[$handle]['status']) {
                    case Extension::STATUS_ENABLED:
                        $td4 = Widget::TableData();
                        $td4->appendChild(Widget::Input("action[uninstall][{$handle}]", __('Uninstall'), 'submit', array('class' => 'button delete')));
                        $td4->appendChild(Widget::Input("action[disable][{$handle}]", __('Disable'), 'submit', array('class' => 'button')));

                        break;

                    case Extension::STATUS_DISABLED:
                        $td4 = Widget::TableData(Widget::Input("action[enable][{$handle}]", __('Enable'), 'submit', array('class' => 'button create')));
                        break;

                    case Extension::STATUS_NOT_INSTALLED:
                        $td4 = Widget::TableData(Widget::Input("action[enable][{$handle}]", __('Install'), 'submit', array('class' => 'button create')));
                        break;

                    case Extension::STATUS_REQUIRES_UPDATE:
                        $td4 = Widget::TableData(Widget::Input("action[update][{$handle}]", __('Update'), 'submit', array('class' => 'button create')));
                }

                ## Add a row to the body array, assigning each cell to the row
                $aTableBody[] = Widget::TableRow(
                    array($td1, $td2, $td3, $td4),
                    ($this->lists->status[$handle] == Extension::STATUS_NOT_INSTALLED ? array('class' => 'inactive') : array())
                );
            }
        }

        $table = Widget::Table(Widget::TableHead($aTableHead), null, Widget::TableBody($aTableBody));

        return $table;
    }

    public function action()
    {
        if (isset($_POST['items'])) {
            $checked = array_keys($_POST['items']);
        }

        try {
            if (isset($_POST['action']['apply']) && isset($_POST['with-selected']) && is_array($checked) && !empty($checked)) {
                $action = $_POST['with-selected'];

                if (method_exists('Extension', $action)) {
                    ###
                    # Delegate: {name of the action} (enable|disable|uninstall)
                    # Description: Notifies of enabling, disabling or uninstalling of an Extension. Array of selected extensions is provided.
                    #              This can be modified.
                    Extension::notify($action, CURRENT_PATH, array('extensions' => &$checked));

                    foreach ($checked as $handle) {
                        call_user_func(array('Extension', $action), $handle);
                    }
                }

                redirect(Administration::instance()->getCurrentPageURL());
            } elseif (isset($_POST['action'])) {
                $action = end(array_keys($_POST['action']));
                $handle = end(array_keys($_POST['action'][$action]));

                if (method_exists('Extension', $action)) {
                    ###
                    # Delegate: {name of the action} (enable|disable|uninstall)
                    # Description: Notifies of enabling, disabling or uninstalling of an Extension. Extension handle is provided
                    #              This can be modified.
                    Extension::notify($action, CURRENT_PATH, array('extensions' => &$handle));

                    call_user_func(array('Extension', $action), $handle);

                    redirect(Administration::instance()->getCurrentPageURL());
                }
            }
        } catch (ExtensionException $e) {
            $extension = Extension::load($handle);
            $about = $extension->about();

            switch ($action) {
                case 'enable':
                    $message = "%s could not be enabled. <a class='more'>Show more information.</a>";
                    break;

                case 'disable':
                    $message = "%s could not be disabled. <a class='more'>Show more information.</a>";
                    break;

                case 'uninstall':
                    $message = "%s could not be uninstalled. <a class='more'>Show more information.</a>";
                    break;
            }

            $this->alerts()->append(
                __(
                    $message, array(
                        isset($about)
                            ? $about->{'name'}
                            : $name
                    )
                ),
                AlertStack::ERROR, $e
            );

            return false;
        }
    }
}
