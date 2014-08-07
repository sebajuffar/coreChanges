<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Base html block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Block_Text_List_Link extends Mage_Core_Block_Text
{
    function setLink($liParams, $aParams, $innerText, $afterText='')
    {
        $this->setLiParams($liParams);
        $this->setAParams($aParams);
        $this->setInnerText($innerText);
        $this->setAfterText($afterText);

        return $this;
    }

    protected function _toHtml()
    {
        $this->setText('<li');
        $params = $this->getLiParams();
        if (!empty($params) && is_array($params)) {
            foreach ($params as $key=>$value) {
                $this->addText(' '.$key.'="'.addslashes($value).'"');
            }
        } elseif (is_string($params)) {
            $this->addText(' '.$params);
        }
        $this->addText('><a');

        $params = $this->getAParams();
        if (!empty($params) && is_array($params)) {
            foreach ($params as $key=>$value) {
                $this->addText(' '.$key.'="'.addslashes($value).'"');
            }
        } elseif (is_string($params)) {
            $this->addText(' '.$params);
        }

        $this->addText('>'.$this->getInnerText().'</a>'.$this->getAfterText().'</li>'."\r\n");

        return parent::_toHtml();
    }

}
