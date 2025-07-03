<?php

namespace IJIDeals\AuctionSystem\Enums;

enum AuctionStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
    case SOLD = 'sold';
    case FAILED = 'failed';
    case EXHAUSTED_BUDGET = 'exhausted_budget';
}
