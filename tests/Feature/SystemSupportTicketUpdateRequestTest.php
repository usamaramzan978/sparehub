<?php

declare(strict_types=1);

use App\Http\Requests\System\SupportTicketUpdateRequest;
use Illuminate\Support\Facades\Validator;

it('validates required support ticket update fields for system user', function (): void {
    $request = new SupportTicketUpdateRequest();

    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('title', 'description', 'priority', 'status');
});

it('allows valid system support ticket update payload with reply', function (): void {
    $request = new SupportTicketUpdateRequest();

    $validator = Validator::make([
        'title' => 'Updated issue title',
        'description' => 'Detailed updated issue description.',
        'priority' => 'high',
        'status' => 'in_progress',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});
