<?php

require(__DIR__.'/../../vendor/autoload.php');

class Benchmark {
    public static $t = "%8s: %-22s %10.4f sec, %10.1f MiB\n";

    public static function smarty3($tpl, $data, $double, $message) {
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

        printf(self::$t, __FUNCTION__, $message, round(microtime(true)-$start, 4), round(memory_get_peak_usage()/1024/1024, 2));
    }

    public static function twig($tpl, $data, $double, $message) {

        Twig_Autoloader::register();
        $loader = new Twig_Loader_Filesystem(__DIR__.'/../templates');
        $twig = new Twig_Environment($loader, array(
            'cache' => __DIR__."/../compile/",
            'autoescape' => false,
            'auto_reload' => false,
        ));

        if($double) {
            $twig->loadTemplate($tpl)->render($data);
        }

        $start = microtime(true);
        $twig->loadTemplate($tpl)->render($data);
        printf(self::$t, __FUNCTION__, $message, round(microtime(true)-$start, 4), round(memory_get_peak_usage()/1024/1024, 2));
    }

    public static function aspect($tpl, $data, $double, $message) {

        $cytro = Cytro::factory(__DIR__.'/../templates', __DIR__."/../compile/");

        if($double) {
            $cytro->fetch($tpl, $data);
        }
        $_SERVER["t"] = $start = microtime(true);
        $cytro->fetch($tpl, $data);
        printf(self::$t, __FUNCTION__, $message, round(microtime(true)-$start, 4), round(memory_get_peak_usage()/1024/1024, 2));
    }

    public static function run($engine, $template, $data, $double, $message) {
        passthru(sprintf("php -dmemory_limit=512M %s/run.php --engine '%s' --template '%s' --data '%s' --message '%s' %s", __DIR__, $engine, $template, $data, $message, $double ? '--double' : ''));
    }

    public static function runs($engine, $template, $data) {
        self::run($engine, $template, $data, false, '!compiled and !loaded');
        self::run($engine, $template, $data, false, ' compiled and !loaded');
        self::run($engine, $template, $data, true,  ' compiled and  loaded');
        echo "\n";
    }
}

function t() {
    if(isset($_SERVER["t"])) var_dump(round(microtime(1) - $_SERVER["t"], 4));
}