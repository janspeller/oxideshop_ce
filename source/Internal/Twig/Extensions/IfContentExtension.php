<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Twig\Extensions;

use OxidEsales\EshopCommunity\Internal\Adapter\TemplateLogic\IfContentLogic;
use Twig\Extension\AbstractExtension;

/**
 * Class IfContentExtension
 *
 * @author Tomasz Kowalewski (t.kowalewski@createit.pl)
 */
class IfContentExtension extends AbstractExtension
{

    /** @var IfContentLogic */
    private $ifContentLogic;

    /**
     * IfContentExtension constructor.
     *
     * @param IfContentLogic $ifContentLogic
     */
    public function __construct(IfContentLogic $ifContentLogic)
    {
        $this->ifContentLogic = $ifContentLogic;
    }

    /**
     * @param string $sIdent
     * @param string $sOxid
     *
     * @return array
     */
    public function getContent($sIdent, $sOxid)
    {
        return $this->ifContentLogic->getContent($sIdent, $sOxid);
    }
}
