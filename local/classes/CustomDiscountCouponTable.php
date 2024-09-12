<?php
namespace ITtower\Discount;

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/lib/internals/discountcoupon.php";

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Sale\Internals\DiscountCouponTable;
use \Bitrix\Sale\Internals\DiscountTable;
use Bitrix\Main;

class CustomDiscountCouponTable extends DiscountCouponTable
{
    /**
     * Create one and more coupons for discount.
     *
     * @param array $data				Coupon data.
     * @param int $count				Coupos count.
     * @param int $limit				Maximum number of attempts.
     * @return \Main\Entity\Result
     */
    public static function addPacket(array $data, $count, $limit = 0)
    {
        $result = new Main\Entity\Result();
        $result->setData(array(
            'result' => 0,
            'count' => $count,
            'limit' => $limit,
            'all' => 0
        ));
        $count = (int)$count;
        if ($count <= 0)
        {
            $result->addError(new Main\Entity\EntityError(
                Loc::getMessage('DISCOUNT_COUPON_PACKET_COUNT_ERR'),
                'COUPON_PACKET'
            ));
        }
        foreach (static::getEntity()->getFields() as $field)
        {
            if ($field instanceof Main\Entity\ScalarField &&  !array_key_exists($field->getName(), $data))
            {
                $defaultValue =  $field->getDefaultValue();

                if ($defaultValue !== null)
                    $data[$field->getName()] = $field->getDefaultValue();
            }
        }
        $checkResult = static::checkPacket($data, false);
        if (!$checkResult->isSuccess())
        {
            foreach ($checkResult->getErrors() as $checkError)
            {
                $result->addError($checkError);
            }
            unset($checkError);
        }
        unset($checkResult);
        $useCoupons = false;
        $discountIterator = DiscountTable::getList(array(
            'select' => array('ID', 'USE_COUPONS'),
            'filter' => array('=ID' => $data['DISCOUNT_ID'])
        ));
        if ($discount = $discountIterator->fetch())
        {
            $useCoupons = ($discount['USE_COUPONS'] == 'Y');
        }
        else
        {
            $result->addError(new Main\Entity\EntityError(
                Loc::getMessage('DISCOUNT_COUPON_PACKET_DISCOUNT_ERR'),
                'COUPON_PACKET'
            ));
        }
        if (!$result->isSuccess(true))
            return $result;

        self::setDiscountCheckList($data['DISCOUNT_ID']);
        self::disableCheckCouponsUse();
        $limit = (int)$limit;
        if ($limit < $count)
            $limit = $count*2;
        $resultCount = 0;
        $all = 0;
        $code = "";
        do
        {
            $data['COUPON'] = self::generateCoupon(true);
            $code .= $data['COUPON'].",";
            $couponResult = self::add($data);
            if ($couponResult->isSuccess())
                $resultCount++;
            $all++;
        } while ($resultCount < $count && $all < $limit);
        $result->setData(array(
            'result' => $resultCount,
            'count' => $count,
            'limit' => $limit,
            'all' => $all,
            'code' => mb_substr($code, 0, -1)
        ));
        if ($resultCount == 0)
        {
            $result->addError(new Main\Entity\EntityError(
                ($useCoupons
                    ? Loc::getMessage('DISCOUNT_COUPON_PACKET_GENERATE_COUPON_ZERO_ERR')
                    : Loc::getMessage('DISCOUNT_COUPON_PACKET_NEW_GENERATE_COUPON_ZERO_ERR')
                ),
                'COUPON_PACKET'
            ));
            self::clearDiscountCheckList();
        }
        elseif ($resultCount < $count)
        {
            $result->addError(new Main\Entity\EntityError(
                Loc::getMessage(
                    'DISCOUNT_COUPON_PACKET_GENERATE_COUPON_COUNT_ERR',
                    array(
                        '#RESULT#' => $resultCount,
                        '#COUNT#' => $count,
                        '#ALL#' => $all
                    )
                ),
                'COUPON_PACKET'
            ));
        }
        self::enableCheckCouponsUse();
        self::updateUseCoupons();

        return $result;
    }
}