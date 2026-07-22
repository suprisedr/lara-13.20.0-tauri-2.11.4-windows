<?php

namespace App\Observers;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\VatRegistration;
use Illuminate\Support\Facades\DB;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "updated" event.
     *
     * Watches for changes to vat_number and keeps the vat_registrations
     * table and VAT chart-of-accounts entries in sync.
     */
    public function updated(Company $company): void
    {
        if (! $company->isDirty('vat_number')) {
            return;
        }

        $newVatNumber = $company->vat_number;

        DB::transaction(function () use ($company, $newVatNumber) {
            if ($newVatNumber !== null) {
                // Deactivate any previously active registrations first
                $company->vatRegistrations()
                    ->where('is_active', true)
                    ->update([
                        'is_active'       => false,
                        'deregistered_at' => now()->toDateString(),
                    ]);

                // Create the new active registration
                $company->vatRegistrations()->create([
                    'vat_number'    => $newVatNumber,
                    'registered_at' => now()->toDateString(),
                    'is_active'     => true,
                ]);

                // Enable VAT accounts in the chart of accounts
                $company->chartOfAccounts()
                    ->where('category', 'VAT')
                    ->update(['is_active' => true]);
            } else {
                // Mark the current registration as deregistered
                $company->vatRegistrations()
                    ->where('is_active', true)
                    ->update([
                        'is_active'       => false,
                        'deregistered_at' => now()->toDateString(),
                    ]);

                // Disable VAT accounts in the chart of accounts
                $company->chartOfAccounts()
                    ->where('category', 'VAT')
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }
}
