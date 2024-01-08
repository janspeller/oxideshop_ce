<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData\Converter;

use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Converter\ModuleSettingsBooleanConverter;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProvider;
use PHPUnit\Framework\TestCase;

class ModuleSettingsBooleanConverterTest extends TestCase
{
    public static function convertToTrueDataProvider(): array
    {
        return [
            ['true'],
            ['True'],
            ['1'],
            [1],
            [true],
        ];
    }

    /**
     * @dataProvider convertToTrueDataProvider
     */
    public function testConvertToTrue($value): void
    {
        $metaData =
            [
                MetaDataProvider::METADATA_SETTINGS => [
                    [
                        'type' => 'bool', 'value' => $value
                    ],
                ]
            ];
        $converter = new ModuleSettingsBooleanConverter();

        $convertedSettings = $converter->convert($metaData);
        $this->assertTrue($convertedSettings[MetaDataProvider::METADATA_SETTINGS][0]['value']);
    }

    public static function convertToFalseDataProvider(): array
    {
        return [
            ['false'],
            ['False'],
            ['0'],
            [0],
            [false],
        ];
    }

    /**
     * @dataProvider convertToFalseDataProvider
     */
    public function testConvertToFalse($value): void
    {
        $metaData =
            [
                MetaDataProvider::METADATA_SETTINGS => [
                    [
                        'type' => 'bool', 'value' => $value
                    ],
                ]
            ];
        $converter = new ModuleSettingsBooleanConverter();

        $convertedSettings = $converter->convert($metaData);
        $this->assertFalse($convertedSettings[MetaDataProvider::METADATA_SETTINGS][0]['value']);
    }

    public static function whenNothingToConvertDataProvider(): array
    {
        return [
            [[]],
            [
                [
                    MetaDataProvider::METADATA_SETTINGS => [
                        [
                            'type' => 'str', 'value' => 'any'
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider whenNothingToConvertDataProvider
     */
    public function testWhenNothingToConvert(array $metaData): void
    {
        $converter = new ModuleSettingsBooleanConverter();

        $convertedSettings = $converter->convert($metaData);
        $this->assertSame($metaData, $convertedSettings);
    }
}
