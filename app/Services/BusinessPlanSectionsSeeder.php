<?php

namespace App\Services;

use App\Models\BusinessPlanSection;
use App\Models\Company;

/**
 * Idempotently seeds the standard business-plan sections for a company.
 *
 * Mirrors {@see FinancialStatementNotesSeeder}: existing sections (matched by
 * slug) keep their user-edited title and body; only the canonical ordering is
 * kept in step, and sections missing from a company are inserted.
 *
 * Bodies are written as prompts, not filler. A generated plan that quietly
 * ships boilerplate as if it were the company's own strategy is worse than one
 * that shows an obvious gap, so every default body says what belongs there and
 * is marked as placeholder text.
 */
class BusinessPlanSectionsSeeder
{
    /** Prefix marking a body the user has not written yet. */
    public const PLACEHOLDER_PREFIX = '[To be completed]';

    public function seed(Company $company): void
    {
        foreach (self::defaults() as $i => $section) {
            $model = BusinessPlanSection::firstOrCreate(
                ['company_id' => $company->id, 'slug' => $section['slug']],
                [
                    'section_number' => $i + 1,
                    'title' => $section['title'],
                    'body' => $section['body'],
                    'sort_order' => $i + 1,
                    'is_active' => true,
                    'include_statistics' => $section['include_statistics'] ?? false,
                ],
            );

            // Keep the canonical sequence for existing companies too, without
            // touching anything the user has written.
            $updates = [];
            if ((int) $model->sort_order !== $i + 1 || (int) $model->section_number !== $i + 1) {
                $updates['sort_order'] = $i + 1;
                $updates['section_number'] = $i + 1;
            }
            $expectedStats = $section['include_statistics'] ?? false;
            if ((bool) $model->include_statistics !== $expectedStats) {
                $updates['include_statistics'] = $expectedStats;
            }
            if ($updates !== []) {
                $model->update($updates);
            }
        }
    }

    /**
     * @return array<int, array{slug: string, title: string, body: string, include_statistics?: bool}>
     */
    public static function defaults(): array
    {
        $p = self::PLACEHOLDER_PREFIX . ' ';

        return [
            [
                'slug' => 'executive-summary',
                'title' => 'Executive Summary',
                'body' => $p . 'Summarise the business in a page: what it does, who it serves, what it '
                    . 'sells, how it makes money, and what it is asking for. Write this section last — it is a '
                    . 'summary of the plan, not an introduction to it.',
            ],
            [
                'slug' => 'company-overview',
                'title' => 'Company Overview',
                'body' => $p . 'Set out the legal name, entity type, registration and tax numbers, date of '
                    . 'incorporation, registered address and trading premises, ownership structure, and a short '
                    . 'history of the business to date.',
            ],
            [
                'slug' => 'products-and-services',
                'title' => 'Products and Services',
                'body' => $p . 'Describe what the business sells, how each line is priced, what it costs to '
                    . 'deliver, and what makes it hard for a competitor to copy.',
            ],
            [
                'slug' => 'market-analysis',
                'title' => 'Market Analysis',
                'body' => $p . 'Define the target market and its size, the customer segments served, the '
                    . 'trends shaping demand, and the regulatory environment. Cite the sources for any figures '
                    . 'quoted here.',
            ],
            [
                'slug' => 'competitive-landscape',
                'title' => 'Competitive Landscape',
                'body' => $p . 'Name the direct and indirect competitors, how each competes, and where this '
                    . 'business wins and loses against them.',
            ],
            [
                'slug' => 'marketing-and-sales',
                'title' => 'Marketing and Sales Strategy',
                'body' => $p . 'Explain how customers are acquired and retained: channels, pricing position, '
                    . 'sales process, cost of acquisition, and the expected conversion rates behind the revenue '
                    . 'forecast.',
            ],
            [
                'slug' => 'operations',
                'title' => 'Operations',
                'body' => $p . 'Describe how the product or service is actually delivered: premises, '
                    . 'equipment, suppliers, systems, capacity limits, and the operating cycle from order to cash.',
            ],
            [
                'slug' => 'management-and-team',
                'title' => 'Management and Team',
                'body' => $p . 'List the directors and key personnel, their relevant experience, the current '
                    . 'headcount, and the roles that still need to be filled.',
            ],
            [
                'slug' => 'financial-performance',
                'title' => 'Financial Performance',
                'body' => 'The tables below are derived from the accounting records for the period stated, on '
                    . 'the same basis as the annual financial statements. They report performance to date; they '
                    . 'are not a forecast.',
                'include_statistics' => true,
            ],
            [
                'slug' => 'financial-plan',
                'title' => 'Financial Plan and Projections',
                'body' => $p . 'Set out the forecast for the next three years — revenue, gross margin, '
                    . 'operating costs, capital expenditure and cash — together with the assumptions each figure '
                    . 'rests on. State the funding required, what it will be spent on, and how it will be repaid.',
            ],
            [
                'slug' => 'risks-and-mitigation',
                'title' => 'Risks and Mitigation',
                'body' => $p . 'Identify the principal risks to the business — commercial, operational, '
                    . 'financial, regulatory and key-person — and what is being done about each.',
            ],
        ];
    }
}
