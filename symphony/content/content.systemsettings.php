<?php

use Embark\CMS\Configuration\Controller as Configuration;

    require_once LIB . '/class.administrationpage.php';
    require_once LIB . '/class.duplicator.php';

    class contentSystemSettings extends AdministrationPage
    {
        public function __construct()
        {
            parent::__construct();

            $this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Settings'))));
            /*
            $element = $this->createElement('p');
            $element->appendChild(new DOMEntityReference('ndash'));
            $this->Body->appendChild($element);*/
        }

        public function appendTabs()
        {
            $path = ADMIN_URL . '/system/settings/';

            $options = array(
                __('Basics')     => $path,
                __('Advanced')     => $path . 'advanced/',
                __('Extensions') => $path . 'extensions/'
            );

            // Hide Extensions tab:
            if (Extension::delegateSubscriptionCount('AddSettingsFieldsets', '/system/settings/extensions/') == 0) {
                unset($options[__('Extensions')]);
            }

            $this->appendViewOptions($options);
        }

        ## Overload the parent 'view' function since we dont need the switchboard logic
        public function __viewIndex()
        {
            $this->appendSubheading(__('Settings'));
            $this->appendTabs();

            if (!is_writable(Configuration::locate('main'))) {
                $this->alerts()->append(
                    __('The core Symphony configuration file, /app/config/main.xml, is not writable. You will not be able to save any changes.'),
                    AlertStack::ERROR
                );
            }

            $mainConfig = Configuration::read('main');

            // Status message:
            $callback = Administration::instance()->getPageCallback();

            if (isset($callback['flag']) && !is_null($callback['flag'])) {
                switch ($callback['flag']) {
                    case 'saved':
                        $this->alerts()->append(
                            __(
                                'System settings saved at %1$s.',
                                array(
                                    General::getTimeAgo(__SYM_TIME_FORMAT__)
                                )
                            ),
                            AlertStack::SUCCESS
                        );
                        break;
                }
            }

            // SETUP PAGE
            $layout = new Layout();

            $left = $layout->createColumn(Layout::LARGE);
            $center = $layout->createColumn(Layout::LARGE);
            $right = $layout->createColumn(Layout::LARGE);

            $this->coreSettingsPanel($mainConfig, $left);
            $this->adminSettingsPanel($mainConfig, $center);
            $this->regionSettingsPanel($mainConfig, $right);

            $layout->appendTo($this->Form);

            $div = $this->createElement('div');
            $div->setAttribute('class', 'actions');

            $attr = array('accesskey' => 's');

            if (!is_writable(Configuration::locate('main'))) {
                $attr['disabled'] = 'disabled';
            }

            $div->appendChild(
                Widget::Submit(
                    'action[save]', __('Save Changes'),
                    $attr
                )
            );

            $this->Form->appendChild($div);
        }

        public function __viewAdvanced()
        {
            $this->appendSubheading(__('Advanced'));
            $this->appendTabs();

            if (!is_writable(Configuration::locate('main'))) {
                $this->alerts()->append(
                    __('The core Symphony configuration file, /app/config/main.xml, is not writable. You will not be able to save any changes.'),
                    AlertStack::ERROR
                );
            }

            $mainConfig = Configuration::read('main');

            // Status message:
            $callback = Administration::instance()->getPageCallback();

            if (isset($callback['flag']) && !is_null($callback['flag'])) {
                switch ($callback['flag']) {
                    case 'saved':
                        $this->alerts()->append(
                            __(
                                'System settings saved at %1$s.',
                                array(
                                    General::getTimeAgo(__SYM_TIME_FORMAT__)
                                )
                            ),
                            AlertStack::SUCCESS
                        );
                        break;
                }
            }

            // SETUP PAGE
            $layout = new Layout();

            $left = $layout->createColumn(Layout::LARGE);
            $center = $layout->createColumn(Layout::LARGE);
            $right = $layout->createColumn(Layout::LARGE);

            $this->logSettingsPanel($mainConfig, $left);
            $this->sessionSettingsPanel($mainConfig, $center);
            $this->systemSettingsPanel($mainConfig, $right);

            $layout->appendTo($this->Form);

            $div = $this->createElement('div');
            $div->setAttribute('class', 'actions');

            $attr = array('accesskey' => 's');

            if (!is_writable(Configuration::locate('main'))) {
                $attr['disabled'] = 'disabled';
            }

            $div->appendChild(
                Widget::Submit(
                    'action[save]', __('Save Changes'),
                    $attr
                )
            );

            $this->Form->appendChild($div);
        }

        public function __viewExtensions() {
            $this->appendSubheading(__('Settings'));
            $this->appendTabs();

            $path = ADMIN_URL . '/symphony/system/settings/';

            // No settings for extensions here
            if (Extension::delegateSubscriptionCount('AddSettingsFieldsets', '/system/settings/extensions/') <= 0) {
                redirect($path);
            }

            // Status message:
            $callback = Administration::instance()->getPageCallback();

            if (isset($callback['flag']) && !is_null($callback['flag'])) {
                switch ($callback['flag']) {
                    case 'saved':
                        $this->alerts()->append(
                            __(
                                'System settings saved at %1$s.',
                                array(
                                    General::getTimeAgo(__SYM_TIME_FORMAT__)
                                )
                            ),
                            AlertStack::SUCCESS
                        );
                        break;
                }
            }

            $extension_fieldsets = array();

            ###
            # Delegate: AddSettingsFieldsets
            # Description: Add Extension settings fieldsets. Append fieldsets to the array provided. They will be distributed evenly accross the 3 columns
            Extension::notify('AddSettingsFieldsets', '/system/settings/extensions/', array('fieldsets' => &$extension_fieldsets));

            if(empty($extension_fieldsets)) redirect($path);

            $layout = new Layout();
            $left = $layout->createColumn(Layout::LARGE);
            $center = $layout->createColumn(Layout::LARGE);
            $right = $layout->createColumn(Layout::LARGE);

            foreach($extension_fieldsets as $index => $fieldset){
                $index += 1;
                if($index % 3 == 0) $right->appendChild($fieldset);
                elseif($index % 2 == 0) $center->appendChild($fieldset);
                else $left->appendChild($fieldset);
            }

            $layout->appendTo($this->Form);

            $div = $this->createElement('div');
            $div->setAttribute('class', 'actions');
            $div->appendChild(
                Widget::Submit(
                    'action[save]', __('Save Changes'),
                    array(
                        'accesskey' => 's'
                    )
                )
            );

            $this->Form->appendChild($div);
        }

        public function __actionExtensions() {
            ###
            # Delegate: CustomSaveActions
            # Description: This is where Extensions can hook on to custom actions they may need to provide.
            Extension::notify('CustomSaveActions', '/system/settings/extensions/');

            if (isset($_POST['action']['save']) && isset($_POST['settings'])) {
                $settings = $_POST['settings'];

                if ($this->errors->length() <= 0) {

                    if(is_array($settings) && !empty($settings)){
                        foreach($settings as $set => $values) {
                            foreach($values as $key => $val) {
                                Symphony::Configuration()->main()->{$set}->{$key} = $val;
                            }
                        }
                    }

                    Symphony::Configuration()->save();

                    redirect(ADMIN_URL . '/system/settings/extensions/:saved/');
                }
                else{
                    $this->alerts()->append(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), AlertStack::ERROR, $this->errors);
                }
            }
        }

        public function __actionIndex() {

            if (!is_writable(Configuration::locate('main'))) {
                return;
            }

            if (isset($_POST['action']['save'])) {
                $settings = $_POST['settings'];

                ###
                # Delegate: Save
                # Description: Saving of system preferences.
                Extension::notify('Save', '/system/settings/', array(
                    'settings' => &$settings,
                    'errors' => &$this->errors
                ));

                // Site Name
                if (strlen(trim($settings['name'])) === 0) {
                    $this->errors->append('name', __("'%s' is a required field.", array('Site Name')));
                }


                // Admin Path
                if (strlen(trim($settings['admin']['path'])) === 0) {
                    $this->errors->append('admin::path', __("'%s' is a required field.", array('Admin URL')));
                }

                // Entries Per Page
                if (strlen(trim($settings['admin']['pagination'])) === 0) {
                    $this->errors->append('admin::pagination', __("'%s' is a required field.", array('Entries Per Page')));
                }
                if (!is_numeric($settings['admin']['pagination'])) {
                    $this->errors->append('admin::pagination', __("'%s' must be a number.", array('Entries Per Page')));
                }


                // Date format
                // TODO: Figure out a way to check date formats to ensure they are valid
                if (strlen(trim($settings['region']['date-format'])) == 0) {
                    $this->errors->append('region::date-format', __("'%s' is a required field.", array('Date Format')));
                }

                // Time format
                // TODO: Figure out a way to check time formats to ensure they are valid
                if (strlen(trim($settings['region']['time-format'])) == 0) {
                    $this->errors->append('region::time-format', __("'%s' is a required field.", array('Time Format')));
                }

                if ($this->errors->length() <= 0) {
                    $config = Configuration::read('main');

                    if ($this->saveSettings($config, $settings)) {
                        Configuration::update($config, 'main');
                    }

                    redirect(ADMIN_URL . '/system/settings/:saved/');
                }
                else{
                    $this->alerts()->append(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), AlertStack::ERROR, $this->errors);
                }
            }
        }

        public function __actionAdvanced()
        {
            if (!is_writable(Configuration::locate('main'))) {
                return;
            }

            if (isset($_POST['action']['save'])) {
                $settings = $_POST['settings'];

                ###
                # Delegate: Save
                # Description: Saving of system preferences.
                Extension::notify('Save', '/system/settings/', array(
                    'settings' => &$settings,
                    'errors' => &$this->errors
                ));

                // Archive
                if (strlen(trim($settings['logging']['archive'])) === 0) {
                    $this->errors->append('archive::name', __("'%s' is a required field.", array('Archive')));
                }

                // Maximum Log Size
                if (strlen(trim($settings['logging']['maxsize'])) === 0) {
                    $this->errors->append('archive::maxsize', __("'%s' is a required field.", array('Miximum Logfile Size')));
                }


                // Session Cookie Prefix
                if (strlen(trim($settings['session']['cookie-prefix'])) === 0) {
                    $this->errors->append('session::cookie-prefix', __("'%s' is a required field.", array('Cookie Prefix')));
                }


                // Maximum Upload Filesize
                if (strlen(trim($settings['system']['maximum-upload-size'])) === 0) {
                    $this->errors->append('system::maximum-upload-size', __("'%s' is a required field.", array('Maximum Upload Filesize')));
                }

                if ($this->errors->length() <= 0) {
                    $config = Configuration::read('main');

                    if ($this->saveSettings($config, $settings)) {
                        Configuration::update($config, 'main');
                    }

                    redirect(ADMIN_URL . '/system/settings/advanced/:saved/');
                }
                else{
                    $this->alerts()->append(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), AlertStack::ERROR, $this->errors);
                }
            }
        }

        protected function coreSettingsPanel($config, $column)
        {
            $fieldset = Widget::Fieldset(__('Site Setup'));

            $label = Widget::Label(__('Site Name'));
            $input = Widget::Input('settings[name]', $config['name']);
            $label->appendChild($input);

            if (isset($this->errors->{'name'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'name'});
            }
            $fieldset->appendChild($label);

            $languages = Lang::getAvailableLanguages(true);

            if (count($languages) > 1) {
                $label = Widget::Label(__('Default Language'));
                asort($languages);
                $options = [];

                foreach ($languages as $code => $name) {
                    $options[] = [$code, $code === $config['lang'], $name];
                }

                $select = Widget::Select('settings[lang]', $options);
                $label->appendChild($select);
                $fieldset->appendChild($label);
            }

            $column->appendChild($fieldset);
        }

        protected function adminSettingsPanel($config, $column)
        {
            $fieldset = Widget::Fieldset(__('Admin Settings'));

            $label = Widget::Label(__('Admin URL'));
            $input = Widget::Input('settings[admin][path]', $config['admin']['path']);
            $label->appendChild($input);
            $help = $this->createElement('p', __('Changing this will log you out.'));
            $help->addClass('help');
            $label->appendChild($help);

            if (isset($this->errors->{'admin::path'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'admin::path'});
            }
            $fieldset->appendChild($label);

            $label = Widget::Label(__('Entries Per Page'));
            $input = Widget::Input('settings[admin][pagination]', $config['admin']['pagination']);
            $label->appendChild($input);

            if (isset($this->errors->{'admin::pagination'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'admin::pagination'});
            }
            $fieldset->appendChild($label);

            $label = Widget::Label(__('Minify Assets'));
            $options = [];
            foreach (['yes', 'no'] as $option) {
                $options[] = [$option, $option === $config['admin']['minify-assets'], ucfirst($option)];
            }
            $select = Widget::Select('settings[admin][minify-assets]', $options);
            $label->appendChild($select);
            $fieldset->appendChild($label);

            $column->appendChild($fieldset);
        }

        protected function regionSettingsPanel($config, $column)
        {
            $fieldset = Widget::Fieldset(__('Date & Time Settings'));

            $label = Widget::Label(__('Date Format'));
            $input = Widget::Input('settings[region][date-format]', $config['region']['date-format']);
            $label->appendChild($input);

            if (isset($this->errors->{'region::date-format'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'region::date-format'});
            }
            $fieldset->appendChild($label);

            $label = Widget::Label(__('Time Format'));
            $input = Widget::Input('settings[region][time-format]', $config['region']['time-format']);
            $label->appendChild($input);

            if (isset($this->errors->{'region::time-format'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'region::time-format'});
            }
            $fieldset->appendChild($label);

            $label = Widget::Label(__('Timezone'));
            $timezones = timezone_identifiers_list();
            $options = [];
            foreach ($timezones as $timezone) {
                $options[] = [$timezone, $timezone === $config['region']['timezone'], $timezone];
            }
            $select = Widget::Select('settings[region][timezone]', $options);
            $label->appendChild($select);
            $fieldset->appendChild($label);

            $column->appendChild($fieldset);
        }

        protected function logSettingsPanel($config, $column)
        {
            $fieldset = Widget::Fieldset(__('Log File Settings'));

            $label = Widget::Label(__('Archive'));
            $options = [];
            foreach (['yes', 'no'] as $option) {
                $options[] = [$option, $option === 'yes' && $config['logging']['archive'], ucfirst($option)];
            }
            $select = Widget::Select('settings[logging][archive]', $options);
            $label->appendChild($select);

            if (isset($this->errors->{'logging::archive'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'logging::archive'});
            }
            $fieldset->appendChild($label);

            $label = Widget::Label(__('Maximum Logfile Size'));
            $input = Widget::Input('settings[logging][maxsize]', $config['logging']['maxsize']);
            $label->appendChild($input);
            $help = $this->createElement('p', __('The size is in bytes.'));
            $help->addClass('help');
            $label->appendChild($help);

            if (isset($this->errors->{'logging::maxsize'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'logging::maxsize'});
            }
            $fieldset->appendChild($label);


            $column->appendChild($fieldset);
        }

        protected function sessionSettingsPanel($config, $column)
        {
            $fieldset = Widget::Fieldset(__('Session Settings'));

            $label = Widget::Label(__('Cookie Prefix'));
            $input = Widget::Input('settings[session][cookie-prefix]', $config['session']['cookie-prefix']);
            $label->appendChild($input);

            if (isset($this->errors->{'session::cookie-prefix'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'session::cookie-prefix'});
            }
            $fieldset->appendChild($label);

            $column->appendChild($fieldset);
        }

        protected function systemSettingsPanel($config, $column)
        {
            $fieldset = Widget::Fieldset(__('Filesystem Settings'));

            $folderPermissions = array(
                '0775',
                '0755',
                '0750'
            );

            $filePermissions = array(
                '0664',
                '0644',
                '0640'
            );

            $fileperms = $config['system']['file-write-mode'];
            $fileOptions = [];
            $dirperms = $config['system']['directory-write-mode'];
            $folderOptions = [];

            $label = Widget::Label(__('File Permissions'));

            foreach ($filePermissions as $p) {
                $fileOptions[] = array($p, $p == $fileperms, $p);
            }

            if (!in_array($fileperms, $filePermissions)) {
                $fileOptions[] = array($fileperms, true, $fileperms);
            }

            $select = Widget::Select('settings[system][file-write-mode]', $fileOptions);
            unset($options);
            $label->appendChild($select);
            $fieldset->appendChild($label);

            $label = Widget::Label(__('Directory Permissions'));
            foreach ($folderPermissions as $p) {
                $folderOptions[] = array($p, $p == $dirperms, $p);
            }

            if (!in_array($dirperms, $folderPermissions)) {
                $folderOptions[] = array($dirperms, true, $dirperms);
            }

            $select = Widget::Select('settings[system][directory-write-mode]', $folderOptions);
            unset($options);
            $label->appendChild($select);
            $fieldset->appendChild($label);

            $label = Widget::Label(__('Maximum Upload Filesize'));
            $input = Widget::Input('settings[system][maximum-upload-size]', $config['system']['maximum-upload-size']);
            $label->appendChild($input);
            $help = $this->createElement('p', __('The size is in bytes.'));
            $help->addClass('help');
            $label->appendChild($help);

            if (isset($this->errors->{'system::maximum-upload-size'})) {
                $label = Widget::wrapFormElementWithError($label, $this->errors->{'system::maximum-upload-size'});
            }
            $fieldset->appendChild($label);

            $column->appendChild($fieldset);
        }

        protected function saveSettings($config, array $settings)
        {
            if (!empty($settings)) {
                foreach ($settings as $key => $values) {
                    if (is_array($values)) {
                        foreach ($values as $vkey => $value) {
                            $config[$key][$vkey] = $value;
                        }
                    } else {
                        $config[$key] = $values;
                    }
                }

                return true;
            }

            return false;
        }
    }
