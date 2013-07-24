<?php

    function fenom_modifier_spellcount($num, $one, $two, $many)
    {
        if ($num % 10 == 1 && $num % 100 != 11) {
            echo $num . ' ' . $one;
        } elseif ($num % 10 >= 2 && $num % 10 <= 4 && ($num % 100 < 10 || $num % 100 >= 20)) {
            echo $num . ' ' . $two;
        } else {
            echo $num . ' ' . $many;
        }
    }
?>