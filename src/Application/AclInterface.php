<?php


namespace H4D\Leveret\Application;


interface AclInterface
{
    /**
     * @return bool
     */
    public function isAllowed();
}