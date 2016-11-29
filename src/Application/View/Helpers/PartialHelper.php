<?php


namespace H4D\Leveret\Application\View\Helpers;


use H4D\Leveret\Application\View;
use H4D\Leveret\Application\View\Partial;
use H4D\Leveret\Application\View\ViewAwareInterface;
use H4D\Leveret\Application\View\ViewAwareTrait;

class PartialHelper extends AbstractHelper implements ViewAwareInterface
{

    use ViewAwareTrait;

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
     * @param string $templateRoute
     * @param array $partialVars
     *
     * @return Partial
     */
    public function __invoke($templateRoute, array $partialVars = [])
    {
        $parentView = $this->view;
        $partial = new Partial(['parent' => $parentView]);
        $partial->setTemplateFile($templateRoute);
        // Add parent view vars
        $partial->addVars($parentView->getVars());
        // Add local vars (can overide main view vars)
        if (count($partialVars) > 0)
        {
            $partial->addVars($partialVars);
        }

        return $partial;
    }
}