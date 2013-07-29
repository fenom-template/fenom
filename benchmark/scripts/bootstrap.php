<?php

require(__DIR__.'/../../vendor/autoload.php');

class Benchmark {
    const OUTPUT = "%8s: %-22s %10.4f sec, %10.1f MiB\n";

    public static $stress = 0;
    public static $auto_reload = false;

    public static function smarty3($tpl, $data, $double, $stress = false, $auto_reload = false) {
        $smarty = new Smarty();
        $smarty->compile_check = $auto_reload;

        $smarty->setTemplateDir(__DIR__.'/../templates');
        $smarty->setCompileDir(__DIR__."/../compile/");

        if($double) {
            $smarty->assign($data);
            $smarty->fetch($tpl);
        }

        $start = microtime(true);
        if($stress) {
            for($i=0; $i<$stress; $i++) {
                $smarty->assign($data);
                $smarty->fetch($tpl);
            }
        } else {
            $smarty->assign($data);
            $smarty->fetch($tpl);
        }
        return microtime(true) - $start;

//        printf(self::$t, __FUNCTION__, $message, round(microtime(true)-$start, 4), round(memory_get_peak_usage()/1024/1024, 2));
    }

    public static function twig($tpl, $data, $double, $stress = false, $auto_reload = false) {

        Twig_Autoloader::register();
        $loader = new Twig_Loader_Filesystem(__DIR__.'/../templates');
        $twig = new Twig_Environment($loader, array(
            'cache' => __DIR__."/../compile/",
            'autoescape' => false,
            'auto_reload' => $auto_reload,
        ));

        if($double) {
            $twig->loadTemplate($tpl)->render($data);
        }

        $start = microtime(true);
        if($stress) {
            for($i=0; $i<$stress; $i++) {
                $twig->loadTemplate($tpl)->render($data);
            }
        } else {
            $twig->loadTemplate($tpl)->render($data);
        }
        return microtime(true) - $start;
    }

    public static function fenom($tpl, $data, $double, $stress = false, $auto_reload = false) {

        $fenom = Fenom::factory(__DIR__.'/../templates', __DIR__."/../compile");
        if($auto_reload) {
            $fenom->setOptions(Fenom::AUTO_RELOAD);
        }
        if($double) {
            $fenom->fetch($tpl, $data);
        }
        $start = microtime(true);
        if($stress) {
            for($i=0; $i<$stress; $i++) {
                $fenom->fetch($tpl, $data);
            }
        } else {
            $fenom->fetch($tpl, $data);
        }
        return microtime(true) - $start;
    }

//    public static function volt($tpl, $data, $double, $message) {
//        $view = new \Phalcon\Mvc\View();
//        //$view->setViewsDir(__DIR__.'/../templates');
//        $volt = new \Phalcon\Mvc\View\Engine\Volt($view);
//
//
//        $volt->setOptions(array(
//            "compiledPath" => __DIR__.'/../compile'
//        ));
//        $tpl = __DIR__.'/../templates/'.$tpl;
//        if($double) {
//            $volt->render($tpl, $data);
//        }
//
//        $start = microtime(true);
//        $volt->render($tpl, $data);
//        printf(self::$t, __FUNCTION__, $message, round(microtime(true)-$start, 4), round(memory_get_peak_usage()/1024/1024, 2));
//    }

    public static function run($engine, $template, $data, $double, $message) {
        passthru(sprintf(PHP_BINARY." -n -dextension=phalcon.so -ddate.timezone=Europe/Moscow -dmemory_limit=512M %s/run.php --engine '%s' --template '%s' --data '%s' --message '%s' %s --stress %d %s", __DIR__, $engine, $template, $data, $message, $double ? '--double' : '', self::$stress, self::$auto_reload ? '--auto_reload' : ''));
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
}
