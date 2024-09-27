<?php declare(strict_types=1);

    use STDW\Session\Contract\SessionInterface;
    use STDW\Session\Contract\FlashMessageInterface;


    if ( ! function_exists('session'))
    {
        function session(): SessionInterface
        {
            return app()->make(SessionInterface::class);
        }
    }

    if ( ! function_exists('flash'))
    {
        function flash(): FlashMessageInterface
        {
            return app()->make(FlashMessageInterface::class);
        }
    }