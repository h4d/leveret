<?php

use H4D\Leveret\Application;
use H4D\Leveret\Validation\Adapters\H4DConstraintAdapter;
use H4D\Validator\Constraints\Enum;

require_once(__DIR__.'/../../vendor/autoload.php');

$app = new Application();
$app->registerRoute('GET', '/hello/:(string)name')
    ->addRequestConstraints('name', new H4DConstraintAdapter((new Enum())->setOptions(['paco', 'maria'])))
    ->setAction(
        function ($name) use ($app)
        {
            $isValid = $app->isValidRequest();
            if (!$isValid)
            {
                throw new \Exception($app->getRequestConstraintsViolationMessagesAsString());
            }
            $app->getResponse()->setBody('Hello '.$name);
        });
$app->run();