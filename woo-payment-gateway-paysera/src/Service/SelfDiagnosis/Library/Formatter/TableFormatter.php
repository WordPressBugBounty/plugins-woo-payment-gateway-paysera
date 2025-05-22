<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Formatter;

use Paysera\Service\SelfDiagnosis\Library\Util\ReportFormatterInterface;

class TableFormatter implements ReportFormatterInterface
{
    private const COLUMN_WIDTHS = [
        'name' => 55,
        'result' => 90,
        'status' => 6
    ];

    public string $logo;

    public function __construct(string $logo)
    {
        $this->logo = $logo;
    }

    public function formatReport(array $results): string
    {
        $output = $this->getLogo() . PHP_EOL;
        foreach ($results as $sectionName => $sectionResults) {
            $output .= $this->formatSection($sectionName, $sectionResults);
        }
        return $output;
    }

    private function getLogo(): string
    {
        return sprintf("%s%s", base64_decode($this->logo), str_repeat(PHP_EOL,3) );
    }

    private function formatSection(string $sectionName, array $sectionResults): string
    {
        $output = strtoupper($sectionName) . PHP_EOL;
        $output .= str_repeat('-', 161) . PHP_EOL;
        $output .= sprintf("| %-55s | %-90s | %-6s |%s", 'Name', 'Result', 'Status', PHP_EOL);
        $output .= str_repeat('-', 161) . PHP_EOL;

        foreach ($sectionResults as $checkResult) {
            $output .= $this->formatRow($checkResult->checkName, $checkResult->details, $checkResult->isSuccess ? 'OK' : 'FAIL');
        }

        $output .= str_repeat('-', 161) . PHP_EOL . PHP_EOL;
        return $output;
    }

    private function formatRow(string $name, string $details, string $status): string
    {
        $wrappedName = $this->wrapText($name, self::COLUMN_WIDTHS['name']);
        $wrappedDetails = $this->wrapText($details, self::COLUMN_WIDTHS['result']);

        $output = '';
        $lines = max(count($wrappedName), count($wrappedDetails));
        for ($i = 0; $i < $lines; $i++) {
            $output .= sprintf(
                "| %-55s | %-90s | %-6s |%s",
                $wrappedName[$i] ?? '',
                $wrappedDetails[$i] ?? '',
                $i === 0 ? $status : '',
                PHP_EOL
            );
        }

        return $output;
    }

    private function wrapText(string $text, int $maxLength): array
    {
        return explode(PHP_EOL, wordwrap($text, $maxLength, PHP_EOL, true));
    }
}
