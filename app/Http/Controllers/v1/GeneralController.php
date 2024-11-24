<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Attendant;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Comment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\Member;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Responser\JsonResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class GeneralController extends Controller
{
    // This is general controller
    // Events
    public function eventTypes()
    {
        try {
            $reocrd = EventType::orderBy('created_at', 'desc')->get();
            if ($reocrd->isEmpty()) {
                return JsonResponser::send(true, 'Record Not Found', null, 404);
            }
            return JsonResponser::send(false, 'Record found! successfully', $reocrd, 200);
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function eventCategories()
    {
        try {
            $reocrd = EventCategory::orderBy('created_at', 'desc')->get();
            if ($reocrd->isEmpty()) {
                return JsonResponser::send(true, 'Record Not Found', null, 404);
            }
            return JsonResponser::send(false, 'Record found! successfully', $reocrd, 200);
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function allEvents(Request $request)
    {
        $searchParam = $request->search_param;
        $category = $request->event_category_id;
        $type = $request->event_type_id;
        $sort_by = $request->sort_by;

        try {

            $records = Event::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('title', 'like', '%' . $searchParam . '%');
            })
                ->when($category, function ($query) use ($category) {
                    return $query->where('event_category_id', $category);
                })
                ->when($type, function ($query) use ($type) {
                    return $query->where('event_type_id', $type);
                })
                ->when($sort_by === "date_ascending", function ($query) {
                    return $query->orderBy('created_at', 'ASC');
                })->when($sort_by === "date_descending", function ($query) {
                    return $query->orderBy('created_at', 'DESC');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return JsonResponser::send(false, 'Record found! successfully', $records, 200);
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function showEvent($id)
    {
        try {
            // Fetch the current event by ID
            $record = Event::where('id', $id)->first();
            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }
            // Fetch the 4 latest events excluding the current event
            $similarEvents = Event::where('id', '!=', $id)
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->get();
            // Package both the main event and similar events
            $data = [
                'event' => $record,
                'similar_events' => $similarEvents
            ];
            return JsonResponser::send(false, 'Record found successfully', $data, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
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

            $attendeeExist = Attendant::where('event_id', $request->event_id)->where('email', $request->email)->first();

            if (!is_null($attendeeExist)) {
                return JsonResponser::send(true, "Attendee already exist for this event", [], 400);
            }

            $createRecord = Attendant::create([
                'event_id' => $request->event_id,
                'name' => $request->name,
                'email' => $request->email,
                'country_code' => $request->country_code,
                'phoneno' => $request->phoneno,
                'home_address' => $request->home_address,
                'occupation' => $request->occupation,
                'status' => 'Active' //$request->status
            ]);

            DB::commit();
            return JsonResponser::send(false, "Attendee created successfully", $createRecord, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    // Blogs
    public function blogCategories()
    {
        try {
            $reocrd = BlogCategory::orderBy('created_at', 'desc')->get();
            if ($reocrd->isEmpty()) {
                return JsonResponser::send(true, 'Record Not Found', null, 404);
            }
            return JsonResponser::send(false, 'Record found! successfully', $reocrd, 200);
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function allBlogs(Request $request)
    {
        $blogCategoryId = $request->blog_category_id;
        $searchParam = $request->search_param;

        try {

            $records = Blog::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('title', 'like', '%' . $searchParam . '%');
            })
                ->when($blogCategoryId, function ($query) use ($blogCategoryId) {
                    return $query->where('blog_category_id', $blogCategoryId);
                })
                ->with('category:id,name')
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->through(function ($blog) {
                    $blog->created_at_human = Carbon::parse($blog->created_at)->diffForHumans();
                    return $blog;
                });

            // Fetch the 5 latest church news blogs
            $churchNews = BlogCategory::where('slug', 'church_news')
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->with('blog:id,blog_category_id,title,author_name,author_image,created_at')
                ->get();

            // Format created_at to display as '1 day ago'
            $churchNews->each(function ($category) {
                $category->blog->each(function ($blog) {
                    $blog->created_at_words = Carbon::parse($blog->created_at)->diffForHumans();
                });
            });

            // Package both the main blogs and church news blogs
            $data = [
                'blogs' => $records,
                'church_news' => $churchNews
            ];

            return JsonResponser::send(false, 'Record found successfully!', $data, 200);
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function showBlog($id)
    {
        try {
            // Fetch the current event by ID
            $record = Blog::where('id', $id)
                ->with([
                    'category:id,name',
                    'comments.replies' // Fetch replies
                ])
                ->first();
            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }

            // Format created_at for the blog itself
            $record->created_at_human = Carbon::parse($record->created_at)->diffForHumans();

            // Format created_at for the comments and their replies
            $record->comments->each(function ($comment) {
                $comment->created_at_human = Carbon::parse($comment->created_at)->diffForHumans();

                // Format for each reply under the comment
                if ($comment->replies) {
                    $comment->replies->each(function ($reply) {
                        $reply->created_at_human = Carbon::parse($reply->created_at)->diffForHumans();
                    });
                }
            });

            // Update view_count without including `created_at_human`
            $record->increment('view_count');

            // Fetch the 5 latest church news blogs
            $churchNews = BlogCategory::where('slug', 'church_news')
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->with('blog:id,blog_category_id,title,author_name,author_image,created_at')
                ->get();

            // Format created_at to display as '1 day ago'
            $churchNews->each(function ($category) {
                $category->blog->each(function ($blog) {
                    $blog->created_at_words = Carbon::parse($blog->created_at)->diffForHumans();
                });
            });

            // Package both the main blogs and church news blogs
            $data = [
                'blog' => $record,
                'church_news' => $churchNews
            ];
            return JsonResponser::send(false, 'Record found successfully', $data, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function activityCount(Request $request)
    {
        try {
            $record = Blog::where('id', $request->id)->with('category:id,name')->first();

            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }
            DB::beginTransaction();
            $record->update([$request->like ? 'like' : ($request->share ? 'share' : '') => $request->like ? $record->like + 1 : $record->share + 1]);

            $newBlog = Blog::where('id', $request->id)->first();
            DB::commit();
            return JsonResponser::send(false, 'Record updated successfully', $newBlog, 200);
        } catch (\Throwable $error) {
            logger($error);
            DB::rollBack();
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function addComment(Request $request)
    {
        $validateRequest = $this->validateCommentRequest($request);

        if ($validateRequest->fails()) {
            return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
        }

        try {

            $record = Blog::where('id', $request->blog_id)->first();
            if (is_null($record)) {
                return JsonResponser::send(true, 'Blog not found', [], 400);
            }

            DB::beginTransaction();
            // Generate the avatar URL based on name or email
            $avatarUrl = $this->generateAvatar($request->name, $request->email);

            $comment = Comment::create([
                'blog_id' => $request->blog_id,
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'email' => $request->email,
                'avatar_url' => $avatarUrl, // Save the avatar URL
                'ip_address' => $request->ip(),
                'comment' => $request->comment,
                'like' => 0,
            ]);

            $record->update([
                "comments" => $record->comments + 1
            ]);

            DB::commit();
            return JsonResponser::send(false, 'Comment added successfully', $comment, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function generateAvatar($name = null, $email = null)
    {
        // Use name if provided, otherwise fallback to the first part of the email
        $identifier = $name ?? explode('@', $email)[0];

        // Extract initials from the name or identifier
        $initials = collect(explode(' ', $identifier))
            ->map(function ($segment) {
                return strtoupper(substr($segment, 0, 1)); // First letter
            })
            ->take(2) // Limit to two initials
            ->implode('');

        // Generate avatar image using initials
        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=random';

        return $avatarUrl;
    }

    public function likeComment($id)
    {
        try {
            $record = Comment::where('id', $id)->first();

            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }
            DB::beginTransaction();
            $record->update([
                "like" => $record->like + 1
            ]);

            $newComment = Comment::where('id', $id)->first();
            DB::commit();
            return JsonResponser::send(false, 'Record updated successfully', $newComment, 200);
        } catch (\Throwable $error) {
            logger($error);
            DB::rollBack();
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }


    // Resources
    public function resourceCategories()
    {
        try {
            $reocrd = ResourceCategory::orderBy('created_at', 'desc')->get();
            if ($reocrd->isEmpty()) {
                return JsonResponser::send(true, 'Record Not Found', null, 404);
            }
            return JsonResponser::send(false, 'Record found! successfully', $reocrd, 200);
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function allResources(Request $request)
    {
        $resourceCategory = $request->resource_category_id;
        $searchParam = $request->search_param;

        try {

            $records = Resource::when($searchParam, function ($query) use ($searchParam) {
                return $query->where('title', 'like', '%' . $searchParam . '%');
            })
                ->when($resourceCategory, function ($query) use ($resourceCategory) {
                    return $query->where('resource_category_id', $resourceCategory);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            return JsonResponser::send(false, 'Record found successfully!', $records, 200);
        } catch (\Throwable $e) {
            return JsonResponser::send(true, $e->getMessage(), null, 500);
        }
    }

    public function showResources($id)
    {
        try {
            // Fetch the current event by ID
            $record = Resource::where('id', $id)->first();
            if (is_null($record)) {
                return JsonResponser::send(true, 'Record not found', [], 400);
            }
            // Fetch the 4 latest resources excluding the current resource
            $similarResources = Resource::where('id', '!=', $id)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();
            // Package both the main event and similar events
            $data = [
                'resources' => $record,
                'similar_resources' => $similarResources
            ];
            return JsonResponser::send(false, 'Record found successfully', $data, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function downloadResources($id)
    {
        $resourceExist = Resource::where('id', $id)->first();
        if (is_null($resourceExist)) {
            return JsonResponser::send(true, "Resource does not exist", [], 400);
        }
        $filePath = storage_path('app/public/' . $resourceExist->resource_file);

        if (file_exists($filePath)) {
            $resourceExist->update([
                "download_count" => $resourceExist->download_count + 1
            ]);
            return response()->download($filePath);
        } else {
            return abort(404, 'File not found');
        }
    }

    public function registerMember(Request $request)
    {
        try {
            DB::beginTransaction();

            $validateRequest = $this->validateMemberRequest($request);

            if ($validateRequest->fails()) {
                return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
            }

            $memberExist = Member::where('email', $request->email)->first();

            if (!is_null($memberExist)) {
                return JsonResponser::send(true, "Member email exist for this event", [], 400);
            }

            $createRecord = Member::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'message' => $request->message,
                'home_address' => $request->home_address,
                'occupation' => $request->occupation,
            ]);

            DB::commit();
            return JsonResponser::send(false, "Member registered successfully", $createRecord, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    private function validateMemberRequest($request)
    {
        $rules = [
            'full_name' => 'required',
            'email' => 'required',
            'phone_number' => 'required'
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

    private function validateCommentRequest($request)
    {
        $rules = [
            'comment' => 'required|string',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'parent_id' => 'nullable|exists:comments,id',
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
}
