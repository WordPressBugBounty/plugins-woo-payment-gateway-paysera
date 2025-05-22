<?php

declare(strict_types=1);

namespace Paysera\Admin;

class PayseraSelfDiagnosticAdmin
{
    private PayseraSelfDiagnosticAdminHtml $renderer;

    public function __construct(PayseraSelfDiagnosticAdminHtml $renderer)
    {
        $this->renderer = $renderer;
    }

    public function buildDiagnosticPage()
    {
        echo $this->renderer->render();
    }
}
