<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Model;

use DateTimeImmutable;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;

final class ArticleTest extends IntegrationTestCase
{
    private static string $timeFormat = 'Y-m-d H:i:s';
    private static string $defaultTimestamp = '0000-00-00 00:00:00';

    public function setUp(): void
    {
        parent::setUp();

        Registry::getConfig()->init();
        Registry::getConfig()->setConfigParam('blUseStock', false);
    }

    public function testIsVisibleWithInactive(): void
    {
        $product = oxNew(Article::class);
        $product->oxarticles__oxactive = new Field(false);

        $this->assertFalse($product->isVisible());
    }

    public function testIsVisibleWithAlwaysActive(): void
    {
        $product = oxNew(Article::class);
        $product->oxarticles__oxactive = new Field(true);

        $this->assertTrue($product->isVisible());
    }

    public function testIsVisibleWithValidTimeRestrictionsAndDisabledConfig(): void
    {
        Registry::getConfig()->setConfigParam('blUseTimeCheck', false);
        $now = new DateTimeImmutable();
        $past = $now->modify('-1 day');
        $future = $now->modify('+1 day');
        $product = oxNew(Article::class);
        $product->oxarticles__oxactive = new Field(false);
        $product->oxarticles__oxactivefrom = new Field($past->format(self::$timeFormat));
        $product->oxarticles__oxactiveto = new Field($future->format(self::$timeFormat));

        $this->assertFalse($product->isVisible());
    }

    /**
     * @dataProvider validTimeRestrictionsDataProvider
     */
    public function testIsVisibleWithValidTimeRestrictions(string $activeFrom, string $activeTo): void
    {
        Registry::getConfig()->setConfigParam('blUseTimeCheck', true);

        $product = oxNew(Article::class);
        $product->oxarticles__oxactive = new Field(false);
        $product->oxarticles__oxactivefrom = new Field($activeFrom);
        $product->oxarticles__oxactiveto = new Field($activeTo);

        $this->assertTrue($product->isVisible());
    }

    public static function validTimeRestrictionsDataProvider(): array
    {
        $now = new DateTimeImmutable();
        $past = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        return [
            [$past->format(self::$timeFormat), $future->format(self::$timeFormat)],
            [self::$defaultTimestamp, $future->format(self::$timeFormat)],
            [$now->format(self::$timeFormat), $future->format(self::$timeFormat)]
        ];
    }

    /**
     * @dataProvider invalidTimeRestrictionsDataProvider
     */
    public function testIsVisibleWithInvalidTimeRestrictions(string $activeFrom, string $activeTo): void
    {
        Registry::getConfig()->setConfigParam('blUseTimeCheck', true);

        $product = oxNew(Article::class);
        $product->oxarticles__oxactive = new Field(false);
        $product->oxarticles__oxactivefrom = new Field($activeFrom);
        $product->oxarticles__oxactiveto = new Field($activeTo);

        $this->assertFalse($product->isVisible());
    }

    public static function invalidTimeRestrictionsDataProvider(): array
    {
        $now = new DateTimeImmutable();
        $past = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        return [
            [self::$defaultTimestamp, self::$defaultTimestamp],
            [$now->format(self::$timeFormat), self::$defaultTimestamp],
            [$future->format(self::$timeFormat), $past->format(self::$timeFormat)]
        ];
    }

    public static function isProductAlwaysActiveDataProvider(): array
    {
        return [
            'NULL value' => [null, false],
            'false value' => [false, false],
            'true value' => [true, true],
        ];
    }

    /**
     * @dataProvider isProductAlwaysActiveDataProvider
     */
    public function testIsProductAlwaysActive(?bool $active, bool $result): void
    {
        $product = oxNew(Article::class);
        $product->oxarticles__oxactive = new Field($active);

        $this->assertEquals($result, $product->isProductAlwaysActive());
    }

    public static function hasProductActiveTimeRangeDataProvider(): array
    {
        $now = new DateTimeImmutable();
        return [
            'Empty active From/To' => [self::$defaultTimestamp, self::$defaultTimestamp, false],
            'Empty active From' => [self::$defaultTimestamp, $now->format(self::$timeFormat), true],
            'Empty active To' => [$now->format(self::$timeFormat), self::$defaultTimestamp, true],
            'With active From/to' => [$now->format(self::$timeFormat), $now->format(self::$timeFormat), true],
        ];
    }

    /**
     * @dataProvider hasProductActiveTimeRangeDataProvider
     */
    public function testHasProductActiveTimeRange(string $activeFrom, string $activeTo, bool $result)
    {
        $product = oxNew(Article::class);
        $product->oxarticles__oxactivefrom = new Field($activeFrom);
        $product->oxarticles__oxactiveto = new Field($activeTo);

        $this->assertEquals($result, $product->hasProductActiveTimeRange());
    }

    public static function isProductActiveNowDataProvider(): array
    {
        $now = new DateTimeImmutable();
        $past = $now->modify('-1 day')->format(self::$timeFormat);
        $future = $now->modify('+1 day')->format(self::$timeFormat);
        return [
            'Empty active From/To' => [self::$defaultTimestamp, self::$defaultTimestamp, false],
            'Empty activeFrom valid activeTo' => [self::$defaultTimestamp, $future, true],
            'Empty activeFrom invalid activeTo' => [self::$defaultTimestamp, $past, false],
            'Empty activeTo valid activeFrom' => [$past, self::$defaultTimestamp, false],
            'Empty activeTo invalid activeFrom' => [$future, self::$defaultTimestamp, false],
            'With valid From/to' => [$past, $future, true],
            'With invalid From/to' => [$future, $past, false],
        ];
    }

    /**
     * @dataProvider isProductActiveNowDataProvider
     */
    public function testIsProductActiveNow(string $activeFrom, string $activeTo, bool $result)
    {
        $now = new DateTimeImmutable();
        $product = oxNew(Article::class);
        $product->oxarticles__oxactivefrom = new Field($activeFrom);
        $product->oxarticles__oxactiveto = new Field($activeTo);

        $this->assertEquals($result, $product->isProductActiveNow($now->format(self::$timeFormat)));
    }
}
