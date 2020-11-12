<?php
declare(strict_types=1);

namespace SM\DiscountWholeOrder\Model;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RulesApplier;
use SM\DiscountWholeOrder\Observer\WholeOrderDiscount\AbstractWholeOrderDiscountRule as DiscountWholeOrder;

/**
 * Class SalesRuleApplier
 * @package SM\DiscountWholeOrder\Model
 */
class SalesRuleApplier extends RulesApplier
{
    /**
     * Apply Rule
     *
     * @param AbstractItem $item
     * @param Rule $rule
     * @param Address $address
     * @param mixed $couponCode
     * @return $this
     */
    protected function applyRule($item, $rule, $address, $couponCode)
    {
        $discountData = $this->getDiscountData($item, $rule, $address);
        $this->setDiscountData($discountData, $item);

        // SCGPOS-169 - Add Discount Whole Order To Manual Discount
        $manualDiscountData = $item->getData('cpos_manual_discount_data')
            ? json_decode($item->getData('cpos_manual_discount_data'), true)
            : [];

        if ($this->isDiscountWholeOrder($rule)) {
            $manualDiscountData['discount_whole_order'] = $discountData->getAmount();
        }
        if ($rule->getCouponCode() !== null) {
            $manualDiscountData[$rule->getCouponCode()] = $discountData->getAmount();
        }

        $item->setData('cpos_manual_discount_data', json_encode($manualDiscountData));

        $this->maintainAddressCouponCode($address, $rule, $couponCode);
        $this->addDiscountDescription($address, $rule);

        return $this;
    }

    /**
     * @param Rule $rule
     * @return bool
     */
    protected function isDiscountWholeOrder($rule)
    {
        $id = $rule->getRuleId();
        $name = $rule->getName();

        return $id === DiscountWholeOrder::RULE_ID
            && ($name === DiscountWholeOrder::FIX_DISCOUNT_RULE_NAME
                || $name === DiscountWholeOrder::PERCENT_DISCOUNT_RULE_NAME);
    }
}
