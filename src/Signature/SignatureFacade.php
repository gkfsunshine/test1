<?php

namespace JiaLeo\Laravel\Signature;

use Illuminate\Support\Facades\Facade;

class SignatureFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'signature';
    }
}
