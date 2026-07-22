<?php

namespace App\Console\Commands;

use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ScheduleMonthEndWorkflow extends Command
{
    protected $signature = 'temporal:month-end
        {--run : Run month-end immediately for all companies instead of scheduling}
        {--backdate : Run the backdate depreciation workflow to catch up missed months}
        {--company= : Limit to a single company id}
        {--month= : Month to process (YYYY-MM), defaults to the previous month}
        {--schedule : Create a Temporal schedule for automatic monthly runs}
        {--remove-schedule : Remove the existing Temporal schedule}';

    protected $description = 'Run or schedule the Temporal month-end closing workflow (depreciation, amortisation, ROU, ECL).';

    public function handle(): int
    {
        if ($this->option('remove-schedule')) {
            return $this->removeSchedule();
        }

        if ($this->option('schedule')) {
            return $this->createSchedule();
        }

        if ($this->option('backdate')) {
            return $this->runBackdate();
        }

        return $this->runNow();
    }

    private function runNow(): int
    {
        $monthEnd = $this->option('month')
            ? CarbonImmutable::createFromFormat('Y-m', (string) $this->option('month'))->endOfMonth()
            : now()->subMonthNoOverflow()->endOfMonth()->toImmutable();

        $companies = $this->companies();
        $started = 0;

        foreach ($companies as $company) {
            $workflowId = "month-end-{$company->id}-{$monthEnd->format('Ym')}";

            if ($this->temporal('workflow', 'start',
                '--type', 'MonthEndClose',
                '--task-queue', 'month-end',
                '--workflow-id', $workflowId,
                '--input', (string) $company->id,
                '--input', json_encode($monthEnd->format('Y-m-d')),
            )) {
                $started++;
                $this->info("Started workflow {$workflowId} for {$company->name}");
            }
        }

        $this->info("Queued month-end workflows for {$started} company(ies) — period {$monthEnd->format('M Y')}.");
        $this->info('Monitor progress at http://localhost:8233');

        return self::SUCCESS;
    }

    private function runBackdate(): int
    {
        $companies = $this->companies();

        foreach ($companies as $company) {
            $workflowId = 'backdate-depreciation-'.$company->id.'-'.now()->format('YmdHis');

            if ($this->temporal('workflow', 'start',
                '--type', 'BackdateDepreciation',
                '--task-queue', 'month-end',
                '--workflow-id', $workflowId,
                '--input', (string) $company->id,
            )) {
                $this->info("Started backdate workflow {$workflowId} for {$company->name}");
            }
        }

        $this->info('Monitor progress at http://localhost:8233');

        return self::SUCCESS;
    }

    private function createSchedule(): int
    {
        $companies = $this->companies();

        foreach ($companies as $company) {
            $scheduleId = "month-end-{$company->id}";

            if ($this->temporal('schedule', 'create',
                '--schedule-id', $scheduleId,
                '--cron', '0 2 1 * *',
                '--workflow-type', 'MonthEndClose',
                '--task-queue', 'month-end',
                '--input', (string) $company->id,
                '--input', json_encode('auto'),
            )) {
                $this->info("Created schedule {$scheduleId} for {$company->name} (runs 1st of month at 02:00)");
            }
        }

        return self::SUCCESS;
    }

    private function removeSchedule(): int
    {
        $companies = $this->companies();

        foreach ($companies as $company) {
            $scheduleId = "month-end-{$company->id}";

            if ($this->temporal('schedule', 'delete',
                '--schedule-id', $scheduleId,
                '--yes',
            )) {
                $this->info("Removed schedule {$scheduleId}");
            }
        }

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, Company> */
    private function companies()
    {
        $query = Company::query();
        if ($this->option('company')) {
            $query->where('id', (int) $this->option('company'));
        }

        return $query->get();
    }

    private function temporalBin(): string
    {
        $paths = [
            base_path('bin/temporal'),
            $_SERVER['HOME'].'/.temporalio/bin/temporal',
            '/usr/local/bin/temporal',
        ];

        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return 'temporal';
    }

    private function temporal(string ...$args): bool
    {
        $bin = $this->temporalBin();
        $address = config('temporal.address', 'localhost:7233');

        $process = new Process([$bin, ...$args, '--address', $address]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            $stderr = $process->getErrorOutput();
            if (str_contains($stderr, 'already exists')) {
                $this->warn("Already exists — skipping: {$args[1]} {$args[2]}");
                return false;
            }
            $this->error("Temporal CLI failed: {$stderr}");
            return false;
        }

        $this->line($process->getOutput());
        return true;
    }
}
