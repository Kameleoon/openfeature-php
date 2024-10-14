<?php

use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\interfaces\provider\ErrorCode;
use PHPUnit\Framework\TestCase;
use Kameleoon\KameleoonProvider;
use Kameleoon\KameleoonClient;
use Kameleoon\KameleoonClientConfig;
use Kameleoon\KameleoonResolver;

class KameleoonProviderTest extends TestCase
{
    private $clientMock;
    private $resolverMock;
    private KameleoonProvider $provider;
    private KameleoonClientConfig $config;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(KameleoonClient::class);
        $this->resolverMock = $this->createMock(KameleoonResolver::class);
        $this->config = new KameleoonClientConfig(
            "clientId",
            "clientSecret",
        );
        $this->provider = new KameleoonProvider('siteCode', $this->config);
        $reflection = new ReflectionClass($this->provider);
        $property = $reflection->getProperty("client");
        $property->setAccessible(true);
        $property->setValue($this->provider, $this->clientMock);
        $property = $reflection->getProperty("resolver");
        $property->setAccessible(true);
        $property->setValue($this->provider, $this->resolverMock);
    }

    public function testMetadata()
    {
        $metadata = $this->provider->getMetadata();
        $this->assertEquals('Kameleoon Provider', $metadata->getName());
    }

    public function testCreateProviderWithError()
    {
        $defaultValue = false;
        $expectedErrorCode = ErrorCode::PROVIDER_NOT_READY();
        $expectedErrorMessage = 'The provider is not ready to resolve flags.';
        $provider = new KameleoonProvider('', $this->config);

        $result = $provider->resolveBooleanValue('flagKey', $defaultValue);

        $this->assertEquals($defaultValue, $result->getValue());
        $this->assertEquals($expectedErrorCode, $result->getError()->getResolutionErrorCode());
        $this->assertEquals($expectedErrorMessage, $result->getError()->getResolutionErrorMessage());
    }

    public function testResolveBooleanValueReturnsCorrectValue()
    {
        $defaultValue = false;
        $expectedValue = true;
        $this->setupMockResolver($defaultValue, $expectedValue);

        $result = $this->provider->resolveBooleanValue('flagKey', $defaultValue);

        $this->assertEquals($expectedValue, $result->getValue());
    }

    public function testResolveFloatValueReturnsCorrectValue()
    {
        $defaultValue = 0.5;
        $expectedValue = 2.5;
        $this->setupMockResolver($defaultValue, $expectedValue);

        $result = $this->provider->resolveFloatValue('flagKey', $defaultValue);
        $this->assertEquals($expectedValue, $result->getValue());
    }

    public function testResolveIntegerValueReturnsCorrectValue()
    {
        $defaultValue = 1;
        $expectedValue = 2;
        $this->setupMockResolver($defaultValue, $expectedValue);

        $result = $this->provider->resolveIntegerValue('flagKey', $defaultValue);
        $this->assertEquals($expectedValue, $result->getValue());
    }

    public function testResolveStringValueReturnsCorrectValue()
    {
        $defaultValue = '1';
        $expectedValue = '2';
        $this->setupMockResolver($defaultValue, $expectedValue);

        $result = $this->provider->resolveStringValue('flagKey', $defaultValue);
        $this->assertEquals($expectedValue, $result->getValue());
    }

    public function testResolveStructureValueReturnsCorrectValue()
    {
        $defaultValue = ['k' => 10];
        $expectedValue = ['k1' => 20];
        $this->setupMockResolver($defaultValue, $expectedValue);

        $result = $this->provider->resolveObjectValue('flagKey', $defaultValue);
        $this->assertEquals($expectedValue, $result->getValue());
    }

    // TODO: Uncomment after adding Shutdown logic in OpenFeature
//    public function testShutdownForgetSiteCode()
//    {
//        $siteCode = 'testSiteCode';
//        $config = new KameleoonClientConfig('clientId', 'clientSecret');
//        $expectedErrorCode = ErrorCode::PROVIDER_NOT_READY();
//        $defaultValue = false;
//
//        $provider = new KameleoonProvider($siteCode, $config);
//        $clientFirst = $provider->getClient();
//        $clientToCheck = KameleoonClientFactory::create($siteCode, $config);
//
//        $provider->shutdown();
//        $result = $provider->resolveBooleanValue('flagKey', $defaultValue);
//
//        $providerSecond = new KameleoonProvider($siteCode, $config);
//        $clientSecond = $providerSecond->getClient();
//
//        $this->assertSame($clientToCheck, $clientFirst);
//        $this->assertNotSame($clientFirst, $clientSecond);
//        $this->assertEquals($expectedErrorCode, $result->getError()->getResolutionErrorCode());
//        $this->assertEquals($defaultValue, $result->getValue());
//    }

    private function setupMockResolver($defaultValue, $expectedValue)
    {
        $result = (new ResolutionDetailsBuilder())
            ->withValue($expectedValue)
            ->build();
        $this->resolverMock->method('resolve')
            ->with('flagKey', $defaultValue, null)
            ->willReturn($result);
    }
}
