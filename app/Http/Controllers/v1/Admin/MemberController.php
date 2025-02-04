<?php

namespace App\Http\Controllers\v1\Admin;

use App\Exports\MemberExport;
use App\Helpers\UserMgtHelper;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use App\Responser\JsonResponser;
use Maatwebsite\Excel\Facades\Excel;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;
        $department_id = $request->department_id;
        $searchParam = $request->search_param;

        $currentUserInstance = UserMgtHelper::userInstance();
        $currentUserInstanceId = $currentUserInstance->id;
        try {

            $records = Member::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('full_name', 'like', '%' . $searchParam . '%');
            })
                ->when($status, function ($query) use ($status) {
                    return $query->where('status', $status);
                })->when($department_id, function ($query) use ($department_id) {
                    return $query->where('department_id', $department_id);
                })->with('departments:id,name');

            if ($request->export == true) {
                $records = $records->orderBy('created_at', 'desc')->get();
                return Excel::download(new MemberExport($records), 'member.xlsx');
            } else {
                $records = $records->orderBy('created_at', 'desc')->paginate(10);
                return JsonResponser::send(false, 'Record found successfully!', $records, 200);
            }
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }
}
