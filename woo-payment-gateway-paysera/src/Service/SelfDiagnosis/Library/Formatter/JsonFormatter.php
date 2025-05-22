<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Formatter;

use Paysera\Service\SelfDiagnosis\Library\Util\ReportFormatterInterface;

class JsonFormatter implements ReportFormatterInterface
{
    public function formatReport(array $results): string
    {
        return json_encode($results);
    }
}
