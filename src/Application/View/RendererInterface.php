<?php

namespace H4D\Leveret\Application\View;

use H4D\Leveret\Application\View;

interface RendererInterface
{
    const TEMPLATE_ENGINE_DEFAULT = 'default';
    const TEMPLATE_ENGINE_TWIG = 'twig';

    /**
     * @param View $view
     * @param string $templatePath
     *
     * @return string
     */
    public function render(View $view, $templatePath);
}