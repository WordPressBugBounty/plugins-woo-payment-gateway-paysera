<?php

declare(strict_types=1);

namespace Paysera\Admin;

use Paysera\Action\PayseraSelfDiagnosisActions;
use Paysera\Entity\PayseraPaths;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Service\CompatibilityCheckerManager;

class PayseraSelfDiagnosticAdminHtml
{
    private CompatibilityCheckerManager $compatibilityManager;

    public function __construct(CompatibilityCheckerManager $compatibilityManager)
    {
        $this->compatibilityManager = $compatibilityManager;
    }

    public function render(): string
    {
        $results = $this->getCheckResults();

        ob_start(); ?>
        <div class="wrap">
            <h1><?php esc_html_e('Self-Diagnosis Tool', PayseraPaths::PAYSERA_TRANSLATIONS); ?></h1>
            <a href="<?php echo admin_url(sprintf('admin-post.php?action=%s', PayseraSelfDiagnosisActions::DOWNLOAD_DIAGNOSTIC_TEXT_RESULT)); ?>" class="button">
                <?php echo __('Download the diagnostic report', PayseraPaths::PAYSERA_TRANSLATIONS); ?>
            </a>
            <?php foreach ($results as $category => $checks): ?>
                <h2><?php echo __(
                        sprintf('%s Details', ucfirst($category)),
                        PayseraPaths::PAYSERA_TRANSLATIONS
                    ) ?></h2>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Test', PayseraPaths::PAYSERA_TRANSLATIONS); ?></th>
                        <th><?php esc_html_e('Status', PayseraPaths::PAYSERA_TRANSLATIONS); ?></th>
                        <th><?php esc_html_e('Details', PayseraPaths::PAYSERA_TRANSLATIONS); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($checks as $result): ?>
                        <tr>
                            <td><?php echo esc_html($result->checkName); ?></td>
                            <td>
                                <?php if ($result->isSuccess): ?>
                                    <span style="color: green;"><?php esc_html_e('Pass', PayseraPaths::PAYSERA_TRANSLATIONS); ?></span>
                                <?php else: ?>
                                    <span style="color: red;"><?php esc_html_e('Fail', PayseraPaths::PAYSERA_TRANSLATIONS); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><pre style="margin: 0; padding: 0; white-space: break-spaces;"><?php echo $result->details; ?></pre></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @return CheckerResult[][]
     */
    private function getCheckResults(): array
    {
        return $this->compatibilityManager->runChecks();
    }
}
