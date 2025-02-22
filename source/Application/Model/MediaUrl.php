<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;

class MediaUrl extends \OxidEsales\Eshop\Core\Model\MultiLanguageModel
{
    protected $_sClassName = 'oxmediaurls';

    public function __construct()
    {
        parent::__construct();
        $this->init('oxmediaurls');
    }

    /**
     * Return HTML code depending on current URL
     *
     * @return string
     */
    public function getHtml()
    {
        $sUrl = $this->oxmediaurls__oxurl->value;
        //youtube link
        if (strpos($sUrl, 'youtube.com') || strpos($sUrl, 'youtu.be')) {
            return $this->getYoutubeHtml();
        }

        //simple link
        return $this->getHtmlLink();
    }

    /**
     * Returns simple HTML link
     *
     * @param bool $blNewPage Whether to open link in new window (adds target=_blank to link)
     *
     * @return string
     */
    public function getHtmlLink($blNewPage = true)
    {
        $sForceBlank = $blNewPage ? ' target="_blank"' : '';
        $sDesc = $this->oxmediaurls__oxdesc->value;
        $sUrl = $this->getLink();

        $sHtmlLink = "<a href=\"$sUrl\"{$sForceBlank}>$sDesc</a>";

        return $sHtmlLink;
    }

    /**
     * Returns  link
     *
     * @return string
     */
    public function getLink()
    {
        if ($this->oxmediaurls__oxisuploaded->value) {
            $sUrl = \OxidEsales\Eshop\Core\Registry::getConfig()->isSsl() ? \OxidEsales\Eshop\Core\Registry::getConfig()->getSslShopUrl() : \OxidEsales\Eshop\Core\Registry::getConfig()->getShopUrl();
            $sUrl .= 'out/media/';
            $sUrl .= basename($this->oxmediaurls__oxurl->value);
        } else {
            $sUrl = $this->oxmediaurls__oxurl->value;
        }

        return $sUrl;
    }

    /**
     * Returns  object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->oxmediaurls__oxobjectid->value;
    }

    /**
     * Deletes record and unlinks the file
     *
     * @param string $sOXID Object ID(default null)
     *
     * @return bool
     */
    public function delete($sOXID = null)
    {
        $sFilePath = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('sShopDir') . "/out/media/" .
                     basename($this->oxmediaurls__oxurl->value);

        if ($this->oxmediaurls__oxisuploaded->value) {
            if (file_exists($sFilePath)) {
                unlink($sFilePath);
            }
        }

        return parent::delete($sOXID);
    }

    /**
     * @return string
     */
    protected function getYoutubeHtml()
    {
        $url = $this->oxmediaurls__oxurl->value;
        $youTubeUrl = '';

        if (strpos($url, 'youtube.com')) {
            $youTubeUrl = str_replace('www.youtube.com/watch?v=', 'www.youtube.com/embed/', $url);
            $ampersand = $this->isFieldValueHtmlEcaped() ? '&amp;' : '&';
            $youTubeUrl = preg_replace("/$ampersand/", '?', $youTubeUrl, 1);
        }
        if (strpos($url, 'youtu.be')) {
            $youTubeUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $url);
        }

        return sprintf(
            '%s<br><iframe width="425" height="344" src="%s" frameborder="0" allowfullscreen></iframe>',
            $this->oxmediaurls__oxdesc->value,
            $youTubeUrl
        );
    }

    private function isFieldValueHtmlEcaped(): bool
    {
        return !ContainerFacade::getParameter('oxid_esales.templating.engine_autoescapes_html');
    }
}
