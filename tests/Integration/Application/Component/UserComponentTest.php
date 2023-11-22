<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Component;

use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;

final class UserComponentTest extends IntegrationTestCase
{
    public function testCreateUserPrivateSales()
    {
        $rawVal = [
            'oxuser__oxfname' => 'fname',
            'oxuser__oxlname' => 'lname',
            'oxuser__oxstreetnr' => 'nr',
            'oxuser__oxstreet' => 'street',
            'oxuser__oxzip' => 'zip',
            'oxuser__oxcity' => 'city',
            'oxuser__oxcountryid' => 'a7c40f631fc920687.20179984',
            'oxuser__oxactive' => 1
        ];

        $_POST = array_merge($_POST,
            [
                'lgn_usr' => 'test@oxid-esales.com',
                'lgn_pwd' => 'Test@oxid-esales.com',
                'lgn_pwd2' => 'Test@oxid-esales.com',
                'order_remark' => 'TestRemark',
                'option' => 3,
                'invadr' => $rawVal
            ]
        );

        $this->sessionTokenIsCorrect();
        Registry::getConfig()->setConfigParam('blPsLoginEnabled', true);

        $fronendController = oxNew(FrontendController::class);
        $userComponent = oxNew(UserComponent::class);
        $userComponent->setParent($fronendController);

        $createUserReturn = $userComponent->createUser();
        $this->assertEquals('payment?new_user=1&success=1', $createUserReturn);

        $user = $this->fetchUserData('test@oxid-esales.com');

        $this->assertEquals('fname', $user['OXFNAME']);
        $this->assertEquals(0, $user['OXACTIVE']);
    }

    private function fetchUserData(string $username)
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();

        return $queryBuilder
            ->select('*')
            ->from('oxuser')
            ->where('oxusername = :oxusername')
            ->setParameters([
                'oxusername' => $username,
            ])
            ->execute()
            ->fetchAll()[0];
    }

    private function sessionTokenIsCorrect(): void
    {
        Registry::set(
            Session::class,
            $this->createConfiguredMock(
                Session::class,
                ['checkSessionChallenge' => true]
            )
        );
    }
}
