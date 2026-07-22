<?php

namespace App\Services;

/**
 * South African PAYE, UIF and SDL calculations.
 * Tax year 2025/2026 (1 March 2025 – 28 February 2026).
 * Source: SARS Budget Tax Guide 2025.
 */
class SaTaxService
{
    // ── Tax brackets ───────────────────────────────────────────────────────
    // [lower_limit, base_tax, marginal_rate]
    private const BRACKETS = [
        [0,          0,       0.18],
        [237_100,    42_678,  0.26],
        [370_500,    77_362,  0.31],
        [512_800,    121_475, 0.36],
        [673_000,    179_147, 0.39],
        [857_900,    251_258, 0.41],
        [1_817_000,  644_489, 0.45],
    ];

    // ── Rebates ────────────────────────────────────────────────────────────
    private const REBATE_PRIMARY   = 17_235;
    private const REBATE_SECONDARY = 9_444;   // age 65–74
    private const REBATE_TERTIARY  = 3_145;   // age 75+

    // ── Medical aid tax credits (monthly) ─────────────────────────────────
    private const MEDICAL_CREDIT_FIRST_TWO    = 364;  // per member, first two
    private const MEDICAL_CREDIT_ADDITIONAL   = 246;  // each additional member

    // ── UIF ────────────────────────────────────────────────────────────────
    private const UIF_RATE              = 0.01;
    private const UIF_MONTHLY_EARNINGS_CAP = 17_712;

    // ── SDL ────────────────────────────────────────────────────────────────
    private const SDL_RATE = 0.01;

    // ── Section 11F retirement deduction cap ──────────────────────────────
    private const RETIREMENT_DEDUCTION_RATE = 0.275;
    private const RETIREMENT_DEDUCTION_CAP  = 350_000;

    /**
     * Calculate monthly PAYE using the annual equivalent method.
     *
     * @param  float  $monthlyGrossEarnings   Taxable income for this period
     * @param  float  $monthlyRetirementFund  Employee retirement fund contribution (section 11F)
     * @param  int    $medicalAidMembers       Total members (employee + dependants), min 1
     * @param  int    $age                     Employee age (for rebates / threshold)
     * @param  int    $periodsPerYear          12 = monthly, 26 = fortnightly, 52 = weekly
     */
    public function calculatePaye(
        float $monthlyGrossEarnings,
        float $monthlyRetirementFund = 0,
        int $medicalAidMembers = 1,
        int $age = 30,
        int $periodsPerYear = 12,
    ): array {
        // 1. Annual equivalent of gross
        $annualGross = $monthlyGrossEarnings * $periodsPerYear;

        // 2. Section 11F deduction (retirement fund / RA)
        $annualRetirement  = $monthlyRetirementFund * $periodsPerYear;
        $retirementDeduct  = min(
            $annualRetirement,
            $annualGross * self::RETIREMENT_DEDUCTION_RATE,
            self::RETIREMENT_DEDUCTION_CAP,
        );

        $annualTaxable = max(0, $annualGross - $retirementDeduct);

        // 3. Tax on taxable income
        $annualTax = $this->taxOnIncome($annualTaxable);

        // 4. Rebates
        $rebates = self::REBATE_PRIMARY;
        if ($age >= 75) {
            $rebates += self::REBATE_SECONDARY + self::REBATE_TERTIARY;
        } elseif ($age >= 65) {
            $rebates += self::REBATE_SECONDARY;
        }

        $annualTaxAfterRebates = max(0, $annualTax - $rebates);

        // 5. Medical aid tax credit (monthly amount × 12)
        $monthlyCreditEarnings = $this->medicalAidCredit($medicalAidMembers);
        $annualMedicalCredit   = $monthlyCreditEarnings * 12;

        $annualTaxNet = max(0, $annualTaxAfterRebates - $annualMedicalCredit);

        // 6. Monthly PAYE
        $monthlyPaye = round($annualTaxNet / $periodsPerYear, 2);

        return [
            'monthly_paye'              => $monthlyPaye,
            'annual_equivalent_income'  => $annualGross,
            'taxable_income'            => $annualTaxable,
            'annual_tax_before_rebates' => $annualTax,
            'annual_tax_after_rebates'  => $annualTaxNet,
            'medical_aid_credit_monthly' => $monthlyCreditEarnings,
        ];
    }

    /**
     * Calculate employee and employer UIF contributions.
     * Capped at 1% of R17,712/month each.
     */
    public function calculateUif(float $grossEarnings): array
    {
        $cappedEarnings = min($grossEarnings, self::UIF_MONTHLY_EARNINGS_CAP);
        $contribution   = round($cappedEarnings * self::UIF_RATE, 2);

        return [
            'employee' => $contribution,
            'employer' => $contribution,
        ];
    }

    /**
     * Calculate SDL (Skills Development Levy).
     * Employer pays 1% of leviable amount.
     */
    public function calculateSdl(float $grossEarnings): float
    {
        return round($grossEarnings * self::SDL_RATE, 2);
    }

    /**
     * Monthly medical aid tax credit based on member count.
     */
    public function medicalAidCredit(int $members): float
    {
        $members = max(1, $members);

        if ($members <= 2) {
            return $members * self::MEDICAL_CREDIT_FIRST_TWO;
        }

        return (2 * self::MEDICAL_CREDIT_FIRST_TWO) + (($members - 2) * self::MEDICAL_CREDIT_ADDITIONAL);
    }

    private function taxOnIncome(float $income): float
    {
        $brackets = self::BRACKETS;

        for ($i = count($brackets) - 1; $i >= 0; $i--) {
            [$lower, $base, $rate] = $brackets[$i];

            if ($income > $lower) {
                return $base + ($income - $lower) * $rate;
            }
        }

        return 0;
    }
}
