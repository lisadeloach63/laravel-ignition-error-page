<?php

namespace Facade\Ignition\SolutionProviders;

use Facade\Ignition\Solutions\LivewireDiscoverSolution;
use Facade\IgnitionContracts\HasSolutionsForThrowable;
use Livewire\Exceptions\ComponentNotFoundException;
use Livewire\LivewireComponentsFinder;
use Throwable;

class MissingLivewireComponentSolutionProvider implements HasSolutionsForThrowable
{
    public function canSolve(Throwable $throwable): bool
    {
        if (! class_exists(ComponentNotFoundException::class)) {
            return false;
        }
        if (! class_exists(LivewireComponentsFinder::class)) {
            return false;
        }
        if (! $throwable instanceof ComponentNotFoundException) {
            return false;
        }

        return true;
    }

    public function getSolutions(Throwable $throwable): array
    {
        return [new LivewireDiscoverSolution('A livewire component was not found')];
    }
}
