<?php declare(strict_types=1);

    namespace STDW\Session\Spec;

    use STDW\Contract\Session\SessionConfigInterface as ContractSessionConfigInterface;


    interface SessionConfigInterface extends ContractSessionConfigInterface
    {
        /** @return string
         */
        public function handler(): string;
    }
