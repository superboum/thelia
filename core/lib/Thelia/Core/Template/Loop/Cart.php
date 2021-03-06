<?php
/**
 * Created by JetBrains PhpStorm.
 * User: manu
 * Date: 08/08/13
 * Time: 12:44
 * To change this template use File | Settings | File Templates.
 */

namespace Thelia\Core\Template\Loop;

use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\CountryQuery;
use Thelia\Type;

class Cart extends BaseLoop
{
    use \Thelia\Cart\CartTrait;
    /**
     *
     * define all args used in your loop
     *
     * array key is your arg name.
     *
     * example :
     *
     * return array (
     *  "ref",
     *  "id" => "optional",
     *  "stock" => array(
     *          "optional",
     *          "default" => 10
     *          )
     * );
     *
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('limit'),
            Argument::createAnyTypeArgument('position')
        );
    }

    /**
     *
     * this function have to be implement in your own loop class.
     *
     * All your parameters are defined in defineArgs() and can be accessible like a class property.
     *
     * example :
     *
     * public function defineArgs()
     * {
     *  return array (
     *      "ref",
     *      "id" => "optional",
     *      "stock" => array(
     *          "optional",
     *          "default" => 10
     *          )
     *  );
     * }
     *
     * you can retrieve ref value using $this->ref
     *
     * @param $pagination
     *
     * @return mixed
     */
    public function exec(&$pagination)
    {

        $cart = $this->getCart($this->request);

        $cartItems = $cart->getCartItems();
        $result = new LoopResult($cartItems);

        if ($cart === null) {
            return $result;
        }

        $limit = $this->getLimit();

        $countCartItems = count($cartItems);

        if ($limit <= 0 || $limit >= $countCartItems) {
            $limit = $countCartItems;
        }

        $position = $this->getPosition();

        if (isset($position)) {
            if ($position == "first") {
                $limit = 1;
                $cartItems = array($cartItems[0]);
            } elseif ($position == "last") {
                $limit = 1;
                $cartItems = array(end($cartItems));
            }

            // @TODO : if the position is a number
        }

        $taxCountry = CountryQuery::create()->findPk(64); // @TODO : make it magic;

        for ($i=0; $i<$limit; $i ++) {
            $cartItem = $cartItems[$i];
            $product = $cartItem->getProduct();
            $productSaleElement = $cartItem->getProductSaleElements();

            $loopResultRow = new LoopResultRow($result, $cartItem, $this->versionable, $this->timestampable, $this->countable);

            $loopResultRow->set("ITEM_ID", $cartItem->getId());
            $loopResultRow->set("TITLE", $product->getTitle());
            $loopResultRow->set("REF", $product->getRef());
            $loopResultRow->set("QUANTITY", $cartItem->getQuantity());
            $loopResultRow->set("PRICE", $cartItem->getPrice());
            $loopResultRow->set("PRODUCT_ID", $product->getId());
            $loopResultRow->set("PRODUCT_URL", $product->getUrl($this->request->getSession()->getLang()->getLocale()))
                ->set("STOCK", $productSaleElement->getQuantity())
                ->set("PRICE", $cartItem->getPrice())
                ->set("PROMO_PRICE", $cartItem->getPromoPrice())
                ->set("TAXED_PRICE", $cartItem->getTaxedPrice($taxCountry))
                ->set("PROMO_TAXED_PRICE", $cartItem->getTaxedPromoPrice($taxCountry))
                ->set("IS_PROMO", $cartItem->getPromo() === 1 ? 1 : 0);
            $loopResultRow->set("PRODUCT_SALE_ELEMENTS_ID", $productSaleElement->getId());
            $result->addRow($loopResultRow);
        }

        return $result;
    }

    /**
     * Return the event dispatcher,
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
