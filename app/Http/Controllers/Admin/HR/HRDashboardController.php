<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\EmployeeCertification;
use App\Models\HR\EmployeeProfile;
use App\Models\HR\PromotionRequest;
use App\Models\HR\ResignationRequest;
use App\Models\HR\TerminationRequest;
use App\Models\User;

class HRDashboardController extends Controller
{
    public function index()
    {
        $this->authorize('view-staff');

        $stats = [
            'total_active'        => User::active()->excludeAdmin()->count(),
            'on_probation'        => EmployeeProfile::where('probation_end_date', '>=', now())->count(),
            'on_notice'           => User::onNoticePeriod()->count(),
            'contracts_expiring'  => EmployeeProfile::whereNotNull('contract_end_date')
                                        ->whereBetween('contract_end_date', [now(), now()->addDays(30)])
                                        ->count(),
            'certs_expiring'      => EmployeeCertification::whereNotNull('expiry_date')
                                        ->whereBetween('expiry_date', [now(), now()->addDays(60)])
                                        ->count(),
        ];

        $pendingPromotions = PromotionRequest::with('employee', 'proposedBy')
            ->whereNotIn('status', ['approved', 'rejected', 'withdrawn'])
            ->latest()
            ->take(5)
            ->get();

        $pendingResignations = ResignationRequest::with('employee', 'manager')
            ->whereNotIn('status', ['approved', 'rejected', 'withdrawn'])
            ->latest()
            ->take(5)
            ->get();

        $activeTerminations = TerminationRequest::with('employee')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->take(5)
            ->get();

        $recentJoinees = User::with('profile', 'department')
            ->excludeAdmin()
            ->active()
            ->latest()
            ->take(5)
            ->get();

        return view('admin.hr.dashboard', compact(
            'stats', 'pendingPromotions', 'pendingResignations',
            'activeTerminations', 'recentJoinees'
        ));
    }
}
