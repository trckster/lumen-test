<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('sendCode', 'EmailVerificationController@sendCode');

$router->get('checkCode', 'EmailVerificationController@checkCode');
