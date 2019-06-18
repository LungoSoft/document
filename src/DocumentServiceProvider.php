<?php

namespace Lungo\Doc;

use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->configPath() => config_path('document.php')]);
        }
    }

    protected function configPath()
    {
        return __DIR__ . '/../config/document.php';
    }
}
