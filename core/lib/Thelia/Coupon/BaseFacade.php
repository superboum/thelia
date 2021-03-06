<?php
/**********************************************************************************/
/*                                                                                */
/*      Thelia	                                                                  */
/*                                                                                */
/*      Copyright (c) OpenStudio                                                  */
/*      email : info@thelia.net                                                   */
/*      web : http://www.thelia.net                                               */
/*                                                                                */
/*      This program is free software; you can redistribute it and/or modify      */
/*      it under the terms of the GNU General Public License as published by      */
/*      the Free Software Foundation; either version 3 of the License             */
/*                                                                                */
/*      This program is distributed in the hope that it will be useful,           */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of            */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             */
/*      GNU General Public License for more details.                              */
/*                                                                                */
/*      You should have received a copy of the GNU General Public License         */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.      */
/*                                                                                */
/**********************************************************************************/

namespace Thelia\Coupon;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Thelia\Condition\ConditionEvaluator;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\Coupon;
use Thelia\Model\CouponQuery;
use Thelia\Cart\CartTrait;
use Thelia\Model\Currency;
use Thelia\Model\CurrencyQuery;

/**
 * Created by JetBrains PhpStorm.
 * Date: 8/19/13
 * Time: 3:24 PM
 *
 * Allow to assist in getting relevant data on the current application state
 *
 * @package Coupon
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 *
 */
class BaseFacade implements FacadeInterface
{
    use CartTrait {
        CartTrait::getCart as getCartFromTrait;
    }

    /** @var ContainerInterface Service Container */
    protected $container = null;

    /** @var Translator Service Translator  */
    protected $translator = null;

    /**
     * Constructor
     *
     * @param ContainerInterface $container  Service container
     */
    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Return a Cart a CouponManager can process
     *
     * @return \Thelia\Model\Cart
     */
    public function getCart()
    {
        return $this->getCartFromTrait($this->getRequest());
    }

    /**
     * Return an Address a CouponManager can process
     *
     * @return \Thelia\Model\Address
     */
    public function getDeliveryAddress()
    {
        // @todo: Implement getDeliveryAddress() method.
    }

    /**
     * Return an Customer a CouponManager can process
     *
     * @return \Thelia\Model\Customer
     */
    public function getCustomer()
    {
        return $this->container->get('thelia.securityContext')->getCustomerUser();
    }

    /**
     * Return Checkout total price
     *
     * @return float
     */
    public function getCheckoutTotalPrice()
    {
        return $this->getRequest()->getSession()->getOrder()->getTotalAmount();
    }

    /**
     * Return Checkout total postage (only) price
     *
     * @return float
     */
    public function getCheckoutPostagePrice()
    {
        return $this->getRequest()->getSession()->getOrder()->getPostage();
    }

    /**
     * Return Products total price
     *
     * @return float
     */
    public function getCartTotalPrice()
    {
        return $this->getRequest()->getSession()->getCart()->getTotalAmount();

    }

    /**
     * Return the Checkout currency EUR|USD
     *
     * @return Currency
     */
    public function getCheckoutCurrency()
    {
        return $this->getRequest()->getSession()->getCurrency()->getCode();
    }


    /**
     * Return the number of Products in the Cart
     *
     * @return int
     */
    public function getNbArticlesInCart()
    {
        return count($this->getRequest()->getSession()->getCart()->getCartItems());
    }

    /**
     * Return all Coupon given during the Checkout
     *
     * @return array Array of CouponInterface
     */
    public function getCurrentCoupons()
    {
        $couponCodes = $this->getRequest()->getSession()->getConsumedCoupons();

        if (null === $couponCodes) {
            return array();
        }
        $couponFactory = $this->container->get('thelia.coupon.factory');

        $coupons = array();
        foreach ($couponCodes as $couponCode) {
            $coupons[] = $couponFactory->buildCouponFromCode($couponCode);
        }

        return $coupons;
    }

    /**
     * Find one Coupon in the database from its code
     *
     * @param string $code Coupon code
     *
     * @return Coupon
     */
    public function findOneCouponByCode($code)
    {
        $couponQuery = CouponQuery::create();

        return $couponQuery->findOneByCode($code);
    }

    /**
     * Return platform Container
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Return platform TranslatorInterface
     *
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->container->get('thelia.translator');
    }


    /**
     * Return the main currency
     * THe one used to set prices in BackOffice
     *
     * @return string
     */
    public function getMainCurrency()
    {
        return $this->getRequest()->getSession()->getCurrency();
    }

    /**
     * Return request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * Return Constraint Validator
     *
     * @return ConditionEvaluator
     */
    public function getConditionEvaluator()
    {
        return $this->container->get('thelia.condition.validator');
    }


    /**
     * Return all available currencies
     *
     * @return array of Currency
     */
    public function getAvailableCurrencies()
    {
        $currencies = CurrencyQuery::create();

        return $currencies->find();
    }

    /**
     * Return the event dispatcher,
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }
}
