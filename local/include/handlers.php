<?php
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'main',
    'OnBuildGlobalMenu',
    ['CustomMenu', 'addExtraSettingsMenu']
);
require_once __DIR__."/handlersClasses/CustomMenu.php";


Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    ['ProductCountObserver','emailSender']
);
require_once __DIR__."/handlersClasses/ProductCountObserver.php";

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderPaid',
    ['CouponMakerHandler','createCoupon']
);
require_once __DIR__."/handlersClasses/CouponMakerHandler.php";