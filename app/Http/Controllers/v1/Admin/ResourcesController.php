<?php

namespace App\Http\Controllers\v1\Admin;

use App\Exports\ResourcesExport;
use App\Helpers\ProcessAuditLog;
use App\Helpers\UserMgtHelper;
use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Responser\JsonResponser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ResourcesController extends Controller
{
    public function index(Request $request)
    {
        $resource_type = $request->resource_type;
        $searchParam = $request->search_param;

        $currentUserInstance = UserMgtHelper::userInstance();
        $currentUserInstanceId = $currentUserInstance->id;
        try {

            $records = Resource::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('title', 'like', '%' . $searchParam . '%');
            })
                ->when($resource_type, function ($query) use ($resource_type) {
                    return $query->where('resource_type', $resource_type);
                });

            if ($request->export == true) {
                $records = $records->orderBy('created_at', 'desc')->get();
                return Excel::download(new ResourcesExport($records), 'resources.xlsx');
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
            $validateRequest = $this->validateResourcesRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;

            // Handle background image upload
            if ($request->hasFile('background_image')) {
                $file = $request->file('background_image');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
                $backgroundFilePath = $file->storeAs('resources', $fileName, 'public'); // Save file to the public disk

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $backgroundFileExtension = strtolower($file->getClientOriginalExtension());

                // Validate the file type (pdf, mp3, mp4)
                if (!in_array($backgroundFileExtension, ['png', 'jpg', 'jpeg'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
                }
            }

            // Handle file upload
            if ($request->hasFile('resource_file')) {
                $file = $request->file('resource_file');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
                $filePath = $file->storeAs('resources', $fileName, 'public'); // Save file to the public disk

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $fileExtension = strtolower($file->getClientOriginalExtension());

                // Validate the file type (pdf, mp3, mp4)
                if (!in_array($fileExtension, ['pdf', 'mp3', 'mp4'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PDF, MP3, or MP4 files are allowed.", [], 400);
                }
            }

            $createRecord = Resource::create([
                'user_id' => $currentUserInstanceId,
                'resource_category_id' => $request->resource_category_id,
                'title' => $request->title,
                'resource_file' => $filePath ?? null, // Store file path
                'background_image' => $backgroundFilePath ?? null, // Store file path
                'author' => $request->author,
                'file_type' => $fileExtension ?? null, // Save the file extension (pdf, mp3, mp4)
                'resource_type' => $request->resource_type,
                'status' => $request->status
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstanceId,
                'action_id' => $createRecord->id,
                'action_type' => "Models\Resource",
                'action' => "Create",
                'log_name' => "Resources created successfully",
                'description' => "Resources created successfully by {$currentUserInstance->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Resources created successfully", $createRecord, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function show($id)
    {
        try {
            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;
            $record = Resource::where('id', $id)->first();

            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }
            return JsonResponser::send(false, 'Record found successfully', $record, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validate request
            $validateRequest = $this->validateResourcesRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;

            // Check if resource exists
            $resourcesExist = Resource::find($request->id);
            if (is_null($resourcesExist)) {
                return JsonResponser::send(true, "Resource not found", [], 400);
            }

            // Handle background image upload
            if ($request->hasFile('background_image')) {

                // Remove the previous background image if it exists
                if (!is_null($resourcesExist->background_image) && Storage::disk('public')->exists($resourcesExist->background_image)) {
                    Storage::disk('public')->delete($resourcesExist->background_image);
                }

                $file = $request->file('background_image');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
                $backgroundFilePath = $file->storeAs('resources', $fileName, 'public'); // Save file to the public disk

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $backgroundFileExtension = strtolower($file->getClientOriginalExtension());

                // Validate the file type (pdf, mp3, mp4)
                if (!in_array($backgroundFileExtension, ['png', 'jpg', 'jpeg'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
                }

                // Update the resource with the new file
                $resourcesExist->background_image = $backgroundFilePath;
            }

            // Handle file upload
            if ($request->hasFile('resource_file')) {

                // Remove the previous resource file if it exists
                if (!is_null($resourcesExist->resource_file) && Storage::disk('public')->exists($resourcesExist->resource_file)) {
                    Storage::disk('public')->delete($resourcesExist->resource_file);
                }

                $file = $request->file('resource_file');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file
                $filePath = $file->storeAs('resources', $fileName, 'public'); // Save file to public storage

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $fileExtension = strtolower($file->getClientOriginalExtension());

                // Validate file type
                if (!in_array($fileExtension, ['pdf', 'mp3', 'mp4'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PDF, MP3, or MP4 files are allowed.", [], 400);
                }

                // Update the resource with the new file
                $resourcesExist->resource_file = $filePath;
                $resourcesExist->file_type = $fileExtension;
            }

            // Update other fields
            $resourcesExist->resource_category_id = $request->resource_category_id;
            $resourcesExist->title = $request->title;
            $resourcesExist->author = $request->author;
            $resourcesExist->resource_type = $request->resource_type;
            $resourcesExist->status = $request->status;

            // Save the updated resource
            $resourcesExist->save();

            // Log the update action
            $dataToLog = [
                'causer_id' => $currentUserInstanceId,
                'action_id' => $resourcesExist->id,
                'action_type' => "Models\Resource",
                'action' => "Update",
                'log_name' => "Resource updated successfully",
                'description' => "Resource updated successfully by {$currentUserInstance->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            
            // Check if resource exists
            $resourcesExist = Resource::find($request->id);

            // Return the updated resource
            return JsonResponser::send(false, "Resource updated successfully", $resourcesExist, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function download($id)
    {
        $resourceExist = Resource::where('id', $id)->first();
        if (is_null($resourceExist)) {
            return JsonResponser::send(true, "Resource does not exist", [], 400);
        }
        $filePath = storage_path('app/public/' . $resourceExist->resource_file);

        if (file_exists($filePath)) {
            $resourceExist->update([
                "resourceExist" => $resourceExist + 1
            ]);
            return response()->download($filePath);
        } else {
            return abort(404, 'File not found');
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $user = UserMgtHelper::userInstance();
            $userId = $user->id;

            $resourceExist = Resource::where('id', $id)->first();

            if (is_null($resourceExist)) {
                return JsonResponser::send(true, "Resource does not exist", [], 400);
            }

            // Delete the resource file from storage (if it exists)
            if (!is_null($resourceExist->resource_file)) {
                Storage::disk('public')->delete($resourceExist->resource_file);
            }

            if (!is_null($resourceExist->background_image)) {
                Storage::disk('public')->delete($resourceExist->background_image);
            }

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $resourceExist->id,
                'action_type' => "Models\Resource",
                'action' => "Delete",
                'log_name' => "Record deleted successfully",
                'description' => "Record deleted successfully by {$user->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            $resourceExist->delete();

            DB::commit();
            return JsonResponser::send(false, "Resource deleted successfully", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    private function validateResourcesRequest($request)
    {
        $rules = [
            'resource_category_id' => 'required',
            'title' => 'required',
            'resource_file' => 'required',
            'author' => 'required',
            'resource_type' => 'required',
            'status' => 'required'
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
}
