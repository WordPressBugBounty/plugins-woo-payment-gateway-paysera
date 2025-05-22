<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util;

use Paysera\Service\SelfDiagnosis\Library\Service\CompatibilityCheckerManager;

class DiagnosticReportGenerator
{
    private CompatibilityCheckerManager $compatibilityManager;
    private ReportFormatterInterface $formatter;

    public function __construct(ReportFormatterInterface $formatter, CompatibilityCheckerManager $compatibilityManager)
    {
        $this->compatibilityManager = $compatibilityManager;
        $this->formatter = $formatter;
    }

    public function getReport(): string
    {
        $results = $this->compatibilityManager->runChecks();
        return $this->formatter->formatReport($results);
    }
}
