<?php
declare(strict_types=1);

namespace Kameleoon;

use Kameleoon\Exception\SiteCodeIsEmpty;
use Kameleoon\Logging\KameleoonLogger;
use OpenFeature\implementation\common\Metadata;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\common\Metadata as IMetadata;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\hooks\Hook;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\Log\LoggerInterface;

/**
 * The {@link KameleoonProvider} is an OpenFeature {@link Provider} implementation
 * for the Kameleoon SDK.
 */
class KameleoonProvider implements Provider
{
    private ?KameleoonClient $client;
    private Resolver $resolver;

    /**
     * Create a new instance of the provider with the given siteCode and config.
     *
     * @param string $siteCode Code of the website you want to run experiments on. This unique code id can
     *        be found in our platform's back-office. This field is mandatory.
     * @param KameleoonClientConfig $config Configuration SDK object.
     */
    public function __construct(string $siteCode, KameleoonClientConfig $config)
    {
        $this->client = self::makeKameleoonClient($siteCode, $config);
        $this->resolver = new KameleoonResolver($this->client);
    }

    /**
     * Helper method to create a new KameleoonClient instance with error checking and conversion
     * their types from KameleoonClient SDK to OpenFeature.
     */
    private function makeKameleoonClient(string $siteCode, KameleoonClientConfig $config): ?KameleoonClient
    {
        try {
            return KameleoonClientFactory::createWithConfig($siteCode, $config);
        } catch (SiteCodeIsEmpty $ex) {
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        KameleoonLogger::setLogger(new KameleoonOpenFeatureLogger($logger));
    }

    /**
     * @return Hook[]
     */
    public function getHooks(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(): IMetadata
    {
        return new Metadata("Kameleoon Provider");
    }

    /**
     * Returns the KameleoonClient SDK instance.
     *
     * @return KameleoonClient
     */
    public function getClient(): KameleoonClient
    {
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function resolveBooleanValue(string $flagKey, bool $defaultValue,
                                        ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    /**
     * @inheritdoc
     */
    public function resolveStringValue(string $flagKey, string $defaultValue,
                                       ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    /**
     * @inheritdoc
     */
    public function resolveIntegerValue(string $flagKey, int $defaultValue,
                                        ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    /**
     * @inheritdoc
     */
    public function resolveFloatValue(string $flagKey, float $defaultValue,
                                      ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    /**
     * @inheritdoc
     */
    public function resolveObjectValue(string $flagKey, array $defaultValue,
                                       ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    private function resolve(string $flagKey, mixed $defaultValue,
                             ?EvaluationContext $context = null): ResolutionDetails
    {
        if ($this->client === null) {
            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::PROVIDER_NOT_READY(), 'The provider is not ready to resolve flags.'))
                ->withReason(Reason::ERROR)
                ->build();
        }
        return $this->resolver->resolve($flagKey, $defaultValue, $context);
    }
}
