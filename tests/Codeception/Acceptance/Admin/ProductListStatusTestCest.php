<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace Acceptance\Admin;

use Codeception\Attribute\Group;
use Codeception\Util\Fixtures;
use DateTime;
use OxidEsales\Codeception\Admin\Products;
use OxidEsales\EshopCommunity\Tests\Codeception\Support\AcceptanceTester;

#[Group('admin')]
final class ProductListStatusTestCest
{
    private string $productID = '1000';

    public function _before(AcceptanceTester $I): void
    {
        $I->updateConfigInDatabase('blUseTimeCheck', true, 'bool');
    }

    public function _after(AcceptanceTester $I): void
    {
        $I->updateConfigInDatabase('blUseTimeCheck', false, 'bool');

        $product = Fixtures::get('product-1000');
        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVE' => $product['OXACTIVE'],
                'OXACTIVEFROM' => $product['OXACTIVEFROM'],
                'OXACTIVETO' => $product['OXACTIVETO'],
            ],
            [
                'OXID' => $product['OXID']
            ]
        );
    }

    public function checkProductsActiveStatus(AcceptanceTester $I): void
    {
        $admin = $I->loginAdmin();
        $productList = $admin->openProducts();

        $I->wantToTest('Product active in list');


        $I->amGoingTo('Test product with active option checked');

        $productList->filterByProductNumber($this->productID);

        $I->expect('Product is active');
        $I->assertStringContainsString(
            'active',
            $I->grabAttributeFrom($productList->productStatusClass, 'class')
        );


        $I->amGoingTo('Test product temporary active in the list');

        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVE' => 0,
                'OXACTIVEFROM' => (new DateTime())->modify('-1 day')->format('Y-m-d 00:00:00'),
                'OXACTIVETO' => (new DateTime())->modify('+1 day')->format('Y-m-d 00:00:00')
            ],
            [
                'OXID' => $this->productID
            ]
        );

        $I->expect('Product is temporary active');
        $this->testTemporaryActiveProduct($I, $productList);


        $I->amGoingTo('Test product temporary active in the list with empty activeTo');

        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVE' => 0,
                'OXACTIVEFROM' => (new DateTime())->modify('-1 day')->format('Y-m-d 00:00:00'),
                'OXACTIVETO' => '0000-00-00 00:00:00'
            ],
            [
                'OXID' => $this->productID
            ]
        );

        $I->expect('Product is temporary active');
        $this->testTemporaryActiveProduct($I, $productList);


        $I->amGoingTo('Test product temporary active in the list with empty activeFrom');

        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVE' => 0,
                'OXACTIVEFROM' => '0000-00-00 00:00:00',
                'OXACTIVETO' => (new DateTime())->modify('+1 day')->format('Y-m-d 00:00:00')
            ],
            [
                'OXID' => $this->productID
            ]
        );

        $I->expect('Product is temporary active');
        $this->testTemporaryActiveProduct($I, $productList);
    }

    public function checkProductsNotActiveStatus(AcceptanceTester $I): void
    {
        $admin = $I->loginAdmin();
        $productList = $admin->openProducts();

        $I->wantToTest('Product not active in list');


        $I->amGoingTo('Test product not active in the list with empty activeFrom/activeTo');

        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVE' => 0
            ],
            [
                'OXID' => $this->productID
            ]
        );

        $productList->filterByProductNumber($this->productID);

        $I->expect('Product is not active');
        $I->assertStringNotContainsString(
            'active',
            $I->grabAttributeFrom($productList->productStatusClass, 'class')
        );


        $I->amGoingTo('Test product is temporarily not active with expired time range');

        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVEFROM' => (new DateTime())->modify('-2 day')->format('Y-m-d 00:00:00'),
                'OXACTIVETO' => (new DateTime())->modify('-1 day')->format('Y-m-d 00:00:00')
            ],
            [
                'OXID' => $this->productID
            ]
        );

        $I->expect('Product is temporary not active');
        $this->testTemporaryInactiveProduct($I, $productList);


        $I->amGoingTo('Test product temporary not active in the list with empty activeTo');

        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVEFROM' => (new DateTime())->modify('+1 day')->format('Y-m-d 00:00:00'),
                'OXACTIVETO' => '0000-00-00 00:00:00'
            ],
            [
                'OXID' => $this->productID
            ]
        );

        $I->expect('Product is temporary not active');
        $this->testTemporaryInactiveProduct($I, $productList);


        $I->amGoingTo('Test product temporary not active in the list with empty activeFrom');

        $I->updateInDatabase(
            'oxarticles',
            [
                'OXACTIVEFROM' => '0000-00-00 00:00:00',
                'OXACTIVETO' => (new DateTime())->modify('-1 day')->format('Y-m-d 00:00:00')
            ],
            [
                'OXID' => $this->productID
            ]
        );

        $I->expect('Product is temporary not active');
        $this->testTemporaryInactiveProduct($I, $productList);
    }

    private function testTemporaryActiveProduct(AcceptanceTester $I, Products $productList): void
    {
        $productList->filterByProductNumber($this->productID);

        $I->assertStringContainsString(
            'temp-active',
            $I->grabAttributeFrom($productList->productStatusClass, 'class')
        );
    }

    private function testTemporaryInactiveProduct(AcceptanceTester $I, Products $productList): void
    {
        $productList->filterByProductNumber($this->productID);

        $I->assertStringContainsString(
            'temp-inactive',
            $I->grabAttributeFrom($productList->productStatusClass, 'class')
        );
    }
}
