<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\JobCardStatus;
use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\JobCard;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\VendorPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

final class EndOfDayController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $selectedDateInput = mb_trim($request->string('date')->toString());
        $selectedDate = $selectedDateInput !== ''
            ? Date::parse($selectedDateInput)->toDateString()
            : now()->toDateString();
        $dayStart = Date::parse($selectedDate)->startOfDay();
        $dayEnd = Date::parse($selectedDate)->endOfDay();
        $monthStart = Date::parse($selectedDate)->startOfMonth();

        $salesQuery = Sale::query()
            ->where('branch_id', $branchId)
            ->whereDate('invoice_date', $selectedDate);
        $purchasesQuery = Purchase::query()
            ->where('branch_id', $branchId)
            ->whereDate('purchase_date', $selectedDate);
        $salePaymentsQuery = SalePayment::query()
            ->where('branch_id', $branchId)
            ->whereBetween('paid_at', [$dayStart, $dayEnd]);
        $mechanicPayableQuery = SaleItem::query()
            ->where('branch_id', $branchId)
            ->where('line_type', 'service')
            ->whereNotNull('mechanic_id')
            ->where('mechanic_charge', '>', 0)
            ->whereHas('sale', fn ($query) => $query->whereDate('invoice_date', $selectedDate));
        $vendorPaymentsQuery = VendorPayment::query()
            ->where('branch_id', $branchId)
            ->whereBetween('paid_at', [$dayStart, $dayEnd]);
        $expensesQuery = Expense::query()
            ->where('branch_id', $branchId)
            ->whereDate('expense_date', $selectedDate);
        $attendanceQuery = EmployeeAttendance::query()
            ->where('branch_id', $branchId)
            ->whereDate('attendance_date', $selectedDate);
        $salaryMonthQuery = EmployeeSalary::query()
            ->where('branch_id', $branchId)
            ->whereDate('salary_month', $monthStart->toDateString());

        $cashIn = (float) (clone $salePaymentsQuery)->sum('amount');
        $expenseTotal = (float) (clone $expensesQuery)->sum('amount');
        $cashOut = (float) (clone $vendorPaymentsQuery)->sum('amount') + $expenseTotal;

        $checkedInCount = (clone $attendanceQuery)->whereNotNull('check_in_at')->count();
        $checkedOutCount = (clone $attendanceQuery)->whereNotNull('check_out_at')->count();

        return view('tenants.end-of-day', [
            'selectedDate' => $selectedDate,
            'summary' => [
                'sales_count' => (clone $salesQuery)->count(),
                'sales_total' => (float) (clone $salesQuery)->sum('grand_total'),
                'purchases_count' => (clone $purchasesQuery)->count(),
                'purchases_total' => (float) (clone $purchasesQuery)->sum('grand_total'),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'cash_net' => $cashIn - $cashOut,
                'expenses_count' => (clone $expensesQuery)->count(),
                'expenses_total' => $expenseTotal,
                'mechanic_payable_count' => (clone $mechanicPayableQuery)->count(),
                'mechanic_payable_total' => (float) (clone $mechanicPayableQuery)->sum('mechanic_charge'),
                'open_job_cards' => JobCard::query()
                    ->where('branch_id', $branchId)
                    ->whereNotIn('status', [JobCardStatus::CLOSED->value, JobCardStatus::CANCELLED->value])
                    ->count(),
                'checked_in_count' => $checkedInCount,
                'checked_out_count' => $checkedOutCount,
                'missing_checkout_count' => max($checkedInCount - $checkedOutCount, 0),
                'payroll_total' => (float) (clone $salaryMonthQuery)->sum('net_salary'),
                'payroll_paid_total' => (float) (clone $salaryMonthQuery)->whereNotNull('paid_at')->sum('net_salary'),
                'payroll_unpaid_total' => (float) (clone $salaryMonthQuery)->whereNull('paid_at')->sum('net_salary'),
            ],
            'recentSales' => (clone $salesQuery)->latest('invoice_date')->limit(8)->get(),
            'recentPurchases' => (clone $purchasesQuery)->latest('purchase_date')->limit(8)->get(),
        ]);
    }
}
