<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\ServiceProviderAbstracted;
    use STDW\Session\Contract\SessionHandlerInterface;
    use STDW\Session\Contract\SessionInterface;
    use STDW\Session\Contract\FlashMessageInterface;
    use STDW\Session\Handler\SessionFileHandler;
    use STDW\Session\Session;
    use STDW\Session\FlashMessage;


    class ServiceProvider extends ServiceProviderAbstracted
    {
        public function register(): void
        {
            $this->app->singleton(SessionHandlerInterface::class, SessionFileHandler::class);
            $this->app->singleton(SessionInterface::class, Session::class);
            $this->app->singleton(FlashMessageInterface::class, FlashMessage::class);
        }

        public function boot(): void
        {
            session()->start();
        }

        public function terminate(): void
        {
            flash()->clear();
        }
    }