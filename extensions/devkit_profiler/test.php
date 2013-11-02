<?php

	require_once __DIR__ . '/lib/interface.profiler.php';
	require_once __DIR__ . '/lib/class.active-profiler.php';
	require_once __DIR__ . '/lib/class.dummy-profiler.php';

	class Extension_DevKit_Profiler {
		static public $profiler;
	}

	function get_profiler() {
		if (isset(Extension_DevKit_Profiler::$profiler) === false) {
			if (isset($_GET['profiler'])) {
				Extension_DevKit_Profiler::$profiler = new ActiveProfiler();
			}

			else {
				Extension_DevKit_Profiler::$profiler = new DummyProfiler();
			}
		}

		return Extension_DevKit_Profiler::$profiler;
	}

	$p = get_profiler();
	$p->begin('Symphony execution');

	$p->begin('Executing datasources');
		$p->begin('Example datasource 1');
		usleep(80000);
		$p->end();
		$p->begin('Example datasource 2');
		usleep(50000);
		$p->end();
	$p->end();

	$p->begin('Some long linear process');
	usleep(55000);
	$p->notice('Task 1 completed');
	usleep(10000);
	$p->notice('Task 2 completed');
	usleep(70000);
	$p->notice('Task 3 completed')
		->end();

	$p->end();

	var_dump($p);