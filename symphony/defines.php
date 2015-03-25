<?php

	define_safe('__IN_SYMPHONY__', true);

	define_safe('MANIFEST', 	DOCROOT . '/manifest');
	define_safe('SYMPHONY', 	DOCROOT . '/symphony');
	define_safe('EXTENSIONS', 	DOCROOT . '/extensions');
	define_safe('WORKSPACE', 	DOCROOT . '/workspace');

	define_safe('LIB',		SYMPHONY . '/lib');
	define_safe('ASSETS', 	SYMPHONY . '/assets');
	define_safe('LANG',		SYMPHONY . '/lang');

	define_safe('ACTORS',			WORKSPACE . '/actors');
	define_safe('FORMS',			WORKSPACE . '/forms');
	define_safe('UTILITIES', 		WORKSPACE . '/utilities');
	define_safe('DATASOURCES',		WORKSPACE . '/data-sources');
	define_safe('EVENTS',			WORKSPACE . '/events');
	define_safe('TEXTFORMATTERS',	WORKSPACE . '/text-formatters');

	define_safe('VIEWS',			WORKSPACE . '/views');
	define_safe('SECTIONS',			WORKSPACE . '/sections');

	define_safe('CACHE',	MANIFEST . '/cache');
	define_safe('TMP',		MANIFEST . '/tmp');
	define_safe('LOGS',		MANIFEST . '/logs');
	define_safe('CONF', 	MANIFEST . '/conf');

	define_safe('CONTENT', 	SYMPHONY . '/content');

	define_safe('TEMPLATES', SYMPHONY . '/templates');

	define_safe('STARTTIME', microtime(true));

	define_safe('TWO_WEEKS',	(60 * 60 * 24 * 14));
	define_safe('CACHE_LIFETIME', TWO_WEEKS);

	define_safe('HTTPS', getenv('HTTPS'));
	define_safe('HTTP_HOST', getenv('HTTP_HOST'));
	define_safe('REMOTE_ADDR', getenv('REMOTE_ADDR'));
	define_safe('HTTP_USER_AGENT', getenv('HTTP_USER_AGENT'));

	define_safe('__SECURE__', (HTTPS == 'on'));
	define_safe('URL', 'http' . (defined('__SECURE__') && __SECURE__ ? 's' : '') . '://' . DOMAIN);

	$root_url = parse_url(URL);

	define_safe('ROOT_PATH', $root_url['path']);
	define_safe('CURRENT_PATH', (
		isset($_GET['symphony-page'])
		&& strlen(ltrim($_GET['symphony-page'], '/')) > 0
			? '/' . ltrim($_GET['symphony-page'], '/')
			: null
	));

	define_safe('ACTIVITY_LOG', LOGS . '/main');