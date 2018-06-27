<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

/**
 * Register routes
 */
use Accio\Support\Facades\Routes;


$routes = new Routes();

// Backend Routes
$routes::mapBackendRoutes();
$routes::mapPluginsBackendRoutes();

// Frontend Routes
$routes::mapFrontendBaseRoutes();
$routes::mapFrontendRoutes();
$routes::mapThemeRoutes();
$routes::mapPluginsFrontendRoutes();

// Add Language {lang} prefix
$routes::addLanguagePrefix();
$routes::sortRoutes();