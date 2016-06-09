<?php


namespace Mocks;


use H4D\Leveret\Application\AuthenticatorInterface;

class SampleAuth implements AuthenticatorInterface
{

    /**
     * @param string $user
     * @param string $pass
     *
     * @param string $remoteAdress (optional)
     *
     * @return bool
     */
    public function authenticate($user, $pass, $remoteAdress = null)
    {
        return true;
    }

    /**
     * Message related with authentication proccess (i.e: reason for denial)
     *
     * @return string
     */
    public function getMessage()
    {
        return '';
    }
}