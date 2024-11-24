<?php

namespace App\Http\Controllers\v1\Admin;

use App\Exports\BlogExport;
use App\Helpers\ProcessAuditLog;
use App\Helpers\UserMgtHelper;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Responser\JsonResponser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;
        $searchParam = $request->search_param;

        $currentUserInstance = UserMgtHelper::userInstance();
        $currentUserInstanceId = $currentUserInstance->id;
        try {

            $allPosts = Blog::count();
            $totalComments = Comment::count();
            $totalLikes = Blog::sum('like');
            $totalShare = Blog::sum('share');

            $records = Blog::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('title', 'like', '%' . $searchParam . '%');
            })
                ->when($status, function ($query) use ($status) {
                    return $query->where('status', $status);
                })->with('category:id,name');

            $comments = Comment::when($searchParam, function ($query) use ($searchParam) {
                return $query->whereRelation('blog', 'title', 'like', '%' . $searchParam . '%');
            })->orderBy('created_at', 'desc')
                ->take(5)->get();

            $comments->each(function ($comment) {
                $comment->created_at_human = Carbon::parse($comment->created_at)->diffForHumans();
            });

            if ($request->export == true) {
                $records = $records->orderBy('created_at', 'desc')->get();
                return Excel::download(new BlogExport($records), 'blog.xlsx');
            } else {
                $records = $records->orderBy('created_at', 'desc')->paginate(10);
                $data = [
                    "allPosts" => $allPosts,
                    "totalComments" => $totalComments,
                    "totalLikes" => $totalLikes,
                    "totalShare" => $totalShare,
                    "records" => $records,
                    "livecomments" => $comments,
                ];
                return JsonResponser::send(false, 'Record found successfully!', $data, 200);
            }
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            $validateRequest = $this->validateBlogRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;

            // Handle background image upload
            if ($request->hasFile('blog_banner')) {
                $file = $request->file('blog_banner');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
                $blogBannerFilePath = $file->storeAs('blogs', $fileName, 'public'); // Save file to the public disk

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $blogBannerFileExtension = strtolower($file->getClientOriginalExtension());

                // Validate the file type (pdf, mp3, mp4)
                if (!in_array($blogBannerFileExtension, ['png', 'jpg', 'jpeg'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
                }
            }

            if ($request->hasFile('author_image')) {
                $file = $request->file('author_image');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
                $authorImageFilePath = $file->storeAs('blogs', $fileName, 'public'); // Save file to the public disk

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $authorImageFileExtension = strtolower($file->getClientOriginalExtension());

                // Validate the file type (pdf, mp3, mp4)
                if (!in_array($authorImageFileExtension, ['png', 'jpg', 'jpeg'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
                }
            }

            $createRecord = Blog::create([
                'user_id' => $currentUserInstanceId,
                'blog_category_id' => $request->blog_category_id,
                'title' => $request->title,
                'author_name' => $request->author_name,
                'blog_banner' => $blogBannerFilePath ?? null,
                'author_image' => $authorImageFilePath ?? null,
                'description' => $request->description,
                'show_author' => $request->show_author,
                'allow_comments' => $request->allow_comments,
                'allow_share' => $request->allow_share,
                'allow_likes' => $request->allow_likes,
                'status' => $request->status
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstanceId,
                'action_id' => $createRecord->id,
                'action_type' => "Models\Blog",
                'action' => "Create",
                'log_name' => "Blog post created successfully",
                'description' => "Blog post created successfully by {$currentUserInstance->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Blog post created successfully", $createRecord, 200);
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
            $record = Blog::where('id', $id)->with('category:id,name')->first();

            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }

            // Format the created_at for the blog record itself
            $record->created_at_human = Carbon::parse($record->created_at)->diffForHumans();

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

            $validateRequest = $this->validateBlogRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();
            $currentUserInstanceId = $currentUserInstance->id;

            $blogExist = Blog::where('id', $request->id)->first();

            if (is_null($blogExist)) {
                return JsonResponser::send(true, "Blog does not found Exist", [], 400);
            }

            // Handle background image upload
            if ($request->hasFile('blog_banner')) {

                // Remove the previous background image if it exists
                if (!is_null($blogExist->blog_banner) && Storage::disk('public')->exists($blogExist->blog_banner)) {
                    Storage::disk('public')->delete($blogExist->blog_banner);
                }

                $file = $request->file('blog_banner');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
                $blogBannerFilePath = $file->storeAs('blogs', $fileName, 'public'); // Save file to the public disk

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $blogBannerFileExtension = strtolower($file->getClientOriginalExtension());

                // Validate the file type (pdf, mp3, mp4)
                if (!in_array($blogBannerFileExtension, ['png', 'jpg', 'jpeg'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
                }

                // Update the resource with the new file
                $blogExist->blog_banner = $blogBannerFilePath;
            }

            if ($request->hasFile('author_image')) {

                // Remove the previous background image if it exists
                if (!is_null($blogExist->author_image) && Storage::disk('public')->exists($blogExist->author_image)) {
                    Storage::disk('public')->delete($blogExist->author_image);
                }

                $file = $request->file('author_image');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Rename the file uniquely
                $authorImageFilePath = $file->storeAs('blogs', $fileName, 'public'); // Save file to the public disk

                // Get the file extension (e.g., 'pdf', 'mp3', 'mp4')
                $authorImageFileExtension = strtolower($file->getClientOriginalExtension());

                // Validate the file type (pdf, mp3, mp4)
                if (!in_array($authorImageFileExtension, ['png', 'jpg', 'jpeg'])) {
                    return JsonResponser::send(true, "Invalid file type. Only PNG, JP, or JPEG files are allowed.", [], 400);
                }

                // Update the resource with the new file
                $blogExist->author_image = $authorImageFilePath;
            }

            $blogExist->update([
                'blog_category_id' => $request->blog_category_id,
                'title' => $request->title,
                'author_name' => $request->author_name,
                'description' => $request->description,
                'show_author' => $request->show_author,
                'allow_comments' => $request->allow_comments,
                'allow_share' => $request->allow_share,
                'allow_likes' => $request->allow_likes,
                'status' => $request->status
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstanceId,
                'action_id' => $blogExist->id,
                'action_type' => "Models\Blog",
                'action' => "Create",
                'log_name' => "Blog post updated successfully",
                'description' => "Blog post updated successfully by {$currentUserInstance->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            $newBlog = Blog::where('id', $request->id)->first();

            DB::commit();
            return JsonResponser::send(false, "Blog post updated successfully", $newBlog, 200);
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

            $blogExist = Blog::where('id', $id)->first();

            if (is_null($blogExist)) {
                return JsonResponser::send(true, "Blog does not exist", [], 400);
            }

            // Delete the resource file from storage (if it exists)
            if (!is_null($blogExist->blog_banner)) {
                Storage::disk('public')->delete($blogExist->blog_banner);
            }

            if (!is_null($blogExist->author_image)) {
                Storage::disk('public')->delete($blogExist->author_image);
            }

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $blogExist->id,
                'action_type' => "Models\Blog",
                'action' => "Delete",
                'log_name' => "Record deleted successfully",
                'description' => "Record deleted successfully by {$user->name}",
            ];
            ProcessAuditLog::storeAuditLog($dataToLog);

            $blogExist->delete();

            DB::commit();
            return JsonResponser::send(false, "Blog deleted successfully", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    private function validateBlogRequest($request)
    {
        $rules = [
            'blog_category_id' => 'required',
            'title' => 'required',
            'author_name' => 'required',
            // 'blog_banner' => 'required',
            'author_image' => 'required',
            'description' => 'required',
            'status' => 'required'
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
}
