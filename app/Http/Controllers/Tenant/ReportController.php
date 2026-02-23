<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Report\BuildReportDataAction;
use App\Actions\Tenant\Report\ExportSingleReportPdfAction;
use App\Http\Controllers\Controller;
use App\Support\TenantDateTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ReportController extends Controller
{
    public function index(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.index', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function overview(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.index', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function sales(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.sales', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function purchases(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.purchases', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function salePayments(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.sale-payments', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function vendorPayments(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.vendor-payments', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function receivables(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.receivables', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function payables(Request $request, BuildReportDataAction $buildReportDataAction): View
    {
        return view('tenants.reports.payables', $buildReportDataAction->handle($request, $this->currentBranchId(), true));
    }

    public function exportPdf(Request $request, BuildReportDataAction $buildReportDataAction): Response
    {
        $data = $buildReportDataAction->handle($request, $this->currentBranchId(), false);
        $data['generatedAt'] = TenantDateTime::now();

        return Pdf::loadView('tenants.reports.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download('reports-'.TenantDateTime::now()->format('Ymd_His').'.pdf');
    }

    public function exportSalesPdf(Request $request, BuildReportDataAction $buildReportDataAction, ExportSingleReportPdfAction $exportSingleReportPdfAction): Response
    {
        return $exportSingleReportPdfAction->handle($buildReportDataAction->handle($request, $this->currentBranchId(), false), 'sales');
    }

    public function exportPurchasesPdf(Request $request, BuildReportDataAction $buildReportDataAction, ExportSingleReportPdfAction $exportSingleReportPdfAction): Response
    {
        return $exportSingleReportPdfAction->handle($buildReportDataAction->handle($request, $this->currentBranchId(), false), 'purchases');
    }

    public function exportSalePaymentsPdf(Request $request, BuildReportDataAction $buildReportDataAction, ExportSingleReportPdfAction $exportSingleReportPdfAction): Response
    {
        return $exportSingleReportPdfAction->handle($buildReportDataAction->handle($request, $this->currentBranchId(), false), 'sale-payments');
    }

    public function exportVendorPaymentsPdf(Request $request, BuildReportDataAction $buildReportDataAction, ExportSingleReportPdfAction $exportSingleReportPdfAction): Response
    {
        return $exportSingleReportPdfAction->handle($buildReportDataAction->handle($request, $this->currentBranchId(), false), 'vendor-payments');
    }

    public function exportReceivablesPdf(Request $request, BuildReportDataAction $buildReportDataAction, ExportSingleReportPdfAction $exportSingleReportPdfAction): Response
    {
        return $exportSingleReportPdfAction->handle($buildReportDataAction->handle($request, $this->currentBranchId(), false), 'receivables');
    }

    public function exportPayablesPdf(Request $request, BuildReportDataAction $buildReportDataAction, ExportSingleReportPdfAction $exportSingleReportPdfAction): Response
    {
        return $exportSingleReportPdfAction->handle($buildReportDataAction->handle($request, $this->currentBranchId(), false), 'payables');
    }

    public function exportSummaryPdf(Request $request, BuildReportDataAction $buildReportDataAction): Response
    {
        $data = $buildReportDataAction->handle($request, $this->currentBranchId(), false);
        $data['generatedAt'] = TenantDateTime::now();

        return Pdf::loadView('tenants.reports.summary-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download('reports-summary-'.TenantDateTime::now()->format('Ymd_His').'.pdf');
    }
}
