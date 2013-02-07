<?php

class obj {
    public $value = "obj.value property";
    public $num = 777;

    public function method() {
        return "object method";
    }

    public function methodArgs($i, $j, $k, $str, $value) {
        return "object method with ars $i, $j, $k, $str, $value";
    }

    public function __toString() {
        return "object";
    }

    public function getSelf() {
        return $this;
    }
}

return array(
    "title" => "syntax test page",

    "user" => array(
        "email" => 'bzick@megagroup.ru',
        "name" => 'Ivan'
    ),

    "data" => array(
        1 => array(
            "foo" => "data.1.foo value"
        ),
        2 => 4,
        4 => "data.four value",
        5 => array(
            "bar" => "data.5.baz value",
            "baz" => array(
                "foo" => "data.5.baz.foo value"
            )
        ),
        6 => array(
            "foo" => "data.6.foo value"
        ),
        "bar" => "data.bar value",
        "barz" => "data.barz value",
        "baz_key" => "baz",
        "foo" => array(
            "bar" => "data.foo.baz value",
            "baz" => array(
                "foo" => "data.foo.baz.foo value",
                "ls" => array(
                    4 => "data.foo.baz.ls.4 value",
                    5 => 555
                )
            )
        ),
        "obj" => new obj(),
        "tpl_name" => 'subdir/subtpl'
    ),

    "foo_key" => "foo",
    "bar_key" => "bar",
    "barz_key" => "barz",
    "baz_key" => "baz",

    "item" => "some item",
    "x" => 1,
    "y" => 2,
    "tpl_name" => "subtpl",

    "contacts" => array(
        array(
            "foo" => "bar",
            "foo1" => "bar1",
            "foo2" => "bar2",
        ),
        array(
            "baz" => "buh",
            "baz1" => "buh1",
            "baz2" => "buh2",
        )
    ),
    "logged_in" => false
);