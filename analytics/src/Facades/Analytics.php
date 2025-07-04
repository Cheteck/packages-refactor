<?php

namespace IJIDeals\Analytics\Facades;

use Illuminate\Support\Facades\Facade;

class Analytics extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'analytics';
    }
}
