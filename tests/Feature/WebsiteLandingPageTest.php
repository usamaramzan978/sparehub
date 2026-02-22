<?php

declare(strict_types=1);

test('landing page is publicly accessible and shows sparehub messaging', function (): void {
    $response = $this->get(route('website.landing'));

    $response->assertSuccessful();
    $response->assertSee('SpareHub', false);
    $response->assertSee('Multi-Tenant ERP + Workshop SaaS', false);
});
