<?php
declare(strict_types=1);

namespace Kameleoon;

use Kameleoon\Data\Conversion;
use Kameleoon\Data\CustomData;
use Kameleoon\Types\ConversionType;
use Kameleoon\Types\CustomDataType;
use Kameleoon\Types\DataType;
use OpenFeature\interfaces\flags\EvaluationContext;

/**
 * DataConverter is used to convert a data from OpenFeature to Kameleoon and back.
 */
class DataConverter
{
    /**
     * Dictionary which contains conversion methods by keys
     */
    private static array $conversionMethods;

    private static function conversionMethods(): array
    {
        if (!isset(self::$conversionMethods)) {
            self::$conversionMethods = [
                DataType::CONVERSION => [self::class, 'makeConversion'],
                DataType::CUSTOM_DATA => [self::class, 'makeCustomData']
            ];
        }
        return self::$conversionMethods;
    }

    /**
     * The method for converting EvaluationContext data to Kameleoon SDK data types.
     */
    public static function toKameleoon(?EvaluationContext $context): array
    {
        if ($context === null) {
            return [];
        }

        $data = [];
        foreach ($context->getAttributes()->keys() as $key) {
            $value = $context->getAttributes()->get($key);
            $method = self::conversionMethods()[$key] ?? null;
            if ($method === null || $value === null) {
                continue;
            }
            $values = is_array($value) && array_keys($value) === range(0, count($value) - 1) ? $value : [$value];
            foreach ($values as $val) {
                $data[] = call_user_func($method, $val);
            }
        }
        return $data;
    }

    /**
     * Converts a value to a Conversion object.
     *
     * @param array $value The value to convert. Expected to be an array.
     * @return Conversion|null The converted Conversion object or null if the input is not an array.
     */
    private static function makeConversion(array $value)
    {
        $goalId = $value[ConversionType::GOAL_ID] ?? null;
        $revenue = $value[ConversionType::REVENUE] ?? 0.0;

        return new Conversion($goalId, (float)$revenue, false);
    }

    /**
     * Converts a value to a CustomData object.
     *
     * @param array $value The value to convert. Expected to be an array.
     * @return CustomData|null The converted CustomData object or null if the input is not an array.
     */
    private static function makeCustomData(array $value)
    {
        $index = $value[CustomDataType::INDEX] ?? null;
        $values = $value[CustomDataType::VALUES] ?? [];
        if (is_string($values)) {
            $values = [$values];
        }

        return new CustomData($index, ...$values);
    }
}
