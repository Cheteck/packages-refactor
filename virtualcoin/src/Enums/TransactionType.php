<?php

namespace IJIDeals\VirtualCoin\Enums;

enum TransactionType: string
{
    case DEPOSIT_PURCHASE = 'deposit_purchase';
    case DEPOSIT_BONUS = 'deposit_bonus';
    case SPEND_ITEM = 'spend_item';
    case SPEND_SERVICE = 'spend_service';
    case SPEND_SPONSORSHIP_FUNDING = 'spend_sponsorship_funding';
    case SPEND_SPONSORSHIP_IMPRESSION = 'spend_sponsorship_impression';
    case SPEND_SPONSORSHIP_CLICK = 'spend_sponsorship_click';
    case EARN_REWARD_ACTIVITY = 'earn_reward_activity';
    case EARN_REFERRAL_BONUS = 'earn_referral_bonus';
    case REFUND_ITEM = 'refund_item';
    case REFUND_SPONSORSHIP = 'refund_sponsorship';
    case WITHDRAWAL_CASH_OUT = 'withdrawal_cash_out';
    case ADJUSTMENT_CREDIT = 'adjustment_credit';
    case ADJUSTMENT_DEBIT = 'adjustment_debit';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT_PURCHASE => 'Deposit (Purchase)',
            self::DEPOSIT_BONUS => 'Deposit (Bonus)',
            self::SPEND_ITEM => 'Spend (Item Purchase)',
            self::SPEND_SERVICE => 'Spend (Service Fee)',
            self::SPEND_SPONSORSHIP_FUNDING => 'Spend (Sponsorship Funding)',
            self::SPEND_SPONSORSHIP_IMPRESSION => 'Spend (Sponsorship Impression Cost)',
            self::SPEND_SPONSORSHIP_CLICK => 'Spend (Sponsorship Click Cost)',
            self::EARN_REWARD_ACTIVITY => 'Earn (Activity Reward)',
            self::EARN_REFERRAL_BONUS => 'Earn (Referral Bonus)',
            self::REFUND_ITEM => 'Refund (Item Return)',
            self::REFUND_SPONSORSHIP => 'Refund (Sponsorship Cancellation)',
            self::WITHDRAWAL_CASH_OUT => 'Withdrawal (Cash Out)',
            self::ADJUSTMENT_CREDIT => 'Adjustment (Credit by Admin)',
            self::ADJUSTMENT_DEBIT => 'Adjustment (Debit by Admin)',
            self::OTHER => 'Other Transaction',
        };
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
}
