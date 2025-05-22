<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util;

interface ReportFormatterInterface
{
    public function formatReport(array $results): string;
}
