<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\Edition\EditionSelector;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Rss feed manager
 * loads needed rss data
 * @deprecated will be removed in v7.0
 */
class RssFeed extends \OxidEsales\Eshop\Core\Base
{
    /**
     * timeout in seconds for regenerating data (3h)
     */
    const CACHE_TTL = 10800;

    /**
     * Rss data Ids for cache
     */
    const RSS_TOPSHOP = 'RSS_TopShop';
    const RSS_NEWARTS = 'RSS_NewArts';
    const RSS_CATARTS = 'RSS_CatArts';
    const RSS_ARTRECOMMLISTS = 'RSS_ARTRECOMMLISTS';
    const RSS_RECOMMLISTARTS = 'RSS_RECOMMLISTARTS';
    const RSS_BARGAIN = 'RSS_Bargain';

    /**
     * _aChannel channel data to be passed to view
     *
     * @var array
     * @access protected
     */
    protected $_aChannel = [];

    /**
     * Give back the cache file name for the given oxActionId.
     *
     * @param string $sOxActionId The oxaction we want the cache file name for.
     *
     * @return string The name of the corresponding file cache file.
     */
    public function mapOxActionToFileCache($sOxActionId)
    {
        $aOxActionToCacheIds = [
            'oxbargain' => 'RSS_BARGAIN',
            'oxtop5' => 'RSS_TopShop',
            'oxnewest' => 'RSS_NewArts'
        ];

        $sFileCacheName = $aOxActionToCacheIds[$sOxActionId];

        if (is_null($sFileCacheName)) {
            $sFileCacheName = '';
        }

        return $sFileCacheName;
    }

    /**
     * getChannel retrieve channel data
     *
     * @access public
     * @return array
     */
    public function getChannel()
    {
        return $this->_aChannel;
    }

    /**
     * Expire/remove the cache file for the given action rss feed.
     *
     * @param string $sName The name of the stream we want to remove from the file cache.
     */
    public function removeCacheFile($sName)
    {
        $sFileKey = $this->mapOxActionToFileCache($sName);
        $sFilePath = Registry::getUtils()->getCacheFilePath($this->_getCacheId($sFileKey));

        $this->_deleteFile($sFilePath);
    }

    /**
     * _loadBaseChannel loads basic channel data
     *
     * @access protected
     */
    protected function _loadBaseChannel() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oShop = $this->getConfig()->getActiveShop();
        $this->_aChannel['title'] = $oShop->oxshops__oxname->value;
        $this->_aChannel['link'] = Registry::getUtilsUrl()->prepareUrlForNoSession($this->getConfig()->getShopUrl());
        $this->_aChannel['description'] = '';
        $oLang = Registry::getLang();
        $aLangIds = $oLang->getLanguageIds();
        $this->_aChannel['language'] = $aLangIds[$oLang->getBaseLanguage()];
        $this->_aChannel['copyright'] = $oShop->oxshops__oxname->value;
        $this->_aChannel['selflink'] = '';
        if (oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($oShop->oxshops__oxinfoemail->value)) {
            $this->_aChannel['managingEditor'] = $oShop->oxshops__oxinfoemail->value;
            if ($oShop->oxshops__oxfname) {
                $this->_aChannel['managingEditor'] .= " ({$oShop->oxshops__oxfname} {$oShop->oxshops__oxlname})";
            }
        }

        $this->_aChannel['generator'] = $oShop->oxshops__oxname->value;

        $editionSelector = new EditionSelector();
        $this->_aChannel['image']['url'] = $this->getConfig()->getImageUrl()
            . 'logo_' . strtolower($editionSelector->getEdition()) . '.png';

        $this->_aChannel['image']['title'] = $this->_aChannel['title'];
        $this->_aChannel['image']['link'] = $this->_aChannel['link'];
    }

    /**
     * _getCacheId retrieve cache id
     *
     * @param string $name cache name
     *
     * @access protected
     * @return string
     */
    protected function _getCacheId($name) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oConfig = $this->getConfig();

        return $name . '_' . $oConfig->getShopId() . '_' . Registry::getLang()->getBaseLanguage() . '_' . (int) $oConfig->getShopCurrency();
    }

    /**
     * _loadFromCache load data from cache, requires Rss data Id
     *
     * @param string $name Rss data Id
     *
     * @access protected
     * @return array
     */
    protected function _loadFromCache($name) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($aRes = Registry::getUtils()->fromFileCache($this->_getCacheId($name))) {
            if ($aRes['timestamp'] > time() - self::CACHE_TTL) {
                return $aRes['content'];
            }
        }

        return false;
    }


    /**
     * _getLastBuildDate check if changed data and renew last build date if needed
     * returns result as string
     *
     * @param string $name  Rss data Id
     * @param array  $aData channel data
     *
     * @access protected
     * @return string
     */
    protected function _getLastBuildDate($name, $aData) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($aData2 = Registry::getUtils()->fromFileCache($this->_getCacheId($name))) {
            $sLastBuildDate = $aData2['content']['lastBuildDate'];
            $aData2['content']['lastBuildDate'] = '';
            $aData['lastBuildDate'] = '';
            if (!strcmp(serialize($aData), serialize($aData2['content']))) {
                return $sLastBuildDate;
            }
        }

        return date('D, d M Y H:i:s O');
    }

    /**
     * _saveToCache writes generated rss data to cache
     * returns true on successfull write, false otherwise
     * A successfull write means only write ok AND data has actually changed
     * if give
     *
     * @param string $name     cache name
     * @param array  $aContent data to be saved
     *
     * @access protected
     * @return void
     */
    protected function _saveToCache($name, $aContent) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aData = ['timestamp' => time(), 'content' => $aContent];

        return Registry::getUtils()->toFileCache($this->_getCacheId($name), $aData);
    }


    /**
     * _getArticleItems create channel items from article list
     *
     * @param \OxidEsales\Eshop\Application\Model\ArticleList $oList article list
     *
     * @access protected
     * @return array
     */
    protected function _getArticleItems(\OxidEsales\Eshop\Application\Model\ArticleList $oList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsUrl = Registry::getUtilsUrl();
        $aItems = [];
        $oLang = Registry::getLang();
        $oStr = getStr();

        foreach ($oList as $oArticle) {
            $oItem = new stdClass();
            $oActCur = $this->getConfig()->getActShopCurrencyObject();
            $sPrice = '';

            // check if article is a variant
            if ($oArticle->isParentNotBuyable()) {
                $oPrice = $oArticle->getVarMinPrice();
            } else {
                $oPrice = $oArticle->getPrice();
            }

            if ($oPrice) {
                $sFrom = ($oArticle->isRangePrice()) ? Registry::getLang()->translateString('PRICE_FROM') . ' ' : '';
                $sPrice .= ' ' . $sFrom . $oLang->formatCurrency($oPrice->getBruttoPrice(), $oActCur) . ' ' . $oActCur->sign;
            }

            $oItem->title = strip_tags($oArticle->oxarticles__oxtitle->value . $sPrice);
            $oItem->guid = $oItem->link = $myUtilsUrl->prepareUrlForNoSession($oArticle->getLink());
            $oItem->isGuidPermalink = true;
            // $oItem->description             = $oArticle->getLongDescription()->value; //oxarticles__oxshortdesc->value;
            //#4038: Smarty not parsed in RSS, although smarty parsing activated for longdescriptions
            if (Registry::getConfig()->getConfigParam('bl_perfParseLongDescinSmarty')) {
                $oItem->description = $oArticle->getLongDesc();
            } else {
                $oItem->description = $oArticle->getLongDescription()->value;
            }

            if (trim(str_replace('&nbsp;', '', (strip_tags($oItem->description)))) == '') {
                $oItem->description = $oArticle->oxarticles__oxshortdesc->value;
            }

            $oItem->description = trim($oItem->description);
            if ($sThumb = $oArticle->getThumbnailUrl()) {
                $oItem->description = "<img src='$sThumb' border=0 align='left' hspace=5>" . $oItem->description;
            }
            $oItem->description = $oStr->htmlspecialchars($oItem->description);

            if ($oArticle->oxarticles__oxtimestamp->value) {
                list($date, $time) = explode(' ', $oArticle->oxarticles__oxtimestamp->value);
                $date = explode('-', $date);
                $time = explode(':', $time);
                $oItem->date = date('D, d M Y H:i:s O', mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]));
            } else {
                $oItem->date = date('D, d M Y H:i:s O', time());
            }

            $aItems[] = $oItem;
        }

        return $aItems;
    }

    /**
     * _prepareUrl make url from uri
     *
     * @param string $sUri   standard uri
     * @param string $sTitle page title
     *
     * @access protected
     *
     * @return string
     */
    protected function _prepareUrl($sUri, $sTitle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iLang = Registry::getLang()->getBaseLanguage();
        $sUrl = $this->_getShopUrl();
        $sUrl .= $sUri . '&amp;lang=' . $iLang;

        if (Registry::getUtils()->seoIsActive()) {
            $oEncoder = Registry::getSeoEncoder();
            $sUrl = $oEncoder->getDynamicUrl($sUrl, "rss/{$sTitle}/", $iLang);
        }

        return Registry::getUtilsUrl()->prepareUrlForNoSession($sUrl);
    }

    /**
     * _prepareFeedName adds shop name to feed title
     *
     * @param string $sTitle page title
     *
     * @access protected
     *
     * @return string
     */
    protected function _prepareFeedName($sTitle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oShop = $this->getConfig()->getActiveShop();

        return $oShop->oxshops__oxname->value . "/" . $sTitle;
    }

    /**
     * _getShopUrl returns shop home url
     *
     * @access protected
     * @return string
     */
    protected function _getShopUrl() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sUrl = $this->getConfig()->getShopUrl();
        $oStr = getStr();
        if ($oStr->strpos($sUrl, '?') !== false) {
            if (!$oStr->preg_match('/[?&](amp;)?$/i', $sUrl)) {
                $sUrl .= '&amp;';
            }
        } else {
            $sUrl .= '?';
        }

        return $sUrl;
    }

    /**
     * _loadData loads given data to channel
     *
     * @param string $sTag       tag
     * @param string $sTitle     object title
     * @param string $sDesc      object description
     * @param array  $aItems     items data to be put to rss
     * @param string $sRssUrl    url of rss page
     * @param string $sTargetUrl url of page rss represents
     *
     * @access protected
     */
    protected function _loadData($sTag, $sTitle, $sDesc, $aItems, $sRssUrl, $sTargetUrl = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_loadBaseChannel();

        $this->_aChannel['selflink'] = $sRssUrl;

        if ($sTargetUrl) {
            $this->_aChannel['link'] = $this->_aChannel['image']['link'] = $sTargetUrl;
        }

        $this->_aChannel['image']['title'] = $this->_aChannel['title'] = $sTitle;
        $this->_aChannel['image']['description'] = $this->_aChannel['description'] = $sDesc;

        $this->_aChannel['items'] = $aItems;

        if ($sTag) {
            $this->_aChannel['lastBuildDate'] = $this->_getLastBuildDate($sTag, $this->_aChannel);
            $this->_saveToCache($sTag, $this->_aChannel);
        } else {
            $this->_aChannel['lastBuildDate'] = date('D, d M Y H:i:s O', Registry::getUtilsDate()->getTime());
        }
    }

    /**
     * getTopShopTitle get title for 'Top of the Shop' rss feed
     *
     * @access public
     *
     * @return string
     */
    public function getTopInShopTitle()
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();

        return $this->_prepareFeedName($oLang->translateString('TOP_OF_THE_SHOP', $iLang));
    }

    /**
     * getTopShopUrl get url for 'Top of the Shop' rss feed
     *
     * @access public
     *
     * @return string
     */
    public function getTopInShopUrl()
    {
        return $this->_prepareUrl("cl=rss&amp;fnc=topshop", $this->getTopInShopTitle());
    }

    /**
     * loadTopShop loads 'Top of the Shop' rss data
     *
     * @access public
     *
     * @return void
     */
    public function loadTopInShop()
    {
        if (($this->_aChannel = $this->_loadFromCache(self::RSS_TOPSHOP))) {
            return;
        }

        $oArtList = oxNew(\OxidEsales\Eshop\Application\Model\ArticleList::class);
        $oArtList->loadTop5Articles($this->getConfig()->getConfigParam('iRssItemsCount'));

        $oLang = Registry::getLang();
        $this->_loadData(
            self::RSS_TOPSHOP,
            $this->getTopInShopTitle(),
            $oLang->translateString('TOP_SHOP_PRODUCTS', $oLang->getBaseLanguage()),
            $this->_getArticleItems($oArtList),
            $this->getTopInShopUrl()
        );
    }


    /**
     * get title for 'Newest Shop Articles' rss feed
     *
     * @access public
     *
     * @return string
     */
    public function getNewestArticlesTitle()
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();

        return $this->_prepareFeedName($oLang->translateString('NEWEST_SHOP_PRODUCTS', $iLang));
    }

    /**
     * getNewestArticlesUrl get url for 'Newest Shop Articles' rss feed
     *
     * @access public
     *
     * @return string
     */
    public function getNewestArticlesUrl()
    {
        return $this->_prepareUrl("cl=rss&amp;fnc=newarts", $this->getNewestArticlesTitle());
    }

    /**
     * loadNewestArticles loads 'Newest Shop Articles' rss data
     *
     * @access public
     *
     * @return void
     */
    public function loadNewestArticles()
    {
        if (($this->_aChannel = $this->_loadFromCache(self::RSS_NEWARTS))) {
            return;
        }
        $oArtList = oxNew(\OxidEsales\Eshop\Application\Model\ArticleList::class);
        $oArtList->loadNewestArticles($this->getConfig()->getConfigParam('iRssItemsCount'));

        $oLang = Registry::getLang();
        $this->_loadData(
            self::RSS_NEWARTS,
            $this->getNewestArticlesTitle(),
            $oLang->translateString('NEWEST_SHOP_PRODUCTS', $oLang->getBaseLanguage()),
            $this->_getArticleItems($oArtList),
            $this->getNewestArticlesUrl()
        );
    }


    /**
     * get title for 'Category Articles' rss feed
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat category object
     *
     * @access public
     *
     * @return string
     */
    public function getCategoryArticlesTitle(\OxidEsales\Eshop\Application\Model\Category $oCat)
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();
        $sTitle = $this->_getCatPath($oCat);

        return $this->_prepareFeedName($sTitle . $oLang->translateString('PRODUCTS', $iLang));
    }

    /**
     * Returns string built from category titles
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat category object
     *
     * @return string
     */
    protected function _getCatPath($oCat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sCatPathString = '';
        $sSep = '';
        while ($oCat) {
            // prepare oCat title part
            $sCatPathString = $oCat->oxcategories__oxtitle->value . $sSep . $sCatPathString;
            $sSep = '/';
            // load parent
            $oCat = $oCat->getParentCategory();
        }

        return $sCatPathString;
    }

    /**
     * getCategoryArticlesUrl get url for 'Category Articles' rss feed
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat category object
     *
     * @access public
     *
     * @return string
     */
    public function getCategoryArticlesUrl(\OxidEsales\Eshop\Application\Model\Category $oCat)
    {
        $oLang = Registry::getLang();

        return $this->_prepareUrl(
            "cl=rss&amp;fnc=catarts&amp;cat=" . urlencode($oCat->getId()),
            sprintf($oLang->translateString('CATEGORY_PRODUCTS_S', $oLang->getBaseLanguage()), $oCat->oxcategories__oxtitle->value)
        );
    }

    /**
     * loadCategoryArticles loads 'Category Articles' rss data
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat category object
     *
     * @access public
     *
     * @return void
     */
    public function loadCategoryArticles(\OxidEsales\Eshop\Application\Model\Category $oCat)
    {
        $sId = $oCat->getId();
        if (($this->_aChannel = $this->_loadFromCache(self::RSS_CATARTS . $sId))) {
            return;
        }

        $oArtList = oxNew(\OxidEsales\Eshop\Application\Model\ArticleList::class);
        $oArtList->setCustomSorting('oc.oxtimestamp desc');
        $oArtList->loadCategoryArticles($oCat->getId(), null, $this->getConfig()->getConfigParam('iRssItemsCount'));

        $oLang = Registry::getLang();
        $this->_loadData(
            self::RSS_CATARTS . $sId,
            $this->getCategoryArticlesTitle($oCat),
            sprintf($oLang->translateString('S_CATEGORY_PRODUCTS', $oLang->getBaseLanguage()), $oCat->oxcategories__oxtitle->value),
            $this->_getArticleItems($oArtList),
            $this->getCategoryArticlesUrl($oCat),
            $oCat->getLink()
        );
    }


    /**
     * get title for 'Search Articles' rss feed
     *
     * @param string $sSearch         search string
     * @param string $sCatId          category id
     * @param string $sVendorId       vendor id
     * @param string $sManufacturerId Manufacturer id
     *
     * @access public
     *
     * @return string
     */
    public function getSearchArticlesTitle($sSearch, $sCatId, $sVendorId, $sManufacturerId)
    {
        return $this->_prepareFeedName(getStr()->htmlspecialchars($this->_getSearchParamsTranslation('SEARCH_FOR_PRODUCTS_CATEGORY_VENDOR_MANUFACTURER', $sSearch, $sCatId, $sVendorId, $sManufacturerId)));
    }

    /**
     * _getSearchParamsUrl return search parameters for url
     *
     * @param string $sSearch         search string
     * @param string $sCatId          category id
     * @param string $sVendorId       vendor id
     * @param string $sManufacturerId Manufacturer id
     *
     * @access protected
     *
     * @return string
     */
    protected function _getSearchParamsUrl($sSearch, $sCatId, $sVendorId, $sManufacturerId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sParams = "searchparam=" . urlencode($sSearch);
        if ($sCatId) {
            $sParams .= "&amp;searchcnid=" . urlencode($sCatId);
        }

        if ($sVendorId) {
            $sParams .= "&amp;searchvendor=" . urlencode($sVendorId);
        }

        if ($sManufacturerId) {
            $sParams .= "&amp;searchmanufacturer=" . urlencode($sManufacturerId);
        }

        return $sParams;
    }

    /**
     * loads object and returns specified field
     *
     * @param string $sId     object id
     * @param string $sObject object class
     * @param string $sField  object field to be taken
     *
     * @access protected
     * @return string
     */
    protected function _getObjectField($sId, $sObject, $sField) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$sId) {
            return '';
        }
        $oObj = oxNew($sObject);
        if ($oObj->load($sId)) {
            return $oObj->$sField->value;
        }

        return '';
    }

    /**
     * _getSearchParamsTranslation translates text for given lang id
     * loads category and vendor to take their titles.
     *
     * @param string $sSearch         search param
     * @param string $sId             language id
     * @param string $sCatId          category id
     * @param string $sVendorId       vendor id
     * @param string $sManufacturerId Manufacturer id
     *
     * @access protected
     * @return string
     */
    protected function _getSearchParamsTranslation($sSearch, $sId, $sCatId, $sVendorId, $sManufacturerId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oLang = Registry::getLang();
        $sCatTitle = '';
        if ($sTitle = $this->_getObjectField($sCatId, 'oxcategory', 'oxcategories__oxtitle')) {
            $sCatTitle = sprintf($oLang->translateString('CATEGORY_S', $oLang->getBaseLanguage()), $sTitle);
        }
        $sVendorTitle = '';
        if ($sTitle = $this->_getObjectField($sVendorId, 'oxvendor', 'oxvendor__oxtitle')) {
            $sVendorTitle = sprintf($oLang->translateString('VENDOR_S', $oLang->getBaseLanguage()), $sTitle);
        }
        $sManufacturerTitle = '';
        if ($sTitle = $this->_getObjectField($sManufacturerId, 'oxmanufacturer', 'oxmanufacturers__oxtitle')) {
            $sManufacturerTitle = sprintf($oLang->translateString('MANUFACTURER_S', $oLang->getBaseLanguage()), $sTitle);
        }

        $sRet = sprintf($oLang->translateString($sSearch, $oLang->getBaseLanguage()), $sId);

        $sRet = str_replace('<TAG_CATEGORY>', $sCatTitle, $sRet);
        $sRet = str_replace('<TAG_VENDOR>', $sVendorTitle, $sRet);
        $sRet = str_replace('<TAG_MANUFACTURER>', $sManufacturerTitle, $sRet);

        return $sRet;
    }

    /**
     * getSearchArticlesUrl get url for 'Search Articles' rss feed
     *
     * @param string $sSearch         search string
     * @param string $sCatId          category id
     * @param string $sVendorId       vendor id
     * @param string $sManufacturerId Manufacturer id
     *
     * @access public
     *
     * @return string
     */
    public function getSearchArticlesUrl($sSearch, $sCatId, $sVendorId, $sManufacturerId)
    {
        $oLang = Registry::getLang();
        $sUrl = $this->_prepareUrl("cl=rss&amp;fnc=searcharts", $oLang->translateString('SEARCH', $oLang->getBaseLanguage()));

        $sJoin = '?';
        if (strpos($sUrl, '?') !== false) {
            $sJoin = '&amp;';
        }

        return $sUrl . $sJoin . $this->_getSearchParamsUrl($sSearch, $sCatId, $sVendorId, $sManufacturerId);
    }

    /**
     * loadSearchArticles loads 'Search Articles' rss data
     *
     * @param string $sSearch         search string
     * @param string $sCatId          category id
     * @param string $sVendorId       vendor id
     * @param string $sManufacturerId Manufacturer id
     *
     * @access public
     */
    public function loadSearchArticles($sSearch, $sCatId, $sVendorId, $sManufacturerId)
    {
        // dont use cache for search
        //if ($this->_aChannel = $this->_loadFromCache(self::RSS_SEARCHARTS.md5($sSearch.$sCatId.$sVendorId))) {
        //    return;
        //}

        $oConfig = $this->getConfig();
        $oConfig->setConfigParam('iNrofCatArticles', $oConfig->getConfigParam('iRssItemsCount'));

        $oArtList = oxNew(\OxidEsales\Eshop\Application\Model\Search::class)->getSearchArticles($sSearch, $sCatId, $sVendorId, $sManufacturerId, oxNew(\OxidEsales\Eshop\Application\Model\Article::class)->getViewName() . '.oxtimestamp desc');

        $this->_loadData(
            // dont use cache for search
            null,
            //self::RSS_SEARCHARTS.md5($sSearch.$sCatId.$sVendorId),
            $this->getSearchArticlesTitle($sSearch, $sCatId, $sVendorId, $sManufacturerId),
            $this->_getSearchParamsTranslation('SEARCH_FOR_PRODUCTS_CATEGORY_VENDOR_MANUFACTURER', getStr()->htmlspecialchars($sSearch), $sCatId, $sVendorId, $sManufacturerId),
            $this->_getArticleItems($oArtList),
            $this->getSearchArticlesUrl($sSearch, $sCatId, $sVendorId, $sManufacturerId),
            $this->_getShopUrl() . "cl=search&amp;" . $this->_getSearchParamsUrl($sSearch, $sCatId, $sVendorId, $sManufacturerId)
        );
    }

    /**
     * get title for 'Recommendation lists' rss feed
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle load lists for this article
     *
     * @return string
     */
    public function getRecommListsTitle(\OxidEsales\Eshop\Application\Model\Article $oArticle)
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();

        return $this->_prepareFeedName(sprintf($oLang->translateString('LISTMANIA_LIST_FOR', $iLang), $oArticle->oxarticles__oxtitle->value));
    }

    /**
     * get url for 'Recommendation lists' rss feed
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle load lists for this article
     *
     * @return string
     */
    public function getRecommListsUrl(\OxidEsales\Eshop\Application\Model\Article $oArticle)
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();

        return $this->_prepareUrl(
            "cl=rss&amp;fnc=recommlists&amp;anid=" . $oArticle->getId(),
            $oLang->translateString("LISTMANIA", $iLang) . "/" . $oArticle->oxarticles__oxtitle->value
        );
    }

    /**
     * make rss data array from given oxlist
     *
     * @param \OxidEsales\Eshop\Core\Model\ListModel $oList recommlist object
     *
     * @return array
     */
    protected function _getRecommListItems($oList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myUtilsUrl = Registry::getUtilsUrl();
        $aItems = [];
        foreach ($oList as $oRecommList) {
            $oItem = new stdClass();
            $oItem->title = $oRecommList->oxrecommlists__oxtitle->value;
            $oItem->guid = $oItem->link = $myUtilsUrl->prepareUrlForNoSession($oRecommList->getLink());
            $oItem->isGuidPermalink = true;
            $oItem->description = $oRecommList->oxrecommlists__oxdesc->value;

            $aItems[] = $oItem;
        }

        return $aItems;
    }

    /**
     * loads 'Recommendation lists' rss data
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle load lists for this article
     *
     * @return null
     */
    public function loadRecommLists(\OxidEsales\Eshop\Application\Model\Article $oArticle)
    {
        if (($this->_aChannel = $this->_loadFromCache(self::RSS_ARTRECOMMLISTS . $oArticle->getId()))) {
            return;
        }

        $oConfig = $this->getConfig();
        $oConfig->setConfigParam('iNrofCrossellArticles', $oConfig->getConfigParam('iRssItemsCount'));

        $oList = oxNew(\OxidEsales\Eshop\Application\Model\RecommendationList::class)->getRecommListsByIds([$oArticle->getId()]);
        if ($oList == null) {
            $oList = oxNew(\OxidEsales\Eshop\Core\Model\ListModel::class);
        }

        $oLang = Registry::getLang();
        $this->_loadData(
            self::RSS_ARTRECOMMLISTS . $oArticle->getId(),
            $this->getRecommListsTitle($oArticle),
            sprintf($oLang->translateString('LISTMANIA_LIST_FOR', $oLang->getBaseLanguage()), $oArticle->oxarticles__oxtitle->value),
            $this->_getRecommListItems($oList),
            $this->getRecommListsUrl($oArticle),
            $oArticle->getLink()
        );
    }

    /**
     * get title for 'Recommendation list articles' rss feed
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $oRecommList recomm list to load articles from
     *
     * @return string
     */
    public function getRecommListArticlesTitle(\OxidEsales\Eshop\Application\Model\RecommendationList $oRecommList)
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();

        return $this->_prepareFeedName(sprintf($oLang->translateString('LISTMANIA_LIST_PRODUCTS', $iLang), $oRecommList->oxrecommlists__oxtitle->value));
    }

    /**
     * get url for 'Recommendation lists' rss feed
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $oRecommList recomm list to load articles from
     *
     * @return string
     */
    public function getRecommListArticlesUrl(\OxidEsales\Eshop\Application\Model\RecommendationList $oRecommList)
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();

        return $this->_prepareUrl(
            "cl=rss&amp;fnc=recommlistarts&amp;recommid=" . $oRecommList->getId(),
            $oLang->translateString("LISTMANIA", $iLang) . "/" . $oRecommList->oxrecommlists__oxtitle->value
        );
    }

    /**
     * loads 'Recommendation lists' rss data
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $oRecommList recomm list to load articles from
     *
     * @return null
     */
    public function loadRecommListArticles(\OxidEsales\Eshop\Application\Model\RecommendationList $oRecommList)
    {
        if (($this->_aChannel = $this->_loadFromCache(self::RSS_RECOMMLISTARTS . $oRecommList->getId()))) {
            return;
        }

        $oList = oxNew(\OxidEsales\Eshop\Application\Model\ArticleList::class);
        $oList->loadRecommArticles($oRecommList->getId(), ' order by oxobject2list.oxtimestamp desc limit ' . $this->getConfig()->getConfigParam('iRssItemsCount'));

        $oLang = Registry::getLang();
        $this->_loadData(
            self::RSS_RECOMMLISTARTS . $oRecommList->getId(),
            $this->getRecommListArticlesTitle($oRecommList),
            sprintf($oLang->translateString('LISTMANIA_LIST_PRODUCTS', $oLang->getBaseLanguage()), $oRecommList->oxrecommlists__oxtitle->value),
            $this->_getArticleItems($oList),
            $this->getRecommListArticlesUrl($oRecommList),
            $oRecommList->getLink()
        );
    }

    /**
     * getBargainTitle get title for 'Bargain' rss feed
     *
     * @access public
     *
     * @return string
     */
    public function getBargainTitle()
    {
        $oLang = Registry::getLang();
        $iLang = $oLang->getBaseLanguage();

        return $this->_prepareFeedName($oLang->translateString('BARGAIN', $iLang));
    }

    /**
     * getBargainUrl get url for 'Bargain' rss feed
     *
     * @access public
     *
     * @return string
     */
    public function getBargainUrl()
    {
        return $this->_prepareUrl("cl=rss&amp;fnc=bargain", $this->getBargainTitle());
    }

    /**
     * loadBargain loads 'Bargain' rss data
     *
     * @access public
     *
     * @return void
     */
    public function loadBargain()
    {
        if (($this->_aChannel = $this->_loadFromCache(self::RSS_BARGAIN))) {
            return;
        }

        $oArtList = oxNew(\OxidEsales\Eshop\Application\Model\ArticleList::class);
        $oArtList->loadActionArticles('OXBARGAIN', $this->getConfig()->getConfigParam('iRssItemsCount'));

        $oLang = Registry::getLang();
        $this->_loadData(
            self::RSS_BARGAIN,
            $this->getBargainTitle(),
            $oLang->translateString('BARGAIN_PRODUCTS', $oLang->getBaseLanguage()),
            $this->_getArticleItems($oArtList),
            $this->getBargainUrl()
        );
    }

    /**
     * Returns timestamp of defind cache time to live
     *
     * @return integer
     */
    public function getCacheTtl()
    {
        return self::CACHE_TTL;
    }

    /**
     * Delete the file, given by its path.
     *
     * @param string $sFilePath The path of the file we want to delete.
     *
     * @return bool Went everything well?
     */
    protected function _deleteFile($sFilePath) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return @unlink($sFilePath);
    }
}
