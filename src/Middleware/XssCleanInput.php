<?php

namespace ProtoneMedia\LaravelXssProtection\Middleware;

use Closure;
use GrahamCampbell\SecurityCore\Security;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use ProtoneMedia\LaravelXssProtection\Cleaners\BladeEchoes;
use ProtoneMedia\LaravelXssProtection\Events\MaliciousInputFound;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class XssCleanInput extends TransformsRequest
{
    /**
     * The security instance.
     *
     * @var \GrahamCampbell\SecurityCore\Security
     */
    protected $security;

    /**
     * The Blade echo cleaner instance.
     *
     * @var \ProtoneMedia\LaravelXssProtection\Cleaners\BladeEchoes
     */
    protected $bladeEchoCleaner;

    /**
     * All of the registered skip callbacks.
     *
     * @var array
     */
    protected static $skipCallbacks = [];

    /**
     * The attributes that should not be cleaned.
     *
     * @var array
     */
    protected $exceptKeys = [
        //
    ];

    /**
     * Array of sanitized keys.
     *
     * @var array
     */
    protected $sanitizedKeys = [];

    /**
     * Create a new instance.
     *
     * @param \GrahamCampbell\SecurityCore\Security $security
     * @param \ProtoneMedia\LaravelXssProtection\Cleaners\BladeEchoes $bladeEchoCleaner
     *
     * @return void
     */
    public function __construct(Security $security, BladeEchoes $bladeEchoCleaner)
    {
        $this->security         = $security;
        $this->bladeEchoCleaner = $bladeEchoCleaner;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->sanitizedKeys = [];

        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return $next($request);
            }
        }

        $originalRequest = clone $request;

        $this->clean($request);

        if (count($this->sanitizedKeys) === 0) {
            return $next($request);
        }

        if ($this->enabledInConfig('dispatch_event_on_malicious_input')) {
            event(new MaliciousInputFound($this->sanitizedKeys, $originalRequest, $request));
        }

        if ($this->enabledInConfig('terminate_request_on_malicious_input')) {
            abort(403, 'Malicious input found.');
        }

        return $next($request);
    }

    /**
     * Transform the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        if (in_array($key, $this->exceptKeys, true)) {
            return $value;
        }

        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if ($value instanceof UploadedFile) {
            if ($this->enabledInConfig('allow_file_uploads')) {
                return $value;
            }

            $this->sanitizedKeys[] = $key;

            return null;
        }

        $output = $this->security->clean((string) $value);

        if (!$this->enabledInConfig('allow_blade_echoes')) {
            $output = $this->bladeEchoCleaner->clean((string) $output);
        }

        if ($output === $value) {
            return $output;
        }

        $this->sanitizedKeys[] = $key;

        return $this->enabledInConfig('completely_replace_malicious_input') ? null : $output;
    }

    private function enabledInConfig($key): bool
    {
        return (bool) config("xss-protection.middleware.{$key}");
    }

    /**
     * Register a callback that instructs the middleware to be skipped.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function skipWhen(Closure $callback)
    {
        static::$skipCallbacks[] = $callback;
    }
}
