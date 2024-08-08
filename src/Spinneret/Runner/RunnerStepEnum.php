<?php

namespace Arakne\Spinneret\Runner;

/**
 * Execution step of the runner
 * Case are ordered by execution order
 */
enum RunnerStepEnum
{
    case Middleware;
    case Router;
    case Presenter;
    case View;
}
