<?php
declare(strict_types=1);

namespace Kameleoon;

use Error;
use Exception;
use Kameleoon\Exception\FeatureException;
use Kameleoon\Exception\VisitorCodeInvalid;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;

/**
 * KameleoonResolver makes evaluations based on provided data, conforms to Resolver interface
 */
class KameleoonResolver implements Resolver
{
    private const VARIABLE_KEY = 'variableKey';
    private ?KameleoonClient $client;

    public function __construct(?KameleoonClient $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function resolve(string $flagKey, mixed $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        $variant = null;
        try {
            // Get visitor code
            $visitorCode = $context?->getTargetingKey();
            if (empty($visitorCode)) {
                return $this->makeResolutionDetailsError($defaultValue, $variant,
                    ErrorCode::TARGETING_KEY_MISSING(),
                    'The TargetingKey is required in context and cannot be omitted.');
            }

            // Add targeting data from context to KameleoonClient by visitor code
            $this->client->addData($visitorCode, ...DataConverter::toKameleoon($context));

            // Get a variant
            $variant = $this->client->getFeatureVariationKey($visitorCode, $flagKey);

            // Get the all variables for the variant
            $variables = $this->client->getFeatureVariationVariables($flagKey, $variant);

            // Get variableKey if it's provided in context or any first in variation.
            // It's the responsibility of the client to have only one variable per variation if
            // variableKey is not provided.
            $variableKey = $this->getVariableKey($context, $variables);

            // Try to get value by variable key
            $value = $variables[$variableKey] ?? null;

            if ($variableKey === null || $value === null) {
                return $this->makeResolutionDetailsError($defaultValue, $variant,
                    ErrorCode::FLAG_NOT_FOUND(),
                    $this->makeErrorDescription($variant, $variableKey));
            }

            // Check if the variable value has a required type
            if (!is_array($defaultValue) && gettype($value) !== gettype($defaultValue)) {
                return $this->makeResolutionDetailsError($defaultValue, $variant,
                    ErrorCode::TYPE_MISMATCH(),
                    'The type of value received is different from the requested value.');
            }

            return $this->makeResolutionDetails($value, $variant);
        } catch (FeatureException $exception) {
            return $this->makeResolutionDetailsError($defaultValue, $variant, ErrorCode::FLAG_NOT_FOUND(),
                $exception->getMessage());
        } catch (VisitorCodeInvalid $exception) {
            return $this->makeResolutionDetailsError($defaultValue, $variant, ErrorCode::INVALID_CONTEXT(),
                $exception->getMessage());
        } catch (Error $exception) {
            return $this->makeResolutionDetailsError($defaultValue, $variant, ErrorCode::INVALID_CONTEXT(),
                $exception->getMessage());
        } catch (Exception $exception) {
            return $this->makeResolutionDetailsError($defaultValue, $variant, ErrorCode::GENERAL(),
                $exception->getMessage());
        }
    }

    /**
     * Helper method to get the variable key from the context or variables map.
     */
    private function getVariableKey(EvaluationContext $context, array $variables): ?string
    {
        return $context->getAttributes()->get(self::VARIABLE_KEY) ?? array_key_first($variables);
    }

    /**
     * Helper method to create a ResolutionDetails object.
     */
    private function makeResolutionDetails($value, ?string $variant): ResolutionDetails
    {
        return (new ResolutionDetailsBuilder())
            ->withValue($value)
            ->withVariant($variant)
            ->build();
    }

    private function makeResolutionDetailsError(mixed $value, ?string $variant,
                                                ErrorCode $errorCode, string $errorMessage): ResolutionDetails
    {
        $builder = (new ResolutionDetailsBuilder())
            ->withValue($value)
            ->withError(new ResolutionError($errorCode, $errorMessage))
            ->withReason(Reason::ERROR);

        if ($variant !== null) {
            $builder->withVariant($variant);
        }

        return $builder->build();
    }

    private function makeErrorDescription(?string $variant, ?string $variableKey): string
    {
        return (empty($variableKey))
            ? sprintf("The variation '%s' has no variables", $variant)
            : sprintf("The value for provided variable key '%s' isn't found in variation '%s'",
                $variableKey, $variant);
    }
}
