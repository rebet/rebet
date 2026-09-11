<?php
declare(strict_types=1);

//-------------------------------------------------
// Register The Auto Loader
//-------------------------------------------------
require __DIR__.'/../vendor/autoload.php';

//-------------------------------------------------
// Create kernel and initilizing application
//-------------------------------------------------
$kernel = Rebet\Application\App::init(new App\Http\AppWebKernel(new App\AppStructure(__DIR__.'/../')));

//-------------------------------------------------
// Routing Request and handle action
//-------------------------------------------------
$kernel->handle(Rebet\Http\Request::createFromGlobals())->send();

//-------------------------------------------------
// Terminate the router
//-------------------------------------------------
$kernel->terminate();
