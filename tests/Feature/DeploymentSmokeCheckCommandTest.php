<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('runs deployment smoke checks without endpoint checks', function (): void {
    $exitCode = Artisan::call('app:deploy-smoke-check', [
        '--skip-http' => true,
    ]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('Deployment smoke checks passed.');
});
