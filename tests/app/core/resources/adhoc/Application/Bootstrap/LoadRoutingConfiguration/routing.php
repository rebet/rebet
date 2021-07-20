<?php

use Rebet\Routing\Router;

//---------------------------------------------
// Routing Settings
//---------------------------------------------
Router::rules('web')->guard('web')->roles('user')->routing(function(){
	Router::get('/hello', function(){ return "Hello World."; });
});
