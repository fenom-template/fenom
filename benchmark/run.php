<?php

echo "Smarty3 vs Twig vs Aspect\n\n";

echo "Generate templates... ";
passthru("php ".__DIR__."/templates/inheritance/smarty.gen.php");
passthru("php ".__DIR__."/templates/inheritance/twig.gen.php");
echo "Done\n";

echo "Testing large output...\n";
passthru("php ".__DIR__."/templates/echo.php");

echo "\nTesting 'foreach' of big array...\n";
passthru("php ".__DIR__."/templates/foreach.php");

echo "\nTesting deep 'inheritance'...\n";
passthru("php ".__DIR__."/templates/inheritance.php");

echo "\nDone. Cleanup.\n";
passthru("rm -rf ".__DIR__."/compile/*");
passthru("rm -f ".__DIR__."/templates/inheritance/smarty/*");
passthru("rm -f ".__DIR__."/templates/inheritance/twig/*");

echo "\nSmarty3 vs Aspect (more details)\n\n";

echo "Coming soon\n";