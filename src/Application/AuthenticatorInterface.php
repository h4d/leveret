<?php

namespace H4D\Leveret\Application;

interface AuthenticatorInterface
{
    /**
     * @param string $user
     * @param string $pass
     *
     * @param string $remoteAdress (optional)
     *
     * @return bool
     */
    public function authenticate($user, $pass, $remoteAdress = null);

    /**
     * Message related with authentication proccess (i.e: reason for denial)
     *
     * @return string
     */
    public function getMessage();
}