<?php

namespace App\Http\Controllers;

use App\Enums\BatchStatus;
use App\Enums\UserRole;
use App\Models\InternshipBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $canViewBatchDashboard = in_array($request->user()->role, [UserRole::ADMIN, UserRole::HR], true);

        $activeBatch = $canViewBatchDashboard
            ? InternshipBatch::query()
                ->where('status', BatchStatus::ACTIVE)
                ->orderByDesc('start_date')
                ->first()
            : null;

        if ($activeBatch) {
            return redirect()->route('batches.show', $activeBatch);
        }

        if ($canViewBatchDashboard) {
            return redirect()->route('batches.index');
        }

        return Inertia::render('Dashboard');
    }
}
