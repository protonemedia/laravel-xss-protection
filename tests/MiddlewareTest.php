<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use ProtoneMedia\LaravelXssProtection\Events\MaliciousInputFound;
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    XssCleanInput::clearCallbacks();
});

it('can partly replace the malicious input', function () {
    $request = Request::createFromGlobals()->merge([
        'key' => 'test<script>script</script>',
    ]);

    config(['xss-protection.middleware.completely_replace_malicious_input' => false]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBe('test');
});

it('can dispatch an event with the transformed keys and request', function () {
    $request = Request::createFromGlobals()->merge([
        'key' => 'test<script>script</script>',
    ]);

    config(['xss-protection.middleware.dispatch_event_on_malicious_input' => true]);

    Event::fake();

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    Event::assertDispatched(function (MaliciousInputFound $event) use ($request) {
        return $event->sanitizedRequest === $request
            && $event->originalRequest->input('key') === 'test<script>script</script>'
            && $event->sanitizedKeys === ['key'];
    });
});

it('can add a callback to skip a request', function () {
    XssCleanInput::skipWhen(fn () => true);

    $request = Request::createFromGlobals()->merge([
        'key' => 'test<script>script</script>',
    ]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBe('test<script>script</script>');
});

it('doesnt interfere with booleans, numbers and null values', function () {
    $request = Request::createFromGlobals()->merge([
        'yes' => true,
        'no' => false,
        'one' => 1,
        'pi' => 3.14,
        'null' => null,
    ]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('yes'))->toBeTrue();
    expect($request->input('no'))->toBeFalse();
    expect($request->input('one'))->toBe(1);
    expect($request->input('pi'))->toBe(3.14);
    expect($request->input('null'))->toBeNull();
});

it('can completely replace the malicious input', function () {
    $request = Request::createFromGlobals()->merge([
        'key' => 'test<script>script</script>',
    ]);

    config(['xss-protection.middleware.completely_replace_malicious_input' => true]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBeNull();
});

it('can terminate the request on malicious input', function () {
    $request = Request::createFromGlobals()->merge([
        'key' => 'test<script>script</script>',
    ]);

    config(['xss-protection.middleware.terminate_request_on_malicious_input' => true]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);
})->throws(HttpException::class);

it('can disallow file uploads', function () {
    $request = Request::createFromGlobals()->merge([
        'key' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    config(['xss-protection.middleware.allow_file_uploads' => false]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBeNull();
});

it('can allow file uploads', function () {
    $request = Request::createFromGlobals()->merge([
        'key' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    config(['xss-protection.middleware.allow_file_uploads' => true]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBeInstanceOf(UploadedFile::class);
});

it('can skip a key', function () {
    class ExceptXssCleanInput extends XssCleanInput
    {
        protected $exceptKeys = [
            'allow',
            'nested.allowed',
        ];
    }

    $request = Request::createFromGlobals()->merge([
        'key' => 'test<script>script</script>',
        'allow' => 'test<script>script</script>',

        'nested' => [
            'key' => 'test<script>script</script>',
            'allowed' => 'test<script>script</script>',
        ],
    ]);

    /** @var ExceptXssCleanInput $middleware */
    $middleware = app(ExceptXssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBeNull();
    expect($request->input('nested.key'))->toBeNull();

    expect($request->input('allow'))->toBe('test<script>script</script>');
    expect($request->input('nested.allowed'))->toBe('test<script>script</script>');
});

it('can trim blade echoes', function () {
    $request = Request::createFromGlobals()->merge([
        'key' => 'test',
        'a' => '{{ $test }}',
        'b' => '{!! $test !!}',
        'c' => '{{{ $test }}}',
        'd' => 'd{{ $test }}',
        'e' => 'e{!! $test !!}',
        'f' => 'f{{{ $test }}}',
    ]);

    config(['xss-protection.middleware.completely_replace_malicious_input' => false]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBe('test');
    expect($request->input('a'))->toBe('');
    expect($request->input('b'))->toBe('');
    expect($request->input('c'))->toBe('');
    expect($request->input('d'))->toBe('d');
    expect($request->input('e'))->toBe('e');
    expect($request->input('f'))->toBe('f');
});

it('can skip a key by a callback', function () {
    XssCleanInput::skipKeyWhen(function (string $key, $value, $request) {
        expect($request)->toBeInstanceOf(Request::class);
        expect($value)->toBe('test<script>script</script>');

        return in_array($key, ['allow', 'nested.allowed']);
    });

    $request = Request::createFromGlobals()->merge([
        'key' => 'test<script>script</script>',
        'allow' => 'test<script>script</script>',

        'nested' => [
            'key' => 'test<script>script</script>',
            'allowed' => 'test<script>script</script>',
        ],
    ]);

    /** @var XssCleanInput $middleware */
    $middleware = app(XssCleanInput::class);
    $middleware->handle($request, fn ($request) => $request);

    expect($request->input('key'))->toBeNull();
    expect($request->input('nested.key'))->toBeNull();

    expect($request->input('allow'))->toBe('test<script>script</script>');
    expect($request->input('nested.allowed'))->toBe('test<script>script</script>');
});
