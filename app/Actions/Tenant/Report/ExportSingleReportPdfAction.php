<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Report;

use App\Support\TenantDateTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExportSingleReportPdfAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, string $reportKey): Response
    {
        $reportTitles = [
            'sales' => 'Sales Report',
            'purchases' => 'Purchases Report',
            'sale-payments' => 'Sale Payments Report',
            'vendor-payments' => 'Vendor Payments Report',
            'receivables' => 'Receivables Report',
            'payables' => 'Payables Report',
        ];

        $reportSlugs = [
            'sales' => 'sales',
            'purchases' => 'purchases',
            'sale-payments' => 'sale-payments',
            'vendor-payments' => 'vendor-payments',
            'receivables' => 'receivables',
            'payables' => 'payables',
        ];

        abort_unless(isset($reportTitles[$reportKey], $reportSlugs[$reportKey]), 404);

        $data['generatedAt'] = TenantDateTime::now();
        $data['reportKey'] = $reportKey;
        $data['reportTitle'] = $reportTitles[$reportKey];

        return Pdf::loadView('tenants.reports.single-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download(
                sprintf(
                    '%s-report-%s.pdf',
                    $reportSlugs[$reportKey],
                    TenantDateTime::now()->format('Ymd_His')
                )
            );
    }
}
