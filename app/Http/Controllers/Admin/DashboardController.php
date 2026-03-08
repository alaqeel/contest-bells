<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminReportService $report) {}

    public function index(): View
    {
        $summary = $this->report->summary();

        return view('admin.dashboard', compact('summary'));
    }
}
