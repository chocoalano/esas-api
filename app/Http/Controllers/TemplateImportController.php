<?php

namespace App\Http\Controllers;
use App\Repositories\Interfaces\AdministrationApp\ScheduleAttendanceInterface;
use Illuminate\Http\Request;

class TemplateImportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function schedule(Request $request)
    {
        $company = $request->input("company_id");
        $departement = $request->input("departement_id");
        return app(ScheduleAttendanceInterface::class)->template($company, $departement);
    }
}
