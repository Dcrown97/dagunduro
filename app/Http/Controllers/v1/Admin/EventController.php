<?php

namespace App\Http\Controllers\v1\Admin;

use App\Exports\EventExport;
use App\Exports\RegistrantExport;
use App\Helpers\FileUploadHelper;
use App\Helpers\ProcessAuditLog;
use App\Helpers\UserMgtHelper;
use App\Http\Controllers\Controller;
use App\Models\Attendant;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Responser\JsonResponser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;
        $searchParam = $request->search_param;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        $currentUserInstance = UserMgtHelper::userInstance();
        $currentUserInstanceId = $currentUserInstance->id;
        try {

            $records = Event::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('title', 'like', '%' . $searchParam . '%');
            })
                ->when($status, function ($query) use ($status) {
                    return $query->where('status', $status);
                })
                ->when($dateSearchParams, function ($query) use ($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);

                    // Filter events that fall between the provided start_date and end_date
                    return $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate]);
                    });
                })->withCount('registrants');

            if ($request->export == true) {
                $records = $records->orderBy('created_at', 'desc')->get();
                return Excel::download(new EventExport($records), 'events.xlsx');
            } else {
                $records = $records->orderBy('created_at', 'desc')->paginate(10);
                return JsonResponser::send(false, 'Record found successfully!', $records, 200);
            }
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            $validateRequest = $this->validateEventRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;

            $request->validate([
                'video' => 'required|mimes:mp4,mp3,avi,mov,wmv|max:102400', // Max 100MB
            ]);

            $bannerUrl = FileUploadHelper::singleBinaryFileUpload($request->banner, "Events");
            $videoUrl = FileUploadHelper::uploadVideo($request->video, "Events");

            // Handle background image upload
            // if ($request->hasFile('banner')) {
            //     $file = $request->file('banner');
            //     $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
            //     $bannerFilePath = $file->storeAs('events', $fileName, 'public'); // Save file to the public disk

            //     // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
            //     $bannerFileExtension = strtolower($file->getClientOriginalExtension());

            //     // Validate the file type (pdf, mp3, mp4)
            //     if (!in_array($bannerFileExtension, ['png', 'jpg', 'jpeg'])) {
            //         return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
            //     }
            // }

            $createRecord = Event::create([
                'user_id' => $currentUserInstanceId,
                'event_category_id' => $request->event_category_id,
                'event_type_id' => $request->event_type_id,
                'title' => $request->title,
                'address' => $request->address,
                'start_date' => Carbon::parse($request->start_date),
                'end_date' => Carbon::parse($request->end_date),
                'start_date_time' => $request->start_date_time,
                'end_date_time' => $request->end_date_time,
                // 'banner' => $bannerFilePath ?? null, //$bannerBase64,
                'banner' => $bannerUrl,
                'video' => $videoUrl,
                'description' => $request->description,
                'recurring' => $request->recurring,
                'status' => $request->status //Published
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstanceId,
                'action_id' => $createRecord->id,
                'action_type' => "Models\Event",
                'action' => "Create",
                'log_name' => "Event created successfully",
                'description' => "Event created successfully by {$currentUserInstance->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Event created successfully", $createRecord, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;
            $searchParam = $request->search_param;
            $record = Event::where('id', $id)
                ->with(['registrants' => function ($query) use ($searchParam) {
                    // Apply the search to the registrants table, e.g., by name, email, etc.
                    return $query->when($searchParam, function ($query) use ($searchParam) {
                        return $query->where('name', 'like', '%' . $searchParam . '%')
                            ->orWhere('email', 'like', '%' . $searchParam . '%');
                    });
                }])
                ->first();

            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }

            if ($request->export == true) {
                // Get the registrants (filtered based on search)
                $registrants = $record->registrants;
                // Export the registrants only
                return Excel::download(new RegistrantExport($registrants), 'registrants.xlsx');
            } else {
                return JsonResponser::send(false, 'Record found successfully', $record, 200);
            }
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            $validateRequest = $this->validateEventRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;

            $request->validate([
                'video' => 'required|mimes:mp4,avi,mov,wmv|max:102400', // Max 100MB
            ]);

            
            $eventExist = Event::where('id', $request->id)->first();
            
            if (is_null($eventExist)) {
                return JsonResponser::send(true, "Event not found Exist", [], 400);
            }
            
            $bannerUrl = $eventExist->banner;
            if (isset($request->banner)) {
                $bannerImage = $request->banner;
                $imageKey = 'Events';
                $bannerUrl = FileUploadHelper::singleBinaryFileUpload($bannerImage, $imageKey);
            }

            $videoUrl = $eventExist->video;
            if (isset($request->video)) {
                $video = $request->video;
                $videoKey = 'Events';
                $videoUrl = FileUploadHelper::uploadVideo($video, $videoKey);
            }

            // Handle background image upload
            // if ($request->hasFile('banner')) {

            //     // Remove the previous background image if it exists
            //     if (!is_null($eventExist->banner) && Storage::disk('public')->exists($eventExist->banner)) {
            //         Storage::disk('public')->delete($eventExist->banner);
            //     }

            //     $file = $request->file('banner');
            //     $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
            //     $bannerFilePath = $file->storeAs('events', $fileName, 'public'); // Save file to the public disk

            //     // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
            //     $bannerFileExtension = strtolower($file->getClientOriginalExtension());

            //     // Validate the file type (pdf, mp3, mp4)
            //     if (!in_array($bannerFileExtension, ['png', 'jpg', 'jpeg'])) {
            //         return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
            //     }

            //     // Update the resource with the new file
            //     $eventExist->banner = $bannerFilePath;
            // }

            $eventExist->update([
                'event_category_id' => $request->event_category_id,
                'event_type_id' => $request->event_type_id,
                'title' => $request->title,
                'address' => $request->address,
                'start_date' => Carbon::parse($request->start_date),
                'end_date' => Carbon::parse($request->end_date),
                'start_date_time' => $request->start_date_time,
                'end_date_time' => $request->end_date_time,
                'description' => $request->description,
                'banner' => $bannerUrl,
                'video' => $videoUrl,
                'recurring' => $request->recurring,
                'status' => $request->status //Published
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstanceId,
                'action_id' => $eventExist->id,
                'action_type' => "Models\Event",
                'action' => "Create",
                'log_name' => "Event updated successfully",
                'description' => "Event updated successfully by {$currentUserInstance->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            $newEvent = Event::where('id', $request->id)->with('registrants')->first();

            DB::commit();
            return JsonResponser::send(false, "Event updated successfully", $newEvent, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function registerAttendee(Request $request)
    {
        try {
            DB::beginTransaction();

            $validateRequest = $this->validateAttendeeRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;

            $attendeeExist = Attendant::where('event_id', $request->event_id)->where('email', $request->email)->first();

            if (!is_null($attendeeExist)) {
                return JsonResponser::send(true, "Attendee already exist for this event", [], 400);
            }

            $createRecord = Attendant::create([
                'user_id' => $currentUserInstanceId,
                'event_id' => $request->event_id,
                'name' => $request->name,
                'email' => $request->email,
                'country_code' => $request->country_code,
                'phoneno' => $request->phoneno,
                'home_address' => $request->home_address,
                'occupation' => $request->occupation,
                'status' => 'Active' //$request->status
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstanceId,
                'action_id' => $createRecord->id,
                'action_type' => "Models\Attendant",
                'action' => "Create",
                'log_name' => "Attendee registered successfully",
                'description' => "Attendee registered successfully by {$currentUserInstance->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Attendee registered successfully", $createRecord, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $user = UserMgtHelper::userInstance();
            $userId = $user->id;

            $eventExist = Event::where('id', $id)->first();

            if (is_null($eventExist)) {
                return JsonResponser::send(true, "Event does not exist", [], 400);
            }

            // Delete the resource file from storage (if it exists)
            if (!is_null($eventExist->banner)) {
                Storage::disk('public')->delete($eventExist->banner);
            }


            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $eventExist->id,
                'action_type' => "Models\Event",
                'action' => "Delete",
                'log_name' => "Record deleted successfully",
                'description' => "Record deleted successfully by {$user->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            $eventExist->delete();

            DB::commit();
            return JsonResponser::send(false, "Event deleted successfully", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    private function validateEventRequest($request)
    {
        $rules = [
            'event_category_id' => 'required',
            'event_type_id' => 'required',
            'title' => 'required',
            'address' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'start_date_time' => 'required',
            'end_date_time' => 'required',
            // 'banner' => 'required',
            // 'banner' => 'required|image|mimes:jpeg,png,jpg',
            'description' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }

    private function validateAttendeeRequest($request)
    {
        $rules = [
            'event_id' => 'required',
            'name' => 'required',
            'email' => 'required',
            'phoneno' => 'required'
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
}
