<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class DeploySmokeCheckCommand extends Command
{
    protected $signature = 'app:deploy-smoke-check
        {--base-url= : Base URL for endpoint checks}
        {--timeout=5 : Timeout in seconds for HTTP checks}
        {--skip-http : Skip endpoint smoke checks}';

    protected $description = 'Run deployment smoke checks (db, cache, queue, endpoints).';

    public function handle(): int
    {
        try {
            $this->checkDatabase();
            $this->checkCache();
            $this->checkQueue();

            if (! $this->option('skip-http')) {
                $this->checkEndpoints();
            }
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Deployment smoke checks passed.');

        return self::SUCCESS;
    }

    private function checkDatabase(): void
    {
        DB::connection()->getPdo();
        $this->line('OK: Database connection');
    }

    private function checkCache(): void
    {
        $key = 'deploy_smoke_check:'.Str::uuid()->toString();
        Cache::put($key, 'ok', now()->addMinute());
        $value = Cache::get($key);
        Cache::forget($key);

        if ($value !== 'ok') {
            throw new RuntimeException('Cache read/write check failed.');
        }

        $this->line('OK: Cache read/write');
    }

    private function checkQueue(): void
    {
        $connection = (string) config('queue.default');
        Queue::connection($connection)->size();
        $this->line(sprintf('OK: Queue connection (%s)', $connection));
    }

    private function checkEndpoints(): void
    {
        $baseUrl = $this->resolveBaseUrl();
        $timeout = max(1, (int) $this->option('timeout'));

        $healthResponse = Http::timeout($timeout)->get($baseUrl.'/up');
        if (! $healthResponse->successful()) {
            throw new RuntimeException(sprintf('Health endpoint failed: %s/up returned %d', $baseUrl, $healthResponse->status()));
        }
        $this->line(sprintf('OK: Endpoint %s/up', $baseUrl));

        $homeResponse = Http::timeout($timeout)->get($baseUrl.'/');
        if ($homeResponse->serverError()) {
            throw new RuntimeException(sprintf('Home smoke check failed: %s/ returned %d', $baseUrl, $homeResponse->status()));
        }
        $this->line(sprintf('OK: Endpoint %s/', $baseUrl));
    }

    private function resolveBaseUrl(): string
    {
        $optionBaseUrl = mb_trim((string) $this->option('base-url'));
        if ($optionBaseUrl !== '') {
            return mb_rtrim($optionBaseUrl, '/');
        }

        $configBaseUrl = mb_trim((string) config('app.url'));
        if ($configBaseUrl !== '') {
            return mb_rtrim($configBaseUrl, '/');
        }

        return 'http://127.0.0.1';
    }
}
