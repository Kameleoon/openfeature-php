<?php
declare(strict_types=1);

use Kameleoon\Data\Conversion;
use Kameleoon\Data\CustomData;
use Kameleoon\DataConverter;
use Kameleoon\Types\ConversionType;
use Kameleoon\Types\CustomDataType;
use Kameleoon\Types\DataType;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use PHPUnit\Framework\TestCase;

class DataConverterTest extends TestCase
{
    private static string $visitorCode = "visitorCode";

    public function testToKameleoonNullContextReturnsEmpty(): void
    {
        $context = null;
        $result = DataConverter::toKameleoon($context);
        $this->assertEmpty($result);
    }


    public function provideTestData_testToKameleoonWithConversionDataReturnsConversionData(): array
    {
        return [
            ['add_revenue' => true],
            ['add_revenue' => false]
        ];
    }

    /**
     * @dataProvider provideTestData_testToKameleoonWithConversionDataReturnsConversionData
     */
    public function testToKameleoonWithConversionDataReturnsConversionData(bool $addRevenue): void
    {
        $randGoalId = rand(1, 1000);
        $randRevenue = rand() / getrandmax() * 1000;

        $conversionData = [ConversionType::GOAL_ID => $randGoalId];
        if ($addRevenue) {
            $conversionData[ConversionType::REVENUE] = $randRevenue;
        }

        $context = new Attributes([DataType::CONVERSION => $conversionData]);
        $evalContext = new EvaluationContext(self::$visitorCode, $context);
        $result = DataConverter::toKameleoon($evalContext);

        $this->assertCount(1, $result);
        $conversion = $result[0];
        $this->assertInstanceOf(Conversion::class, $conversion);
        $this->assertEquals($randGoalId, $conversion->getGoalId());

        // TODO: Uncomment after adding conversion.getRevenue() in SDK
//        if ($tt['add_revenue']) {
//            $this->assertEquals($randRevenue, $conversion->revenue);
//        }
    }

    public function provideTestData_testToKameleoonWithCustomDataReturnsCustomData(): array
    {
        return [
            ['expected_index' => rand(1, 1000), 'expected_values' => []],
            ['expected_index' => rand(1, 1000), 'expected_values' => ['v1']],
            ['expected_index' => rand(1, 1000), 'expected_values' => ['v1', 'v2', 'v3']]
        ];
    }

    /**
     * @dataProvider provideTestData_testToKameleoonWithCustomDataReturnsCustomData
     */
    public function testToKameleoonWithCustomDataReturnsCustomData(int $expectedIndex, array $expectedValues): void
    {
        $customData = [
            CustomDataType::INDEX => $expectedIndex,
            CustomDataType::VALUES => $expectedValues
        ];

        $context = [DataType::CUSTOM_DATA => $customData];
        $evalContext = new EvaluationContext(self::$visitorCode, new Attributes($context));

        $result = DataConverter::toKameleoon($evalContext);

        $this->assertCount(1, $result);
        $customDataObj = $result[0];
        $this->assertInstanceOf(CustomData::class, $customDataObj);
        $this->assertEquals($expectedIndex, $customDataObj->getId());
        $this->assertEquals($expectedValues, $customDataObj->getValues());
    }

    public function testToKameleoonDataAllTypesReturnsAllData(): void
    {
        $goalId1 = rand(1, 1000);
        $goalId2 = rand(1, 1000);
        $index1 = rand(1, 1000);
        $index2 = rand(1, 1000);

        $contextData = [
            DataType::CONVERSION => [
                [ConversionType::GOAL_ID => $goalId1],
                [ConversionType::GOAL_ID => $goalId2]
            ],
            DataType::CUSTOM_DATA => [
                [CustomDataType::INDEX => $index1],
                [CustomDataType::INDEX => $index2]
            ]
        ];

        $evalContext = new EvaluationContext(self::$visitorCode, new Attributes($contextData));

        $result = DataConverter::toKameleoon($evalContext);

        $conversions = array_values(array_filter($result, fn($item) => $item instanceof Conversion));
        $customData = array_values(array_filter($result, fn($item) => $item instanceof CustomData));

        $this->assertCount(4, $result);
        $this->assertEquals($goalId1, $conversions[0]->getGoalId());
        $this->assertEquals($goalId2, $conversions[1]->getGoalId());
        $this->assertEquals($index1, $customData[0]->getId());
        $this->assertEquals($index2, $customData[1]->getId());
    }
}
