<?php

namespace App\Services;

use Closure;
use Illuminate\Auth\Access\Gate as BaseGate;
use Illuminate\Container\Container;

/**
 * Extended Gate with getPolicy() alias for backwards compatibility.
 */
class CustomGate extends BaseGate
{
    public function __construct(Container $container, Closure $userResolver, array $abilities = [], array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [], ?callable $guessPolicyNamesUsingCallback = null)
    {
        parent::__construct($container, $userResolver, $abilities, $policies, $beforeCallbacks, $afterCallbacks, $guessPolicyNamesUsingCallback);
    }

    /**
     * Get the policy for a given model class.
     */
    public function getPolicy($class)
    {
        return $this->getPolicyFor($class);
    }
}
