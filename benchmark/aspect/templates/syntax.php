<?php

function template_syntax($tpl) {

?><html>
<head>
    <title><?php echo $tpl["title"]; ?></title>
</head>
<body>


Simple manipulations

<?php echo $tpl["item"]; ?>
<?php echo $tpl["data"][4]; ?>
<?php echo $tpl["data"]["bar"]; ?>
<?php echo $tpl["data"]["bar"]; ?>
<?php echo $tpl["data"]["bar"]; ?>
<?php echo $tpl["data"]['bar']; ?>
<?php echo $tpl["data"][ $tpl["bar_key"] ]; ?>
<?php echo $tpl["data"]["obj"]->value; ?>
<?php echo $tpl["data"]["obj"]->method(); ?>

Many other combinations are allowed

<?php echo $tpl["data"]["foo"]["bar"]; ?>
<?php echo $tpl["data"]["foo"][ $tpl["baz_key"] ][ $tpl["foo_key"] ]; ?>
<?php echo $tpl["data"]["foo"][ $tpl["bar_key"] ]; ?>
<?php echo $tpl["data"][ $tpl["foo_key"] ]["bar"]; ?>
<?php echo $tpl["data"][5]["bar"]; ?>
<?php echo $tpl["data"][5][ $tpl["bar_key"] ]; ?>
<?php echo $tpl["data"]["foo"][ $tpl["baz_key"] ]["ls"][4]; ?>
<?php echo $tpl["data"]["obj"]->methodArgs($tpl["baz_key"], 2, 2.3, "some string", $tpl["bar_key"]); ?> <-- passing parameters


Math and embedding tags:

<?php echo $tpl["x"] + $tpl["y"]; ?>                             // will output the sum of x and y.
<?php echo $tpl["data"][$tpl["x"] + 3]; ?>
<?php echo $tpl["item"] = 4 + 3; ?>                  // tags within tags
<?php echo $tpl["data"][4] = 4 + 3; ?>
<?php echo $tpl["item"] = "this is message"; ?>  // tags within double quoted strings


Short variable assignment:

<?php echo $tpl["item"] = $tpl["y"] + 2; ?>
<?php echo $tpl["item"] = strlen($tpl["bar_key"]); ?>               // function in assignment
<?php echo $tpl["item"] = intval(($tpl["x"] + $tpl["y"]) * 3); ?>       // as function parameter
<?php echo $tpl["data"]["bar"] = 1; ?>                        // assign to specific array element
<?php echo $tpl["data"]["foo"]["bar"] = "data.foo.bar: tpl value"; ?>

Smarty "dot" syntax (note: embedded {} are used to address ambiguities):

<?php echo $tpl["data"]["foo"]["baz"]["foo"]; ?>
<?php echo $tpl["data"]["foo"][ $tpl["baz_key"] ]["foo"]; ?>
<?php echo $tpl["data"][$tpl["y"] + 4]["foo"]; ?>
<?php echo $tpl["data"]["foo"][$tpl["data"]["baz_key"]]["foo"]; ?>

Object chaining:

<?php echo $tpl["data"]["obj"]->getSelf($tpl["x"])->method($tpl["y"]); ?>

Direct PHP function access:

<?php echo strlen("hi!"); ?>


<?php
    if($tpl["logged_in"]) {
        echo 'Welcome, <span style="color: '.$tpl["fontColor"].'">'.$tpl["name"].'</span>';
    } else {
        echo "hi, ".$tpl["user"]["name"];
    }
?>

Embedding Vars in Double Quotes

<?php echo MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => "test ".$tpl["item"]." test"), $tpl).
    MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => "test ".$tpl["foo_key"]." test"), $tpl).
    MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => "test ".($tpl["data"][4])." test"), $tpl).
    MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => "test ".$tpl["item"].".bar test"), $tpl).
    MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => 'test {$data.barz} test'), $tpl).
    MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => "test ".($tpl["data"]["barz"])." test"), $tpl).
    MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => strtoupper("test ".($tpl["data"]["barz"])." test")), $tpl).
    MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => "test ".(strtoupper($tpl["data"]["barz"]))." test"), $tpl); ?>


will replace $tpl_name with value
<?php template_subtpl($tpl); ?>

does NOT replace $tpl_name
<?php echo MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => "one,two"), $tpl); ?>

<?php template_subtpl($tpl); ?>

Math

some more complicated examples

<?php echo $tpl["data"][2] - $tpl["data"]["obj"]->num * !$tpl["data"]["foo"]["baz"]["ls"][4] - 3 * 7 % $tpl["data"][2]; ?>

<?php
    if($tpl["data"][2] - $tpl["data"]["obj"]->num * $tpl["data"]["foo"]["baz"]["ls"][4] - 3 * 7 % $tpl["data"][2]) {
        echo MF\Misc\Str::truncate($tpl["data"]["barz"], "".($tpl["data"][2] / 2 - 1)."")."\n".
            MF\Misc\Str::truncate($tpl["data"]["barz"], ($tpl["data"][2] / 2 - 1));
    }
?>

Escaping Smarty Parsing

<script>
    // the following braces are ignored by Smarty
    // since they are surrounded by whitespace
    function foobar() {
        alert('foobar!');
    }
    // this one will need literal escapement
    <?php  ?>
    function bazzy() {alert('foobar!');}
    </script>


name:  <?php echo $tpl["user"]["name"]; ?><br />
email: <?php echo $tpl["user"]["email"]; ?><br />

Modifier examples

apply modifier to a variable
<?php echo strtoupper($tpl["user"]["name"]); ?>

modifier with parameters
<?php echo MF\Misc\Str::truncate($tpl["user"]["name"], 40, "..."); ?>

apply modifier to a function parameter
<?php echo MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => strtoupper($tpl["user"]["name"])), $tpl); ?>

with parameters
<?php echo MF\Aspect\Func::mailto(array("address" => $tpl["user"]["email"],"text" => MF\Misc\Str::truncate($tpl["user"]["name"], 40, "...")), $tpl); ?>

apply modifier to literal string

using date_format to format the current date
<?php echo MF\Aspect\Modifier::dateFormat(time(), "%Y/%m/%d"); ?>

apply modifier to a custom function

Foreach
<?php
    if($tpl["contacts"]) {
        foreach($tpl["contacts"] as $tpl["contact"]) {
            if($tpl["contact"]) {
                foreach($tpl["contact"] as $tpl["key"] => $tpl["value"]) {
                    echo $tpl["key"].": ".$tpl["value"]." ";
                }
            } else {
                echo "no items";
            }
        }
    }
?>

If condition
<?php
    if(isset($tpl["user"]["name"]) && $tpl["user"]["name"] == 'yandex') {
        echo "do something";
    } elseif($tpl["user"]["name"] == $tpl["data"]["foo"]["bar"]) {
        echo "do something2";
    }
?>


<?php
    if(is_array($tpl["data"]["foo"]) && count($tpl["data"]["foo"]) > 0) {
        echo "do a foreach loop";
    }
?>


</body>
</html>
<?php
}
?>