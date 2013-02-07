<?php

function template_subtpl($tpl) {

    if($tpl["user"]["name"]) {
        echo 'My name is '.$tpl["user"]["name"];
    } else {
        echo 'I haven\'t name :(';
    };
?>

Ok.

My email <?php echo $tpl["user"]["name"].'. It is great!'; ?>

<?php
}
?>