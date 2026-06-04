<?php

function view(string $templatePath, array $data = []): string
{
    $templatePath = \Core\Application\Application::instance()->viewDir() . DIRECTORY_SEPARATOR . $templatePath;
    return \Core\Template\Renderer::instance()->render($templatePath, $data);
}
