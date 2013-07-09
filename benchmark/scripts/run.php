<?php

$opt = getopt("", array(
	/** @var string $engine */
	"engine:",
	/** @var string $template */
	"template:",
	/** @var string $data */
	"data:",
	/** @var boolean $double */
	"double",
	/** @var boolean $stress */
	"stress",
	/** @var string $message */
	"message:"
));

require_once __DIR__.'/bootstrap.php';

extract($opt);

if (isset($stress)) {
	$start = microtime(true);
	$message = 'stress test';
	$data = json_decode(file_get_contents($data), true);
	gc_enable();
	for ($i = 0; $i < Benchmark::STRESS_REQUEST_COUNT; $i++) {
		Benchmark::$engine($template, $data, false);
		if ($i % 50 == 0) gc_collect_cycles();
	}
	$time = microtime(true) - $start;

} else {
	$time = Benchmark::$engine($template, json_decode(file_get_contents($data), true), isset($double));

}
printf(Benchmark::OUTPUT, $engine, $message, round($time, 4), round(memory_get_peak_usage()/1024/1024, 2));

