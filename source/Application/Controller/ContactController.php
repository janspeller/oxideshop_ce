<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormField;
use OxidEsales\EshopCommunity\Internal\Domain\Contact\Form\ContactFormBridgeInterface;

/**
 * Contact window.
 * Arranges "CONTACT" window, by creating form for user opinion (etc.)
 * submission. After user correctly
 * fulfils all required fields all information is sent to shop owner by
 * email. OXID eShop -> CONTACT.
 */
class ContactController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Entered user data.
     *
     * @var array
     */
    protected $_aUserData = null;

    /**
     * Entered contact subject.
     *
     * @var string
     */
    protected $_sContactSubject = null;

    /**
     * Entered conatct message.
     *
     * @var string
     */
    protected $_sContactMessage = null;

    /**
     * Contact email send status.
     *
     * @var null|int
     */
    protected $_blContactSendStatus = null;

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/info/contact';

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * @var mixed|null|string
     */
    private $_oUserData;

    /**
     * @return string
     */
    public function render()
    {
        $form = ContainerFacade::get(ContactFormBridgeInterface::class)
            ->getContactForm();

        /** @var FormField $formField */
        foreach ($form->getFields() as $key => $formField) {
            $this->_aViewData['contactFormFields'][$key] = [
                'name' => $formField->getName(),
                'label' => $formField->getLabel(),
                'isRequired' => $formField->isRequired(),
            ];
        }

        return parent::render();
    }

    /**
     * Composes and sends user written message, returns false if some parameters
     * are missing.
     *
     * @return bool
     */
    public function send()
    {
        $contactFormBridge = ContainerFacade::get(ContactFormBridgeInterface::class);

        $form = $contactFormBridge->getContactForm();
        $form->handleRequest($this->getMappedContactFormRequest());

        if ($form->isValid()) {
            $this->sendContactMail(
                $form->email->getValue(),
                $form->subject->getValue(),
                $contactFormBridge->getContactFormMessage($form)
            );
        } else {
            foreach ($form->getErrors() as $error) {
                Registry::getUtilsView()->addErrorToDisplay($error);
            }

            return false;
        }
    }

    /**
     * Template variable getter. Returns entered user data
     *
     * @return object
     */
    public function getUserData()
    {
        if ($this->_aUserData === null) {
            $this->_aUserData = Registry::getRequest()->getRequestEscapedParameter('editval');
        }

        return $this->_aUserData;
    }

    /**
     * Template variable getter. Returns entered contact subject
     *
     * @return object
     */
    public function getContactSubject()
    {
        if ($this->_sContactSubject === null) {
            $this->_sContactSubject = Registry::getRequest()->getRequestEscapedParameter('c_subject');
        }

        return $this->_sContactSubject;
    }

    /**
     * Template variable getter. Returns entered message
     *
     * @return object
     */
    public function getContactMessage()
    {
        if ($this->_sContactMessage === null) {
            $this->_sContactMessage = Registry::getRequest()->getRequestEscapedParameter('c_message');
        }

        return $this->_sContactMessage;
    }

    /**
     * Template variable getter. Returns status if email was send succesfull
     *
     * @return null|int
     */
    public function getContactSendStatus()
    {
        return $this->_blContactSendStatus;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $title = Registry::getLang()->translateString(
            'CONTACT',
            Registry::getLang()->getBaseLanguage(),
            false
        );

        return [
            [
                'title' => $title,
                'link'  => $this->getLink(),
            ]
        ];
    }

    /**
     * Page title
     *
     * @return string
     */
    public function getTitle()
    {
        return \OxidEsales\Eshop\Core\Registry::getConfig()->getActiveShop()->oxshops__oxcompany->value;
    }

    /**
     * @return array
     */
    private function getMappedContactFormRequest()
    {
        $request = Registry::getRequest();
        $personData = $request->getRequestEscapedParameter('editval');

        return [
            'email'         => $personData['oxuser__oxusername'] ?? '',
            'firstName'     => $personData['oxuser__oxfname'] ?? '',
            'lastName'      => $personData['oxuser__oxlname'] ?? '',
            'salutation'    => $personData['oxuser__oxsal'] ?? '',
            'subject'       => $request->getRequestEscapedParameter('c_subject'),
            'message'       => $request->getRequestEscapedParameter('c_message'),
        ];
    }

    /**
     * Send a contact mail.
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     */
    private function sendContactMail($email, $subject, $message)
    {
        $mailer = oxNew(Email::class);

        if ($mailer->sendContactMail($email, $subject, $message)) {
            $this->_blContactSendStatus = 1;
        } else {
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_CHECK_EMAIL');
        }
    }
}
