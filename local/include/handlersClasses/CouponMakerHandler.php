<?php
require($_SERVER["DOCUMENT_ROOT"]."/local/classes/DiscountCouponMaker.php");

use ITtower\Discount\DiscountCouponMaker;
use Bitrix\Main\Mail\Event;
class CouponMakerHandler {
    public static function createCoupon(\Bitrix\Main\Event $event)
    {
        /** @var Order $order */

        $order = $event->getParameter("ENTITY");
        \Extra\Dump::toFile("1", "dump_log.log");
        /*Only paid orders*/
        if (!$order ->isPaid() or $order->isPaid() == false ) return;

        \Extra\Dump::toFile("2","dump_log.log");
        $basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
        $prodIds = array();
        foreach ($basket as $basketItem) {
            $pid = $basketItem->getField('PRODUCT_ID');
            $prodIds[] = $pid;

            $products[$pid]["PRODUCT_ID"] = $pid;
            $products[$pid]["QUANTITY"] = $basketItem->getQuantity();
            $products[$pid]["BASE_PRICE"] = $basketItem->getField('BASE_PRICE');
        }

        $coupon = new DiscountCouponMaker($products, $prodIds);
        $products = array();
        if(is_a($coupon, DiscountCouponMaker::class)) {
            $coupon->createCoupons();
            $products = $coupon->getProducts();
        }


        if(!empty($products)){
            $propertyCollection = $order->getPropertyCollection();
            $message = self::getMailMessage($propertyCollection, $products);
            $emailPropValue = $propertyCollection->getUserEmail()->getValue();
            $namePropValue  = $propertyCollection->getPayerName()->getValue();

            Event::send([
                "EVENT_NAME" => "COUPON",
                "LID" => "s1",
                "C_FIELDS" => [
                    'HTML_MESSAGE' => $message,
                    'USER_EMAIL' => $emailPropValue,
                    'USER_NAME' => $namePropValue,
                ]
            ]);
        }
    }

    public static function getMailMessage($propertyCollection, $products): string
    {
        $message = "";

        $message = " Ваши скидочные купоны: </br>";
        foreach ($products as $product){
            $message .= "<b>".$product["NAME"].":</b> ".$product["COUPON_CODE"]."</br>";
        }
        $message .= "С уважением, администрация сайта ";

        return $message;
    }


}