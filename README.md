# Kameleoon OpenFeature provider for PHP

The Kameleoon OpenFeature provider for PHP allows you to connect your OpenFeature PHP implementation to Kameleoon without installing the PHP Kameleoon SDK.

> [!WARNING]
> This is a beta version. Breaking changes may be introduced before general release.

## Supported PHP versions

This version of the SDK is built for the following targets:

* PHP 8.0 and above.

## Get started

This section explains how to install, configure, and customize the Kameleoon OpenFeature provider.

### Install dependencies

First, install the required dependencies in your application.

add in composer.json

```json
{
  "require": {
    "kameleoon/openfeature-php": ">=0.0.1"
  }
}
```

```sh
composer install
```

### Usage

The following example shows how to use the Kameleoon provider with the OpenFeature SDK.

```php
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\OpenFeatureAPI;
use Kameleoon\KameleoonProvider;
use Kameleoon\KameleoonClientConfig;

$clientConfig = new KameleoonClientConfig(
    "clientId",
    "clientSecret",
);

$provider = new KameleoonProvider('siteCode', $clientConfig);
$api = OpenFeatureAPI::getInstance();
$api->setProvider($provider);
$client = $api->getClient();

$dataDictionary = [
    'variableKey' => 'stringKey'
];
$evalContext = new EvaluationContext("visitorCode", new Attributes($dataDictionary));

$evaluationDetails = $client->getStringDetails("featureKey", 5, $evalContext);

$numberOfRecommendedProducts = $evaluationDetails->getValue();
print_r("Number of recommended products: " . $numberOfRecommendedProducts);
```

#### Customize the Kameleoon provider

You can customize the Kameleoon provider by changing the `KameleoonClientConfig` object that you passed to the constructor above. For example:

```php
$clientConfig = new KameleoonClientConfig(
    clientId: "clientId",
    clientSecret: "clientSecret",
    kameleoonWorkDir: "/tmp/kameleoon/php-client/", // kameleoonWorkDir: optional / ("/tmp/kameleoon/php-client/" by default)
    refreshIntervalMinute: 60, // refreshIntervalMinute: in minutes, optional (60 minutes by default)
    defaultTimeoutMillisecond: 10_000, // defaultTimeoutMillisecond: in milliseconds, optional (10_000 ms by default)
    debugMode: false, // debugMode: optional (false by default)
    cookieOptions: $cookieOptions, // cookieOptions: optional
    environment: "development" // environment: optional ("production" by default)
);

$provider = new KameleoonProvider('siteCode', $clientConfig);
```
> [!NOTE]
> For additional configuration options, see the [Kameleoon documentation](https://developers.kameleoon.com/feature-management-and-experimentation/web-sdks/php-sdk/#example-code).

## EvaluationContext and Kameleoon Data

Kameleoon uses the concept of associating `Data` to users, while the OpenFeature SDK uses the concept of an `EvaluationContext`, which is a dictionary of string keys and values. The Kameleoon provider maps the `EvaluationContext` to the Kameleoon `Data`.

> [!NOTE]
> To get the evaluation for a specific visitor, set the `targeting_key` value for the `EvaluationContext` to the visitor code (user ID). If the value is not provided, then the `defaultValue` parameter will be returned.

```php
$values = [
  'variableKey' => 'stringKey'
];

$evalContext = new EvaluationContext("userId", new Attributes($values));
```

The Kameleoon provider provides a few predefined parameters that you can use to target a visitor from a specific audience and track each conversion. These are:

| Parameter               | Description                                                                                                                                                          |
|-------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `DataType::CUSTOM_DATA` | The parameter is used to set [`CustomData`](https://developers.kameleoon.com/feature-management-and-experimentation/web-sdks/php-sdk/#customdata) for a visitor.     |
| `DataType::CONVERSION`  | The parameter is used to track a [`Conversion`](https://developers.kameleoon.com/feature-management-and-experimentation/web-sdks/php-sdk/#conversion) for a visitor. |

### DataType::CUSTOM_DATA

Use `DataType::CUSTOM_DATA` to set [`CustomData`](https://developers.kameleoon.com/feature-management-and-experimentation/web-sdks/php-sdk/#customdata) for a visitor. The `DataType::CUSTOM_DATA` field has the following parameters:

| Parameter                | Type            | Description                                                       |
|--------------------------|-----------------|-------------------------------------------------------------------|
| `CustomDataType::INDEX`  | int             | Index or ID of the custom data to store. This field is mandatory. |
| `CustomDataType::VALUES` | string or array | Value of the custom data to store. This field is mandatory.       |

#### Example

```php
$customeDataDictionary = [
    DataType::CUSTOM_DATA => [
        CustomDataType::INDEX => 1,
        CustomDataType::VALUES => '10'
    ]
];

$evalContext = new EvaluationContext("userId", new Attributes($customeDataDictionary));
```

### DataType::CONVERSION

Use `DataType::CONVERSION` to track a [`Conversion`](https://developers.kameleoon.com/feature-management-and-experimentation/web-sdks/php-sdk/#conversion) for a visitor. The `DataType::CONVERSION` field has the following parameters:

| Parameter                 | Type  | Description                                                     |
|---------------------------|-------|-----------------------------------------------------------------|
| `ConversionType::GOAL_ID` | int   | Identifier of the goal. This field is mandatory.                |
| `ConversionType::REVENUE` | float | Revenue associated with the conversion. This field is optional. |

#### Example

```php
$conversionDictionary = [
    DataType::CONVERSION => [
        ConversionType::GOAL_ID => 1,
        ConversionType::REVENUE => 200
    ]
];

$evalContext = new EvaluationContext("userId", new Attributes($conversionDictionary));
```

### Use multiple Kameleoon Data types

You can provide many different kinds of Kameleoon data within a single `EvaluationContext` instance.

For example, the following code provides one `DataType::CONVERSION` instance and two `DataType::CUSTOM_DATA` instances.

```php
$dataDictionary = [
    DataType::CONVERSION => [
        ConversionType::GOAL_ID => 1,
        ConversionType::REVENUE => 200
    ],
    DataType::CUSTOM_DATA => [
        [
            CustomDataType::INDEX => 1,
            CustomDataType::VALUES => ['10', '30']
        ],
        [
            CustomDataType::INDEX => 2,
            CustomDataType::VALUES => '20'
        ]
    ]
];

$evalContext = new EvaluationContext("userId", $dataDictionary);
```
