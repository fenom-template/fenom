<?php
namespace Aspect;

class Func {
    public static function mailto($params) {
        if(empty($params["address"])) {
            trigger_error(E_USER_WARNING, "Modifier mailto: paramenter 'address' required");
            return "";
        }

        if(empty($params["text"])) {
            $params["text"] = $params["address"];
        }

        return '<a href="mailto:'.$params["address"].'">'.$params["text"].'</a>';
    }
}
