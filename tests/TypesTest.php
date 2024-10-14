<?php

use Kameleoon\Types\ConversionType;
use Kameleoon\Types\CustomDataType;
use Kameleoon\Types\DataType;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testCheckTypeValues_ProperValues()
    {
        // Assert
        $this->assertEquals('conversion', DataType::CONVERSION);
        $this->assertEquals('customData', DataType::CUSTOM_DATA);

        $this->assertEquals('index', CustomDataType::INDEX);
        $this->assertEquals('values', CustomDataType::VALUES);

        $this->assertEquals('goalId', ConversionType::GOAL_ID);
        $this->assertEquals('revenue', ConversionType::REVENUE);
    }
}
