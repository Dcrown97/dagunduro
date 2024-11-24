<?php

namespace App\Http\Controllers\v1\Admin;

use App\Exports\TransactionExport;
use App\Helpers\UserMgtHelper;
use App\Http\Controllers\Controller;
use App\Models\Attendant;
use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Responser\JsonResponser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;
        $donationType = $request->donation_type;
        $searchParam = $request->search_param;

        $currentUserInstance = UserMgtHelper::userInstance();
        $currentUserInstanceId = $currentUserInstance->id;

        $dateFilter = $request->date_filter;

        if ($dateFilter === "1day") {
            $carbonDateFilter = Carbon::now()->subdays(1);
        } elseif ($dateFilter === "7days") {
            $carbonDateFilter = Carbon::now()->subdays(7);
        } elseif ($dateFilter === "30days") {
            $carbonDateFilter = Carbon::now()->subdays(30);
        } elseif ($dateFilter === "3months") {
            $carbonDateFilter = Carbon::now()->subMonths(3);
        } elseif ($dateFilter === "12months") {
            $carbonDateFilter = Carbon::now()->subMonths(12);
        } elseif ($dateFilter === "this_year") {
            $carbonDateFilter = Carbon::now()->startOfYear();
        } else {
            $carbonDateFilter = false;
        }

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {

            $eventCount = Event::count();

            $donations = Transaction::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->sum('amount');

            $totalMembers = Attendant::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->count();

            $months = [
                'JAN',
                'FEB',
                'MAR',
                'APR',
                'MAY',
                'JUN',
                'JUL',
                'AUG',
                'SEP',
                'OCT',
                'NOV',
                'DEC'
            ];

            // Fetch the member counts grouped by month
            $members = Attendant::select(DB::raw("COUNT(id) as count"), DB::raw("MONTH(created_at) as month"))
                ->groupBy(DB::raw("MONTH(created_at)"))
                ->whereYear('created_at', date('Y')) // Filter for the current year
                ->pluck('count', 'month');

            // Initialize the array with months as keys and 0 as default values
            $membersData = array_fill_keys($months, 0);

            // Fill in the actual counts for each month
            foreach ($members as $month => $count) {
                // Convert the month number to index (month - 1 to match array)
                $monthName = $months[$month - 1];
                $membersData[$monthName] = $count;
            }

            // Financial Summary by Quarter
            $financialReport = Transaction::select(
                DB::raw('QUARTER(created_at) as quarter'),
                DB::raw('SUM(CASE WHEN donation_type = "Tithe" THEN amount ELSE 0 END) as total_tithe'),
                DB::raw('SUM(CASE WHEN donation_type = "Offering" THEN amount ELSE 0 END) as total_offering'),
                DB::raw('SUM(CASE WHEN donation_type = "Mission" THEN amount ELSE 0 END) as total_mission')
            )
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy(DB::raw('QUARTER(created_at)'))
                ->get();

            // Initialize financial report arrays for each quarter
            $quarters = [1, 2, 3, 4]; // Q1 to Q4
            $titheData = [0, 0, 0, 0];
            $offeringData = [0, 0, 0, 0];
            $missionData = [0, 0, 0, 0];

            // Populate financial report data for each quarter
            foreach ($financialReport as $report) {
                $quarterIndex = $report->quarter - 1; // Index for array (0-based)
                $titheData[$quarterIndex] = $report->total_tithe;
                $offeringData[$quarterIndex] = $report->total_offering;
                $missionData[$quarterIndex] = $report->total_mission;
            }

            $transactions = Transaction::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('name', 'like', '%' . $searchParam . '%');
            })
                ->when($status, function ($query) use ($status) {
                    return $query->where('payment_status', $status);
                })->when($donationType, function ($query) use ($donationType) {
                    return $query->where('donation_type', $donationType);
                })->when($dateSearchParams, function ($query) use ($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                });

            // Fetch upcoming events (assuming 'start_date' is the event's starting date)
            $upcomingEvents = Event::where('start_date', '>=', Carbon::now())
                ->orderBy('start_date', 'asc')  // Ensure events are ordered by date
                ->limit(5)  // Limit to, for example, 5 events
                ->get(['title', 'start_date_time', 'end_date_time', 'start_date', 'end_date']);  // Add any other fields you need

            if ($request->export == true) {
                $transactions = $transactions->orderBy('created_at', 'desc')->get();
                return Excel::download(new TransactionExport($transactions), 'transactions.xlsx');
            } else {
                $transactions = $transactions->orderBy('created_at', 'desc')->latest()->take;
                $data = [
                    "eventCount" => $eventCount,
                    "donations" => $donations,
                    "totalMembersCount" => $totalMembers,
                    "members" => $membersData, // Array of members count for each month
                    "financialReport" => [
                        "quarters" => $quarters,
                        "tithe" => $titheData,
                        "offering" => $offeringData,
                        "mission" => $missionData
                    ],
                    "transactions" => $transactions,
                    "upcomingEvents" => $upcomingEvents,  // Add upcoming events to the response
                ];
                return JsonResponser::send(false, 'Record found successfully!', $data, 200);
            }
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }
}
