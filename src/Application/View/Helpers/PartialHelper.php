<?php


namespace H4D\Leveret\Application\View\Helpers;


use H4D\Leveret\Application\View;
use H4D\Leveret\Application\View\Partial;

class PartialHelper extends AbstractHelper
{

    /**
     * AbstractHelper constructor.
     *
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @param View $parentView
     * @param string $templateRoute
     * @param array $partialVars
     *
     * @return Partial
     */
    public function __invoke(View $parentView, $templateRoute, array $partialVars = [])
    {
        $partial = new Partial(['parent' => $parentView]);
        $partial->setTemplateFile($templateRoute);
        // Add main view vars
        $partial->addVars($parentView->getVars());
        // Add local vars (can overide main view vars)
        if (count($partialVars) > 0)
        {
            $partial->addVars($partialVars);
        }

        return $partial;
    }
}