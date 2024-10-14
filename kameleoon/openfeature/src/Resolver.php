<?php
declare(strict_types=1);

namespace Kameleoon;

use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ResolutionDetails;

/**
 * Resolver interface which contains method for evaluations based on provided data
 */
interface Resolver
{
    /**
     * Resolve the flag based on the provided key, default value, and evaluation context.
     *
     * @param string $flagKey
     * @param mixed $defaultValue
     * @param ?EvaluationContext $context
     * @return ResolutionDetails
     */
    public function resolve(string $flagKey, mixed $defaultValue, ?EvaluationContext $context): ResolutionDetails;
}
