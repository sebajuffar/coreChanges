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
 * @category    Enterprise
 * @package     Enterprise_PageCache
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Full page cache cookie model
 *
 * @category   Enterprise
 * @package    Enterprise_PageCache
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_PageCache_Model_Cookie extends Mage_Core_Model_Cookie
{
    /**
     * Cookie names
     */
    const COOKIE_CUSTOMER           = 'CUSTOMER';
    const COOKIE_CUSTOMER_GROUP     = 'CUSTOMER_INFO';

    const COOKIE_MESSAGE            = 'NEWMESSAGE';
    const COOKIE_CART               = 'CART';
    const COOKIE_COMPARE_LIST       = 'COMPARE';
    const COOKIE_POLL               = 'POLL';
    const COOKIE_RECENTLY_COMPARED  = 'RECENTLYCOMPARED';
    const COOKIE_WISHLIST           = 'WISHLIST';
    const COOKIE_WISHLIST_ITEMS     = 'WISHLIST_CNT';

    const COOKIE_CUSTOMER_LOGGED_IN = 'CUSTOMER_AUTH';

    const COOKIE_FORM_KEY           = 'CACHED_FRONT_FORM_KEY';

    const COOKIE_CONFIG_CACHE_KEY   = 'full_page_cache_cookie_info';

    /**
     * Subprocessors cookie names
     */
    const COOKIE_CATEGORY_PROCESSOR = 'CATEGORY_INFO';

    /**
     * Cookie to store last visited category id
     */
    const COOKIE_CATEGORY_ID = 'LAST_CATEGORY';

    /**
     * Customer segment ids cookie name
     */
    const CUSTOMER_SEGMENT_IDS = 'CUSTOMER_SEGMENT_IDS';

    /**
     * Cookie name for users who allowed cookie save
     */
    const IS_USER_ALLOWED_SAVE_COOKIE  = 'user_allowed_save_cookie';

    /**
     * Encryption salt value
     *
     * @var sting
     */
    protected $_salt = null;

    /**
     * Session info
     *
     * @var array|null
     */
    static protected $_sessionInfo;

    /**
     * Retrieve encryption salt
     *
     * @return null|sting
     */
    protected function _getSalt()
    {
        if ($this->_salt === null) {
            $saltCacheId = 'full_page_cache_key';
            $this->_salt = Enterprise_PageCache_Model_Cache::getCacheInstance()->load($saltCacheId);
            if (!$this->_salt) {
                $this->_salt = md5(microtime() . rand());
                Enterprise_PageCache_Model_Cache::getCacheInstance()->save($this->_salt, $saltCacheId,
                    array(Enterprise_PageCache_Model_Processor::CACHE_TAG));
            }
        }
        return $this->_salt;
    }

    /**
     * Set cookie with obscure value
     *
     * @param string $name The cookie name
     * @param string $value The cookie value
     * @param int $period Lifetime period
     * @param string $path
     * @param string $domain
     * @param int|bool $secure
     * @param bool $httponly
     * @return Mage_Core_Model_Cookie
     */
    public function setObscure(
        $name, $value, $period = null, $path = null, $domain = null, $secure = null, $httponly = null
    ) {
        $value = md5($this->_getSalt() . $value);
        return $this->set($name, $value, $period, $path, $domain, $secure, $httponly);
    }

    /**
     * Keep customer cookies synchronized with customer session
     *
     * @return Enterprise_PageCache_Model_Cookie
     */
    public function updateCustomerCookies()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');
        $customerId = $session->getCustomerId();
        $customerGroupId = $session->getCustomerGroupId();
        if (!$customerId || is_null($customerGroupId)) {
            $customerCookies = new Varien_Object();
            Mage::dispatchEvent('update_customer_cookies', array('customer_cookies' => $customerCookies));
            if (!$customerId) {
                $customerId = $customerCookies->getCustomerId();
            }
            if (is_null($customerGroupId)) {
                $customerGroupId = $customerCookies->getCustomerGroupId();
            }
        }
        if ($customerId && !is_null($customerGroupId)) {
            $this->setObscure(self::COOKIE_CUSTOMER, 'customer_' . $customerId);
            $this->setObscure(self::COOKIE_CUSTOMER_GROUP, 'customer_group_' . $customerGroupId);
            if ($session->isLoggedIn()) {
                $this->setObscure(self::COOKIE_CUSTOMER_LOGGED_IN, 'customer_logged_in_' . $session->isLoggedIn());
            } else {
                $this->delete(self::COOKIE_CUSTOMER_LOGGED_IN);
            }
        } else {
            $this->delete(self::COOKIE_CUSTOMER);
            $this->delete(self::COOKIE_CUSTOMER_GROUP);
            $this->delete(self::COOKIE_CUSTOMER_LOGGED_IN);
        }
        return $this;
    }

    /**
     * Register viewed product ids in cookie
     *
     * @param int|string|array $productIds
     * @param int $countLimit
     * @param bool $append
     */
    public static function registerViewedProducts($productIds, $countLimit, $append = true)
    {
        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }
        if ($append) {
            if (!empty($_COOKIE[Enterprise_PageCache_Model_Container_Viewedproducts::COOKIE_NAME])) {
                $cookieIds = $_COOKIE[Enterprise_PageCache_Model_Container_Viewedproducts::COOKIE_NAME];
                $cookieIds = explode(',', $cookieIds);
            } else {
                $cookieIds = array();
            }
            array_splice($cookieIds, 0, 0, $productIds);  // append to the beginning
        } else {
            $cookieIds = $productIds;
        }
        $cookieIds = array_unique($cookieIds);
        $cookieIds = array_slice($cookieIds, 0, $countLimit);
        $cookieIds = implode(',', $cookieIds);
        self::_setCookie(Enterprise_PageCache_Model_Container_Viewedproducts::COOKIE_NAME, $cookieIds);
    }

    /**
     * Set catalog cookie
     *
     * @param string $value
     */
    public static function setCategoryCookieValue($value)
    {
        self::_setCookie(self::COOKIE_CATEGORY_PROCESSOR, $value);
    }

    /**
     * Get catalog cookie
     *
     * @static
     * @return bool
     */
    public static function getCategoryCookieValue()
    {
        return (isset($_COOKIE[self::COOKIE_CATEGORY_PROCESSOR])) ? $_COOKIE[self::COOKIE_CATEGORY_PROCESSOR] : false;
    }

    /**
     * Set cookie with visited category id
     *
     * @param int $id
     */
    public static function setCategoryViewedCookieValue($id)
    {
        self::_setCookie(self::COOKIE_CATEGORY_ID, $id);
    }

    /**
     * Set cookie with form key for cached front
     *
     * @param string $formKey
     */
    public static function setFormKeyCookieValue($formKey)
    {
        self::_setCookie(self::COOKIE_FORM_KEY, $formKey);
    }

    /**
     * Get form key cookie value
     *
     * @return string|bool
     */
    public static function getFormKeyCookieValue()
    {
        return (isset($_COOKIE[self::COOKIE_FORM_KEY])) ? $_COOKIE[self::COOKIE_FORM_KEY] : false;
    }

    /**
     * Get session info by frontend cookie
     *
     * @return array
     */
    protected static function _getSessionInfo()
    {
        if (is_null(self::$_sessionInfo)) {
            $sessionInfo = Enterprise_PageCache_Model_Cache::getCacheInstance()->load(self::COOKIE_CONFIG_CACHE_KEY);
            if ($sessionInfo) {
                self::$_sessionInfo = unserialize($sessionInfo);
            } else {
                self::setSessionInfo();
            }
        }
        return self::$_sessionInfo;
    }

    /**
     * Set cookie with using session info
     *
     * @param string $name
     * @param string $value
     */
    protected static function _setCookie($name, $value)
    {
        $sessionInfo = self::_getSessionInfo();
        if (is_array($sessionInfo)) {
            setcookie($name, $value, 0, $sessionInfo['path'], $sessionInfo['domain'], $sessionInfo['secure'],
                $sessionInfo['httponly']
            );
        }
    }

    /**
     * Set session info for frontend cookie
     */
    public static function setSessionInfo()
    {
        try {
            $session = Mage::getSingleton('core/session');
            self::$_sessionInfo = array(
                'lifetime' => $session->getCookie()->getLifetime(),
                'path'     => $session->getCookie()->getPath(),
                'domain'   => $session->getCookie()->getDomain(),
                'secure'   => $session->getCookie()->isSecure(),
                'httponly' => $session->getCookie()->getHttponly(),
            );
            $cacheData = serialize(self::$_sessionInfo);
            Enterprise_PageCache_Model_Cache::getCacheInstance()->save($cacheData, self::COOKIE_CONFIG_CACHE_KEY,
                array(Enterprise_PageCache_Model_Processor::CACHE_TAG)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
