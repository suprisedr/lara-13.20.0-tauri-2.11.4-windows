<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\CustomerAgeAnalysis;
use App\Models\EclRateSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AgeAnalysisService
{
    /**
     * Build the debtors age analysis for a company as at the given date, applying
     * the IFRS 9 simplified-approach provision matrix to compute the expected
     * credit loss (ECL) allowance for each ageing band, and persist the results
     * to the customer_age_analyses table.
     *
     * @return Collection<int, CustomerAgeAnalysis>
     */
    public function generate(Company $company, string $asOfDate): Collection
    {
        $asOf = Carbon::parse($asOfDate)->startOfDay();
        $asOfDateStr = $asOf->toDateString();
        $rates = EclRateSetting::forCompany($company);

        $openInvoices = $company->invoices()
            ->with('items', 'payments')
            ->whereIn('status', [
                InvoiceStatus::Pending->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->whereNotNull('customer_id')
            ->get();

        $buckets = $openInvoices
            ->groupBy('customer_id')
            ->map(function (Collection $invoices) use ($asOf) {
                $totals = [
                    'current_amount' => 0.0,
                    'days_31_60' => 0.0,
                    'days_61_90' => 0.0,
                    'days_91_plus' => 0.0,
                ];

                foreach ($invoices as $invoice) {
                    $balance = $invoice->balanceDue();

                    if ($balance <= 0) {
                        continue;
                    }

                    $dueDate = ($invoice->due_date ?? $invoice->invoice_date)->copy()->startOfDay();
                    $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;

                    $totals[$this->bucketFor($daysOverdue)] += $balance;
                }

                return $totals;
            });

        $rows = [];
        $now = now();

        foreach ($buckets as $customerId => $totals) {
            $totalOutstanding = round(
                $totals['current_amount'] + $totals['days_31_60'] + $totals['days_61_90'] + $totals['days_91_plus'],
                2
            );

            $eclCurrent = round($totals['current_amount'] * (float) $rates->current_rate, 2);
            $ecl3160 = round($totals['days_31_60'] * (float) $rates->days_31_60_rate, 2);
            $ecl6190 = round($totals['days_61_90'] * (float) $rates->days_61_90_rate, 2);
            $ecl91Plus = round($totals['days_91_plus'] * (float) $rates->days_91_plus_rate, 2);

            $rows[] = [
                'company_id' => $company->id,
                'customer_id' => $customerId,
                'as_of_date' => $asOfDateStr,
                'current_amount' => round($totals['current_amount'], 2),
                'days_31_60' => round($totals['days_31_60'], 2),
                'days_61_90' => round($totals['days_61_90'], 2),
                'days_91_plus' => round($totals['days_91_plus'], 2),
                'total_outstanding' => $totalOutstanding,
                'ecl_rate_current' => $rates->current_rate,
                'ecl_rate_31_60' => $rates->days_31_60_rate,
                'ecl_rate_61_90' => $rates->days_61_90_rate,
                'ecl_rate_91_plus' => $rates->days_91_plus_rate,
                'ecl_current' => $eclCurrent,
                'ecl_31_60' => $ecl3160,
                'ecl_61_90' => $ecl6190,
                'ecl_91_plus' => $ecl91Plus,
                'total_ecl' => round($eclCurrent + $ecl3160 + $ecl6190 + $ecl91Plus, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            CustomerAgeAnalysis::upsert(
                $rows,
                ['company_id', 'customer_id', 'as_of_date'],
                ['current_amount', 'days_31_60', 'days_61_90', 'days_91_plus', 'total_outstanding',
                 'ecl_rate_current', 'ecl_rate_31_60', 'ecl_rate_61_90', 'ecl_rate_91_plus',
                 'ecl_current', 'ecl_31_60', 'ecl_61_90', 'ecl_91_plus', 'total_ecl', 'updated_at'],
            );
        }

        // Remove stale rows for customers that no longer have an open balance as at this date.
        $company->customerAgeAnalyses()
            ->where('as_of_date', $asOfDateStr)
            ->whereNotIn('customer_id', $buckets->keys()->all() ?: [0])
            ->delete();

        $analyses = CustomerAgeAnalysis::where('company_id', $company->id)
            ->where('as_of_date', $asOfDateStr)
            ->get();

        return $analyses
            ->load('customer')
            ->sortBy(fn (CustomerAgeAnalysis $analysis) => $analysis->customer?->name)
            ->values();
    }

    /**
     * Classify days overdue into one of the IFRS 9 ageing bands.
     * Not-yet-due and 0-30 days overdue fall into "current".
     */
    private function bucketFor(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 30 => 'current_amount',
            $daysOverdue <= 60 => 'days_31_60',
            $daysOverdue <= 90 => 'days_61_90',
            default => 'days_91_plus',
        };
    }
}
