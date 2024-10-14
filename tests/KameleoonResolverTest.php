<?php

use Kameleoon\Exception\FeatureNotFound;
use Kameleoon\Exception\VisitorCodeInvalid;
use Kameleoon\KameleoonClient;
use Kameleoon\KameleoonResolver;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use PHPUnit\Framework\TestCase;

class KameleoonResolverTest extends TestCase
{
    public function testResolve_WithNullContext_ReturnsErrorForMissingTargetingKey()
    {
        // Arrange
        $clientMock = $this->createMock(KameleoonClient::class);
        $resolver = new KameleoonResolver($clientMock);
        $flagKey = 'testFlag';
        $defaultValue = 'defaultValue';

        // Act
        $result = $resolver->resolve($flagKey, $defaultValue, null);

        // Assert
        $this->assertEquals($defaultValue, $result->getValue());
        $this->assertEquals(ErrorCode::TARGETING_KEY_MISSING(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals('The TargetingKey is required in context and cannot be omitted.',
            $result->getError()->getResolutionErrorMessage());
        $this->assertNull($result->getVariant());
    }

    public function resolve_NoMatchVariables_ReturnsErrorForFlagNotFound_DataProvider(): array
    {
        return [
            ['on', false, [],
                "The variation 'on' has no variables"],
            ['var', true, ['key' => new stdClass()],
                "The value for provided variable key 'variableKey' isn't found in variation 'var'"]
        ];
    }

    /**
     * @dataProvider resolve_NoMatchVariables_ReturnsErrorForFlagNotFound_DataProvider
     */
    public function testResolve_NoMatchVariable_ReturnsErrorForFlagNotFound($variant, $addVariableKey, $variables, $expectedErrorMessage)
    {
        // Arrange
        $clientMock = $this->createMock(KameleoonClient::class);
        $clientMock->method('getFeatureVariationKey')->willReturn($variant);
        $clientMock->method('getFeatureVariationVariables')->willReturn($variables);

        $resolver = new KameleoonResolver($clientMock);
        $flagKey = 'testFlag';
        $defaultValue = 42;

        $values = [];
        if ($addVariableKey) {
            $values['variableKey'] = 'variableKey';
        }
        $context = new EvaluationContext('testVisitor', new Attributes($values));

        // Act
        $result = $resolver->resolve($flagKey, $defaultValue, $context);

        // Assert
        $this->assertEquals($defaultValue, $result->getValue());
        $this->assertEquals(ErrorCode::FLAG_NOT_FOUND(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals($expectedErrorMessage, $result->getError()->getResolutionErrorMessage());
        $this->assertEquals($variant, $result->getVariant());
    }

    public function resolve_MismatchType_ReturnsErrorTypeMismatch_DataProvider(): array
    {
        return [
            [true],
            ['string'],
            [10.0]
        ];
    }

    /**
     * @dataProvider resolve_MismatchType_ReturnsErrorTypeMismatch_DataProvider
     */
    public function testResolve_MismatchType_ReturnsErrorTypeMismatch($returnValue)
    {
        // Arrange
        $variant = 'on';
        $clientMock = $this->createMock(KameleoonClient::class);
        $clientMock->method('getFeatureVariationKey')->willReturn($variant);
        $clientMock->method('getFeatureVariationVariables')->willReturn(['key' => $returnValue]);

        $resolver = new KameleoonResolver($clientMock);
        $flagKey = 'testFlag';
        $defaultValue = 42;

        $context = new EvaluationContext('testVisitor');

        // Act
        $result = $resolver->resolve($flagKey, $defaultValue, $context);

        // Assert
        $this->assertEquals($defaultValue, $result->getValue());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals('The type of value received is different from the requested value.',
            $result->getError()->getResolutionErrorMessage());
        $this->assertEquals($variant, $result->getVariant());
    }

    public function kameleoonException_DataProvider(): array
    {
        return [
            [new FeatureNotFound('featureException'), ErrorCode::FLAG_NOT_FOUND()],
            [new VisitorCodeInvalid('visitorCodeInvalid'), ErrorCode::INVALID_CONTEXT()]
        ];
    }

    /**
     * @dataProvider kameleoonException_DataProvider
     */
    public function testResolve_KameleoonException_ReturnsErrorProperError($exception, $errorCode)
    {
        // Arrange
        $clientMock = $this->createMock(KameleoonClient::class);
        $clientMock->method('getFeatureVariationKey')->willThrowException($exception);

        $resolver = new KameleoonResolver($clientMock);
        $flagKey = 'testFlag';
        $defaultValue = 42;

        $context = new EvaluationContext('testVisitor');

        // Act
        $result = $resolver->resolve($flagKey, $defaultValue, $context);

        // Assert
        $this->assertEquals($defaultValue, $result->getValue());
        $this->assertEquals($errorCode, $result->getError()->getResolutionErrorCode());
        $this->assertEquals($exception->getMessage(), $result->getError()->getResolutionErrorMessage());
        $this->assertNull($result->getVariant());
    }

    public function resolve_ReturnsResultDetails_DataProvider(): array
    {
        return [
            [null, ['k' => 10], 10, 9],
            [null, ['k1' => 'str'], 'str', 'st'],
            [null, ['k2' => true], true, false],
            [null, ['k3' => 10.0], 10.0, 11.0],
            [null, ['k4' => [1, 2]], [1, 2], [1 => 1]],
            [null, ['k5' => ["1" => 1, "2" => 2]], ["1" => 1, "2" => 2], []],
            ['varKey', ['varKey' => 10.0], 10.0, 11.0]
        ];
    }

    /**
     * @dataProvider resolve_ReturnsResultDetails_DataProvider
     */
    public function testResolve_ReturnsResultDetails($variableKey, $variables, $expectedValue, $defaultValue)
    {
        // Arrange
        $variant = 'variant';
        $clientMock = $this->createMock(KameleoonClient::class);
        $clientMock->method('getFeatureVariationKey')->willReturn($variant);
        $clientMock->method('getFeatureVariationVariables')->willReturn($variables);

        $resolver = new KameleoonResolver($clientMock);
        $flagKey = 'testFlag';

        $values = [];
        if ($variableKey !== null) {
            $values['variableKey'] = $variableKey;
        }
        $context = new EvaluationContext('testVisitor', new Attributes($values));

        // Act
        $result = $resolver->resolve($flagKey, $defaultValue, $context);

        // Assert
        $this->assertEquals($expectedValue, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals($variant, $result->getVariant());
    }
}
