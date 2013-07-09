<?php

require(__DIR__.'/../../vendor/autoload.php');

class Benchmark {
	const OUTPUT = "%8s: %-22s %10.4f sec, %10.1f MiB\n";

	const STRESS_REQUEST_COUNT = 1000;
	const STRESS_FENOM_REINIT = true;
	const STRESS_TWIG_REINIT  = true;

	/** @var Fenom */
	protected static $_fenom;
	/** @var Twig_Environment */
	protected static $_twig;

    public static function smarty3($tpl, $data, $double) {
        $smarty = new Smarty();
        $smarty->compile_check = false;

        $smarty->setTemplateDir(__DIR__.'/../templates');
        $smarty->setCompileDir(__DIR__."/../compile/");

        if($double) {
            $smarty->assign($data);
            $smarty->fetch($tpl);
        }

        $start = microtime(true);
        $smarty->assign($data);
        $smarty->fetch($tpl);

        return microtime(true)-$start;
    }

    public static function twig($tpl, $data, $double) {
		if (self::STRESS_TWIG_REINIT || !self::$_twig) {
			Twig_Autoloader::register();
			$loader = new Twig_Loader_Filesystem(__DIR__.'/../templates');
			self::$_twig = new Twig_Environment($loader, array(
				'cache' => __DIR__."/../compile/",
				'autoescape' => false,
				'auto_reload' => false,
			));
		}

		$twig = self::$_twig;

        if($double) {
            $twig->loadTemplate($tpl)->render($data);
        }

        $start = microtime(true);
        $twig->loadTemplate($tpl)->render($data);
		return microtime(true)-$start;
    }

	public static function fenom($tpl, $data, $double) {
		if (self::STRESS_FENOM_REINIT || !self::$_fenom) {
			self::$_fenom = Fenom::factory(__DIR__.'/../templates', __DIR__."/../compile");
		}
		$fenom = self::$_fenom;
		$fenom->setOptions(Fenom::AUTO_RELOAD);
        if($double) {
            $fenom->fetch($tpl, $data);
        }
        $start = microtime(true);
        $fenom->fetch($tpl, $data);

		return microtime(true)-$start;
    }

    public static function volt($tpl, $data, $double) {
        $view = new \Phalcon\Mvc\View();
        //$view->setViewsDir(__DIR__.'/../templates');
        $volt = new \Phalcon\Mvc\View\Engine\Volt($view);


        $volt->setOptions(array(
            "compiledPath" => __DIR__.'/../compile',
            "compiledExtension" =>  __DIR__."/../.compile"
        ));

        if($double) {
            $volt->render($tpl, $data);
        }

        $start = microtime(true);
        $volt->render(__DIR__.'/../templates/'.$tpl, $data);
		return microtime(true)-$start;
    }

    public static function run($engine, $template, $data, $double, $message) {
        passthru(sprintf(PHP_BINARY." -dmemory_limit=512M -dxdebug.max_nesting_level=1024 %s/run.php --engine '%s' --template '%s' --data '%s' --message '%s' %s", __DIR__, $engine, $template, $data, $message, $double ? '--double' : ''));
    }

    /**
     * @param $engine
     * @param $template
     * @param $data
     */
    public static function runs($engine, $template, $data) {
        self::run($engine, $template, $data, false, '!compiled and !loaded');
        self::run($engine, $template, $data, false, ' compiled and !loaded');
        self::run($engine, $template, $data, true,  ' compiled and  loaded');
        echo "\n";
    }

	public static function stress($engine, $template, $data) {
		passthru(
			sprintf(
				PHP_BINARY." -dmemory_limit=2048M -dxdebug.max_nesting_level=1024 %s/run.php --engine '%s' --template '%s' --data '%s' --stress",
				__DIR__, $engine, $template, $data
			)
		);
	}
}