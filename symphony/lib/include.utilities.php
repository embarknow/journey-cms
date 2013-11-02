<?php

	/***

	Method: redirect
	Description: redirects the browser to a specified location. Safer than using a direct header() call
	Param: $url - location to redirect to

	***/
   	function redirect ($url){

		$url = str_replace('Location:', NULL, $url); //Just make sure.

		if(headers_sent($filename, $line)){
			print "<h1>Error: Cannot redirect to <a href=\"$url\">$url</a></h1><p>Output has already started in $filename on line $line</p>";
			exit();
		}

		header("HTTP/1.1 301 Moved Permanently");
		header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('New-Location: ' . $url);
		header("Location: $url");
		exit();
    }

	function unparse_url(array $url) {
		return sprintf(
			'%s%s%s%s%s%s%s%s%s',
			isset($url['scheme'])
				? $url['scheme'] . '://' : '',
			isset($url['user'])
				? $url['user'] : '',
			isset($url['pass'])
				? ':' . $url['pass']  : '',
			isset($url['user']) || isset($url['pass'])
				? '@' : '',
			isset($url['host'])
				? $url['host'] : '',
			isset($url['port'])
				? ':' . $url['port'] : '',
			isset($url['path'])
				? $url['path'] : '',
			isset($url['query'])
				? '?' . $url['query'] : '',
			isset($url['fragment'])
				? '#' . $url['fragment'] : ''
		);
	}

	/**
	 * Search the apc user cache
	 *
	 * @param string $expression Perl compatible regular expression
	 * @param Closure $callback Optional callback run on each item found
	 */
	function apc_cache_search($expression, Closure $callback = null) {
		$data = apc_cache_info('user');
		$found = array();

		foreach ($data['cache_list'] as $item) {
			if ((boolean)preg_match($expression, $item['info']) === false) continue;

			if ($callback instanceof Closure) {
				$callback($item);
			}

			$found[] = $item['info'];
		}

		return $found;
	}

	function define_safe($name, $val) {
		if (!defined($name)) define($name, $val);
	}

	function getCurrentPage($page = null) {
		throw new Exception('Function getCurrentPage is deprecated, use the CURRENT_PATH constant instead.');
	}

	// Convert php.ini size format to bytes
	function ini_size_to_bytes($val) {
	    $val = trim($val);
	    $last = strtolower($val[strlen($val)-1]);
	    switch($last) {
	        // The 'G' modifier is available since PHP 5.1.0
	        case 'g':
	            $val *= 1024;
	        case 'm':
	            $val *= 1024;
	        case 'k':
	            $val *= 1024;
	    }

	    return $val;
	}