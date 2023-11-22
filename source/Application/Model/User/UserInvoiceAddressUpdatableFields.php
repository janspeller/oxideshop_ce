<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model\User;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Contract\AbstractUpdatableFields;

/**
 * @inheritdoc
 */
class UserInvoiceAddressUpdatableFields extends AbstractUpdatableFields
{
    /**
     * UserUpdatableFields constructor.
     */
    public function __construct()
    {
        $user = oxNew(User::class);
        $this->tableName = $user->getCoreTableName();
    }

    /**
     * Return list of fields which could be updated by shop customer.
     *
     * @return array
     */
    public function getUpdatableFields()
    {
        return [
            'OXUSERNAME',
            'OXCUSTNR',
            'OXUSTID',
            'OXCOMPANY',
            'OXFNAME',
            'OXLNAME',
            'OXSTREET',
            'OXSTREETNR',
            'OXADDINFO',
            'OXCITY',
            'OXCOUNTRYID',
            'OXSTATEID',
            'OXZIP',
            'OXFON',
            'OXFAX',
            'OXSAL',
            'OXCREATE',
            'OXREGISTER',
            'OXPRIVFON',
            'OXMOBFON',
            'OXBIRTHDATE',
            'OXURL',
            'OXUPDATEKEY',
            'OXUPDATEEXP',
            'OXTIMESTAMP'
        ];
    }
}
