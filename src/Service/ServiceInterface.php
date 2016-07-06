<?php


namespace H4D\Leveret\Service;


interface ServiceInterface
{
    const OPTION_CALLABLE_SINGLETON = 'CallableSingleton';

    /**
     * @return mixed
     */
    public function getValue();
}