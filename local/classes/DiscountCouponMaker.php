<?php
/*https://dev.1c-bitrix.ru/api_d7/bitrix/sale/classes/internals/discountcoupontable/index.php*/
namespace ITtower\Discount;
require($_SERVER["DOCUMENT_ROOT"]."/local/classes/CustomDiscountCouponTable.php");


use \Bitrix\Iblock\Elements\ElementCatalogTable; // need to set api name "catalog" in catalog iblock settings
use ITtower\Discount\CustomDiscountCouponTable;


if(!\Bitrix\Main\Loader::includeModule('sale')){
    throw new \Bitrix\Main\Config\ConfigurationException("Нет модуля торговый каталог");
}
class DiscountCouponMaker
{
    protected $products = array();
    protected $discountIds = array();

    public function __construct(array $arProducts, array $arProductsId)
    {
        $this->setProductsData($arProducts, $arProductsId);
    }


/*Bitrix\Main\ORM\Data\Result*/
    public function createCoupons()
    {
        if(!empty($this->products)){
            foreach($this->products as &$product){
                $fields['COUPON'] = array(
                    'DISCOUNT_ID' => $product["DISCOUNT_ID_VALUE"],
                    'ACTIVE_FROM' => null,
                    'ACTIVE_TO' => null,
                    'TYPE' => CustomDiscountCouponTable::TYPE_ONE_ORDER,
                    'MAX_USE' => 1,
                );
                $couponsResult = CustomDiscountCouponTable::addPacket(
                    $fields['COUPON'],
                    $product["QUANTITY"]
                );
                if ($couponsResult->isSuccess()){
                    $coupon = $couponsResult->getData();
                    $product["COUPON_CODE"] = $coupon["code"];
                    //\Extra\Dump::dump($coupon);
                }else{
                    $errors = $couponsResult->getErrorMessages();
                    \Extra\Dump::toFile($errors, "couponException.log");
                    throw new \Bitrix\Main\ObjectException("Не создан купон");
                }
            }
        }
        return $this;
       // \Extra\Dump::dump($this->products);
    }


    public function getProducts(): array
    {
        return $this->products;
    }

    protected function setProductsData(array $arProducts, array $arProductsId)
    {

        $dbItems = ElementCatalogTable::getList([
            "select" => [
                "ID", "NAME", "DETAIL_PICTURE",
                "IS_SERT_VALUE" => "IS_SERT.VALUE",
                "DISCOUNT_ID_VALUE" => "DISCOUNT_ID.VALUE",
            ],
            "filter" => [
                "ID"=>$arProductsId,
                "ACTIVE"=>"Y",
            ]
        ])->fetchAll();

        foreach($dbItems as $dbItem){
            if((int)$dbItem["IS_SERT_VALUE"] > 0 && (int)$dbItem["DISCOUNT_ID_VALUE"] > 0){
                $this->products[$dbItem['ID']] = array_merge($dbItem, $arProducts[$dbItem["ID"]]);
                $this->discountIds[] = $dbItem["DISCOUNT_ID_VALUE"];
            }
        }

        return $this;
    }

    protected function getDiscountIds(): array
    {
        $ids = array();

        foreach($this->products as $product){
            if((int)$product["IS_SERT_VALUE"] > 0 && (int)$product["DISCOUNT_ID_VALUE"] > 0){
                $ids[] = $product["DISCOUNT_ID_VALUE"];
            }
        }

        return $ids;
    }
}