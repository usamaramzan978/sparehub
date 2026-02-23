<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\ActivityTimeline\ListRecentActivitiesAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class ActivityTimelineController extends Controller
{
    public function __invoke(ListRecentActivitiesAction $action): View
    {
        return view('tenants.activity-timeline.index', [
            'activities' => $action->handle(),
        ]);
    }
}
