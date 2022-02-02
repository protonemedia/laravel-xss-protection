<?php

namespace ProtoneMedia\LaravelXssProtection\Cleaners;

class BladeEchoes
{
    protected $bladeEchoPatterns = [];

    /**
     * @see Illuminate\View\Compilers\Concerns\CompilesEchos::compileRawEchos
     */
    public function __construct()
    {
        foreach (config('xss-protection.blade_echo_tags') as $pair) {
            $this->bladeEchoPatterns[] = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $pair[0], $pair[1]);
        }

        usort($this->bladeEchoPatterns, fn ($a, $b) => strlen($b) <=> strlen($a));
    }

    public function clean(string $value): string
    {
        foreach ($this->bladeEchoPatterns as $pattern) {
            if (preg_match($pattern, $value, $matches)) {
                return str_replace($matches[0], '', $value);
            }
        }

        return $value;
    }
}
