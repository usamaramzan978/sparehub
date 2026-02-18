<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Request;

final class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(static function (Permission $permission): string {
                $name = $permission->name;

                return str_contains($name, '.') ? explode('.', $name, 2)[0] : $name;
            });

        return view('tenants.permissions.index', ['groups' => $permissions]);
    }

    public function show(Permission $permission): View
    {
        return view('tenants.permissions.show', ['permission' => $permission]);
    }
}
