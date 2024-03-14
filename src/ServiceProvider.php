<?php

namespace ProtoneMedia\LaravelXssProtection;

use GrahamCampbell\SecurityCore\Security;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-xss-protection')
            ->hasConfigFile();
    }

    public function packageBooted()
    {
        $this->app->singleton(Security::class, fn () => Security::create(
            config('xss-protection.anti_xss.evil'),
            config('xss-protection.anti_xss.replacement')
        ));
    }
}
