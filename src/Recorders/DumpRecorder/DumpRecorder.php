<?php

namespace Spatie\LaravelIgnition\Recorders\DumpRecorder;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper as BaseHtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

class DumpRecorder
{
    /** @var array<array<int,mixed>> */
    protected array $dumps = [];

    protected Application $app;

    protected static bool $registeredHandler = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function start(): self
    {
        $multiDumpHandler = new MultiDumpHandler();

        $this->app->singleton(MultiDumpHandler::class, fn () => $multiDumpHandler);

        if (! self::$registeredHandler) {
            static::$registeredHandler = true;

            $this->ensureOriginalHandlerExists();

            $originalHandler = VarDumper::setHandler(fn($dumpedVariable) => $multiDumpHandler->dump($dumpedVariable));

            $multiDumpHandler?->addHandler($originalHandler);

            $multiDumpHandler->addHandler(fn ($var) => (new DumpHandler($this))->dump($var));
        }

        return $this;
    }

    public function record(Data $data): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        $file = (string)Arr::get($backtrace, '6.file');
        $lineNumber = (int)Arr::get($backtrace, '6.line');

        if (! Arr::exists($backtrace, '7.class') && (string)Arr::get($backtrace, '7.function') === 'ddd') {
            $file = (string)Arr::get($backtrace, '7.file');
            $lineNumber = (int)Arr::get($backtrace, '7.line');
        }

        $htmlDump = (new HtmlDumper())->dump($data);

        $this->dumps[] = new Dump($htmlDump, $file, $lineNumber);
    }

    public function getDumps(): array
    {
        return $this->toArray();
    }

    public function reset()
    {
        $this->dumps = [];
    }

    public function toArray(): array
    {
        $dumps = [];

        foreach ($this->dumps as $dump) {
            $dumps[] = $dump->toArray();
        }

        return $dumps;
    }


    /*
     * Only the `VarDumper` knows how to create the orignal HTML or CLI VarDumper.
     * Using reflection and the private VarDumper::register() method we can force it
     * to create and register a new VarDumper::$handler before we'll overwrite it.
     * Of course, we only need to do this if there isn't a registered VarDumper::$handler.
     *
     * @throws \ReflectionException
     */
    protected function ensureOriginalHandlerExists(): void
    {
        $reflectionProperty = new ReflectionProperty(VarDumper::class, 'handler');
        $reflectionProperty->setAccessible(true);
        $handler = $reflectionProperty->getValue();

        if (!$handler) {
            // No handler registered yet, so we'll force VarDumper to create one.
            $reflectionMethod = new ReflectionMethod(VarDumper::class, 'register');
            $reflectionMethod->setAccessible(true);
            $reflectionMethod->invoke(null);
        }
    }
}
