<?php

declare(strict_types=1);

// run this before deploy!

define("FAKE_PHPUNIT", 1);

//require_once "DbMachine.php";

require_once "_autoload.php";
require_once "Tests/GameTest.php";

function runClassTests(object $x) {
    $methods = get_class_methods($x);

    $hasSetup = false;
    if (array_search("setUp", $methods)) {
        $hasSetup = true;
    }
    foreach ($methods as $method) {
        if (strpos($method, "test") === 0) {
            try {
                if ($hasSetup) {
                    call_user_func_array([$x, "setUp"], []);
                }
            } catch (Exception $e) {
                echo "FAIL: setUp $e\n";
                throw new Error();
            }
            //echo("calling $method\n");
            try {
                call_user_func_array([$x, $method], []);
            } catch (Exception $e) {
                echo "FAIL: $method $e\n";
                throw new Error();
            }
        }
    }
}

runClassTests(new GameTest("GameTest"));

echo "DONE, ALL GOOD\n";
