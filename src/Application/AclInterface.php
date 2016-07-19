<?php


namespace H4D\Leveret\Application;


interface AclInterface
{
    /**
     * @return bool
     */
    public function isAllowed();

    /**
     * @return bool
     */
    public function hasMessage();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return bool
     */
    public function hasRedirectUrl();

    /**
     * @return string
     */
    public function getRedirectUrl();

}