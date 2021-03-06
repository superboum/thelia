<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Action;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Thelia\Model\ProductQuery;
use Thelia\Model\Product as ProductModel;

use Thelia\Core\Event\TheliaEvents;

use Thelia\Core\Event\Product\ProductUpdateEvent;
use Thelia\Core\Event\Product\ProductCreateEvent;
use Thelia\Core\Event\Product\ProductDeleteEvent;
use Thelia\Core\Event\UpdatePositionEvent;
use Thelia\Core\Event\Product\ProductToggleVisibilityEvent;
use Thelia\Core\Event\Product\ProductAddContentEvent;
use Thelia\Core\Event\Product\ProductDeleteContentEvent;
use Thelia\Model\ProductAssociatedContent;
use Thelia\Model\ProductAssociatedContentQuery;
use Thelia\Model\ProductCategory;
use Thelia\Model\TaxRuleQuery;
use Thelia\Model\AccessoryQuery;
use Thelia\Model\Accessory;
use Thelia\Core\Event\FeatureProduct\FeatureProductUpdateEvent;
use Thelia\Model\FeatureProduct;
use Thelia\Core\Event\FeatureProduct\FeatureProductDeleteEvent;
use Thelia\Model\FeatureProductQuery;
use Thelia\Model\ProductCategoryQuery;
use Thelia\Core\Event\Product\ProductSetTemplateEvent;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Core\Event\Product\ProductDeleteCategoryEvent;
use Thelia\Core\Event\Product\ProductAddCategoryEvent;
use Thelia\Core\Event\Product\ProductAddAccessoryEvent;
use Thelia\Core\Event\Product\ProductDeleteAccessoryEvent;

class Product extends BaseAction implements EventSubscriberInterface
{
    /**
     * Create a new product entry
     *
     * @param \Thelia\Core\Event\Product\ProductCreateEvent $event
     */
    public function create(ProductCreateEvent $event)
    {
        $product = new ProductModel();

        $product
            ->setDispatcher($this->getDispatcher())

            ->setRef($event->getRef())
            ->setTitle($event->getTitle())
            ->setLocale($event->getLocale())
            ->setVisible($event->getVisible())

            // Set the default tax rule to this product
            ->setTaxRule(TaxRuleQuery::create()->findOneByIsDefault(true))

            ->create(
                    $event->getDefaultCategory(),
                    $event->getBasePrice(),
                    $event->getCurrencyId(),
                    $event->getTaxRuleId(),
                    $event->getBaseWeight()
            );
         ;

        $event->setProduct($product);
    }

    /**
     * Change a product
     *
     * @param \Thelia\Core\Event\Product\ProductUpdateEvent $event
     */
    public function update(ProductUpdateEvent $event)
    {
        if (null !== $product = ProductQuery::create()->findPk($event->getProductId())) {

            $product
                ->setDispatcher($this->getDispatcher())

                ->setLocale($event->getLocale())
                ->setTitle($event->getTitle())
                ->setDescription($event->getDescription())
                ->setChapo($event->getChapo())
                ->setPostscriptum($event->getPostscriptum())
                ->setVisible($event->getVisible())

                ->save()
            ;

            // Update the rewriten URL, if required
            $product->setRewrittenUrl($event->getLocale(), $event->getUrl());

            // Update default category (ifd required)
            $product->updateDefaultCategory($event->getDefaultCategory());

            $event->setProduct($product);
        }
    }

    /**
     * Delete a product entry
     *
     * @param \Thelia\Core\Event\Product\ProductDeleteEvent $event
     */
    public function delete(ProductDeleteEvent $event)
    {
        if (null !== $product = ProductQuery::create()->findPk($event->getProductId())) {

            $product
                ->setDispatcher($this->getDispatcher())
                ->delete()
            ;

            $event->setProduct($product);
        }
    }

    /**
     * Toggle product visibility. No form used here
     *
     * @param ActionEvent $event
     */
    public function toggleVisibility(ProductToggleVisibilityEvent $event)
    {
         $product = $event->getProduct();

         $product
            ->setDispatcher($this->getDispatcher())
            ->setVisible($product->getVisible() ? false : true)
            ->save()
            ;
    }

    /**
     * Changes position, selecting absolute ou relative change.
     *
     * @param ProductChangePositionEvent $event
     */
    public function updatePosition(UpdatePositionEvent $event)
    {
        return $this->genericUpdatePosition(ProductQuery::create(), $event);
    }

    public function addContent(ProductAddContentEvent $event)
    {
        if (ProductAssociatedContentQuery::create()
            ->filterByContentId($event->getContentId())
             ->filterByProduct($event->getProduct())->count() <= 0) {

            $content = new ProductAssociatedContent();

            $content
                ->setDispatcher($this->getDispatcher())
                ->setProduct($event->getProduct())
                ->setContentId($event->getContentId())
                ->save()
            ;
         }
    }

    public function removeContent(ProductDeleteContentEvent $event)
    {
        $content = ProductAssociatedContentQuery::create()
            ->filterByContentId($event->getContentId())
            ->filterByProduct($event->getProduct())->findOne()
        ;

        if ($content !== null)
            $content
                ->setDispatcher($this->getDispatcher())
                ->delete()
            ;
    }

    public function addCategory(ProductAddCategoryEvent $event)
    {
        if (ProductCategoryQuery::create()
            ->filterByProduct($event->getProduct())
            ->filterByCategoryId($event->getCategoryId())
            ->count() <= 0) {

            $productCategory = new ProductCategory();

            $productCategory
                ->setProduct($event->getProduct())
                ->setCategoryId($event->getCategoryId())
                ->setDefaultCategory(false)
                ->save()
            ;
        }
    }

    public function removeCategory(ProductDeleteCategoryEvent $event)
    {
        $productCategory = ProductCategoryQuery::create()
            ->filterByProduct($event->getProduct())
            ->filterByCategoryId($event->getCategoryId())
            ->findOne();

        if ($productCategory != null) $productCategory->delete();
    }

    public function addAccessory(ProductAddAccessoryEvent $event)
    {
        if (AccessoryQuery::create()
            ->filterByAccessory($event->getAccessoryId())
            ->filterByProductId($event->getProduct()->getId())->count() <= 0) {

            $accessory = new Accessory();

            $accessory
                ->setDispatcher($this->getDispatcher())
                ->setProductId($event->getProduct()->getId())
                ->setAccessory($event->getAccessoryId())
            ->save()
            ;
        }
    }

    public function removeAccessory(ProductDeleteAccessoryEvent $event)
    {
        $accessory = AccessoryQuery::create()
            ->filterByAccessory($event->getAccessoryId())
            ->filterByProductId($event->getProduct()->getId())->findOne()
        ;

        if ($accessory !== null)
            $accessory
                ->setDispatcher($this->getDispatcher())
                ->delete()
            ;
    }

    public function setProductTemplate(ProductSetTemplateEvent $event)
    {
        $product = $event->getProduct();

        // Delete all product feature relations
        FeatureProductQuery::create()->filterByProduct($product)->delete();

        // Delete all product attributes sale elements
        ProductSaleElementsQuery::create()->filterByProduct($product)->delete();

        // Update the product template
        $template_id = $event->getTemplateId();

        // Set it to null if it's zero.
        if ($template_id <= 0) $template_id = NULL;

        $product->setTemplateId($template_id)->save();
    }

    /**
     * Changes accessry position, selecting absolute ou relative change.
     *
     * @param ProductChangePositionEvent $event
     */
    public function updateAccessoryPosition(UpdatePositionEvent $event)
    {
        return $this->genericUpdatePosition(AccessoryQuery::create(), $event);
    }

    /**
     * Changes position, selecting absolute ou relative change.
     *
     * @param ProductChangePositionEvent $event
     */
    public function updateContentPosition(UpdatePositionEvent $event)
    {
        return $this->genericUpdatePosition(ProductAssociatedContentQuery::create(), $event);
    }

    /**
     * Update the value of a product feature.
     *
     * @param FeatureProductUpdateEvent $event
     */
    public function updateFeatureProductValue(FeatureProductUpdateEvent $event)
    {
        // If the feature is not free text, it may have one ore more values.
        // If the value exists, we do not change it
        // If the value does not exists, we create it.
        //
        // If the feature is free text, it has only a single value.
        // Etiher create or update it.

        $featureProductQuery = FeatureProductQuery::create()
            ->filterByFeatureId($event->getFeatureId())
            ->filterByProductId($event->getProductId())
        ;

        if ($event->getIsTextValue() !== true) {
            $featureProductQuery->filterByFeatureAvId($event->getFeatureValue());
        }

        $featureProduct = $featureProductQuery->findOne();

        if ($featureProduct == null) {
            $featureProduct = new FeatureProduct();

            $featureProduct
                ->setDispatcher($this->getDispatcher())

                ->setProductId($event->getProductId())
                ->setFeatureId($event->getFeatureId())
            ;
        }

        if ($event->getIsTextValue() == true) {
            $featureProduct->setFreeTextValue($event->getFeatureValue());
        } else {
            $featureProduct->setFeatureAvId($event->getFeatureValue());
        }

        $featureProduct->save();

        $event->setFeatureProduct($featureProduct);
    }

    /**
     * Delete a product feature value
     *
     * @param FeatureProductDeleteEvent $event
     */
    public function deleteFeatureProductValue(FeatureProductDeleteEvent $event)
    {
        $featureProduct = FeatureProductQuery::create()
            ->filterByProductId($event->getProductId())
            ->filterByFeatureId($event->getFeatureId())
            ->delete()
        ;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::PRODUCT_CREATE            => array("create", 128),
            TheliaEvents::PRODUCT_UPDATE            => array("update", 128),
            TheliaEvents::PRODUCT_DELETE            => array("delete", 128),
            TheliaEvents::PRODUCT_TOGGLE_VISIBILITY => array("toggleVisibility", 128),

            TheliaEvents::PRODUCT_UPDATE_POSITION => array("updatePosition", 128),

            TheliaEvents::PRODUCT_ADD_CONTENT               => array("addContent", 128),
            TheliaEvents::PRODUCT_REMOVE_CONTENT            => array("removeContent", 128),
            TheliaEvents::PRODUCT_UPDATE_CONTENT_POSITION   => array("updateContentPosition", 128),

            TheliaEvents::PRODUCT_ADD_ACCESSORY             => array("addAccessory", 128),
            TheliaEvents::PRODUCT_REMOVE_ACCESSORY          => array("removeAccessory", 128),
            TheliaEvents::PRODUCT_UPDATE_ACCESSORY_POSITION => array("updateAccessoryPosition", 128),

            TheliaEvents::PRODUCT_ADD_CATEGORY    => array("addCategory", 128),
            TheliaEvents::PRODUCT_REMOVE_CATEGORY => array("removeCategory", 128),

            TheliaEvents::PRODUCT_SET_TEMPLATE => array("setProductTemplate", 128),

            TheliaEvents::PRODUCT_FEATURE_UPDATE_VALUE => array("updateFeatureProductValue", 128),
            TheliaEvents::PRODUCT_FEATURE_DELETE_VALUE => array("deleteFeatureProductValue", 128),
        );
    }
}
