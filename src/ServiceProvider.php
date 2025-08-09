<?php

namespace ProtoneMedia\LaravelXssProtection;

use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use voku\helper\AntiXSS;

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
        $this->app->when(XssCleanInput::class)
            ->needs(AntiXSS::class)
            ->give(function () {
                $antiXss = new AntiXSS;

                $replacement = config('xss-protection.anti_xss.replacement');

                if ($replacement !== null) {
                    $antiXss->setReplacement($replacement);
                }

                $evil = config('xss-protection.anti_xss.evil');

                if ($evil !== null) {
                    if (isset($evil['attributes']) || isset($evil['tags'])) {
                        $antiXss->addEvilAttributes($evil['attributes'] ?? []);
                        $antiXss->addEvilHtmlTags($evil['tags'] ?? []);
                    } else {
                        $antiXss->addEvilAttributes($evil);
                    }
                }

                return $antiXss;
            });
    }
}
