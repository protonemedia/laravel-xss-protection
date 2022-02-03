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
     * All of the registered skip keys callbacks.
     *
     * @var array
     */
    protected static $skipKeyCallbacks = [];

    /**
     * The attributes that should not be cleaned.
     *
     * @var array
     */
    protected $exceptKeys = [];

    /**
     * Array of sanitized keys.
     *
     * @var array
     */
    protected $sanitizedKeys = [];

    /**
     * Original request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $originalRequest;

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

        $dispatchEvent = $this->enabledInConfig('dispatch_event_on_malicious_input');

        if (count(static::$skipKeyCallbacks) > 0 || $dispatchEvent) {
            $this->originalRequest = clone $request;
        }

        $this->clean($request);

        if (count($this->sanitizedKeys) === 0) {
            return $next($request);
        }

        if ($dispatchEvent) {
            event(new MaliciousInputFound($this->sanitizedKeys, $this->originalRequest, $request));
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

        foreach (static::$skipKeyCallbacks as $callback) {
            if ($callback($key, $value, $this->originalRequest)) {
                return $value;
            }
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

    /**
     * Returns a boolean whether an option has been enabled.
     *
     * @param string $key
     * @return boolean
     */
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

    /**
     * Register a callback that instructs the middleware to be skipped.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function skipKeyWhen(Closure $callback)
    {
        static::$skipKeyCallbacks[] = $callback;
    }

    /**
     * Clear static callback arrays.
     *
     * @return void
     */
    public static function clearCallbacks()
    {
        static::$skipCallbacks = [];

        static::$skipKeyCallbacks = [];
    }
}
