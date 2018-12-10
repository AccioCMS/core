<?php

namespace Accio\App\Interfaces;

interface PluginInterface
{
    public function register();
    public function boot();
}
