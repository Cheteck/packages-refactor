<?php

namespace IJIDeals\AuctionSystem\Enums;

enum BidStatusEnum: string
{
    case ACTIVE = 'active';
    case WINNING = 'winning';
    case OUTBID = 'outbid';
    case CANCELLED = 'cancelled';
}
