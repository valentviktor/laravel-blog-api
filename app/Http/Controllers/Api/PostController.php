<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Traits\HasApiResponses;
use App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 * name="Posts",
 * description="API Endpoints for Posts"
 * )
 */
class PostController extends Controller
{
    use HasApiResponses;

    private $cacheKey = 'post_index_';

    /**
     * @OA\Get(
     * path="/api/posts",
     * tags={"Posts"},
     * summary="List all posts",
     * description="Retrieve paginated list of posts, with optional search",
     * @OA\Parameter(name="search", in="query", description="Search term", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Posts retrieved successfully",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="title", type="string"),
     * @OA\Property(property="slug", type="string"),
     * @OA\Property(property="content", type="string"),
     * @OA\Property(property="user_id", type="integer"),
     * @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time"),
     * @OA\Property(property="image_url", type="string", nullable=true),
     * @OA\Property(property="user", type="object",
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="name", type="string"),
     * ),
     * @OA\Property(property="post_categories", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="name", type="string"),
     * )),
     * )),
     * @OA\Property(property="total", type="integer"),
     * @OA\Property(property="per_page", type="integer"),
     * @OA\Property(property="current_page", type="integer"),
     * @OA\Property(property="last_page", type="integer"),
     * @OA\Property(property="from", type="integer", nullable=true),
     * @OA\Property(property="to", type="integer", nullable=true),
     * @OA\Property(property="offset", type="integer"),
     * @OA\Property(property="limit", type="integer"),
     * )
     * ),
     * @OA\Response(response=500, description="Internal server error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="status", type="integer"),
     * @OA\Property(property="errors", type="object",
     * @OA\Property(property="error", type="string")
     * )
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $cacheKey = $this->cacheKey . md5($search . $page . $perPage);

            $posts = Cache::remember($cacheKey, 60 * 60, function () use ($search, $request) {
                $posts = Post::query()
                    ->with(['user:id,name', 'postCategories:id,name'])
                    ->when($search, function (Builder $query) use ($search) {
                        $query->where('title', 'like', '%' . $search . '%')
                            ->orWhere('content', 'like', '%' . $search . '%');
                    })
                    ->paginate($request->get('per_page', 10));

                $data = $posts->getCollection()->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'content' => $post->content,
                        'user_id' => $post->user_id,
                        'deleted_at' => $post->deleted_at,
                        'created_at' => $post->created_at,
                        'updated_at' => $post->updated_at,
                        'user' => $post->user,
                        'post_categories' => $post->postCategories,
                        'image_url' => $post->image_url,
                    ];
                });

                return [
                    'data' => $data,
                    'total' => $posts->total(),
                    'per_page' => $posts->perPage(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                    'offset' => ($posts->currentPage() - 1) * $posts->perPage(),
                    'limit' => $posts->perPage(),
                ];
            });

            return $this->success($posts, 'Posts retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve posts: ' . $e->getMessage());
            return $this->error('Failed to retrieve posts.', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @OA\Post(
     * path="/api/posts",
     * tags={"Posts"},
     * summary="Create a new post",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"title", "content", "categories"},
     * @OA\Property(property="title", type="string", example="My New Post Title"),
     * @OA\Property(property="content", type="string", example="This is the content of my new post."),
     * @OA\Property(property="post_category_id", type="integer", example="1"),
     * @OA\Property(property="categories", type="array", @OA\Items(type="integer"), example={1, 2}),
     * @OA\Property(property="image", type="string", format="binary")
     * )
     * ),
     * @OA\Response(response=201, description="Post created successfully",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=123),
     * @OA\Property(property="title", type="string", example="My New Post Title"),
     * @OA\Property(property="content", type="string", example="This is the content of my new post."),
     * @OA\Property(property="user_id", type="integer", example=1),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time"),
     * @OA\Property(property="image_url", type="string", nullable=true),
     * )
     * ),
     * @OA\Response(response=422, description="Validation failed",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="errors", type="object",
     * @OA\Property(property="title", type="array", @OA\Items(type="string")),
     * @OA\Property(property="content", type="array", @OA\Items(type="string")),
     * @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     * )
     * )
     * ),
     * @OA\Response(response=500, description="Internal server error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="status", type="integer"),
     * @OA\Property(property="errors", type="object",
     * @OA\Property(property="error", type="string")
     * )
     * )
     * )
     * )
     */
    public function store(PostRequest $request)
    {
        try {
            DB::beginTransaction();
            $post = Auth::user()->posts()->create($request->safe()->only(['title', 'content', 'post_category_id']));

            if ($request->safe()->has('categories')) {
                $post->postCategories()->attach($request->safe()->input('categories'));
            }

            if ($request->hasFile('image')) {
                $post->addMediaFromRequest('image')->toMediaCollection('posts');
            }
            DB::commit();
            $this->clearPostCache();
            return $this->success($post, 'Post created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create post: ' . $e->getMessage());
            return $this->error('Failed to create post.', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @OA\Get(
     * path="/api/posts/{slug}",
     * tags={"Posts"},
     * summary="Get a post by slug",
     * @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     * @OA\Response(response=200, description="Post retrieved successfully",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=123),
     * @OA\Property(property="title", type="string", example="My New Post Title"),
     * @OA\Property(property="slug", type="string", example="my-new-post-title"),
     * @OA\Property(property="content", type="string", example="This is the content of my new post."),
     * @OA\Property(property="user_id", type="integer", example=1),
     * @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time"),
     * @OA\Property(property="user", type="object",
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="name", type="string"),
     * ),
     * @OA\Property(property="post_categories", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="name", type="string"),
     * )),
     * @OA\Property(property="image_url", type="string", nullable=true),
     * )
     * ),
     * @OA\Response(response=404, description="Post not found",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="status", type="integer"),
     * )
     * ),
     * @OA\Response(response=500, description="Internal server error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="status", type="integer"),
     * @OA\Property(property="errors", type="object",
     * @OA\Property(property="error", type="string")
     * )
     * )
     * )
     * )
     */
    public function show(string $slug)
    {
        try {
            $post = Post::with(['user:id,name', 'postCategories:id,name'])->where('slug', $slug)->first();

            if (!$post) {
                return $this->error('Post not found', 404);
            }

            return $this->success($post, 'Post retrieved successfully', 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve post: ' . $e->getMessage());
            return $this->error('Failed to retrieve post.', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @OA\Post(
     * path="/api/posts/{post}",
     * tags={"Posts"},
     * summary="Update a post using POST",
     * @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"title", "content"},
     * @OA\Property(property="title", type="string", example="Updated Post Title"),
     * @OA\Property(property="content", type="string", example="This is the updated content."),
     * @OA\Property(property="post_categories", type="array", @OA\Items(type="integer"), example={1, 2}),
      * @OA\Property(property="image", type="string", format="binary")
     * )
     * ),
     * @OA\Response(response=201, description="Post updated successfully",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=123),
     * @OA\Property(property="title", type="string", example="Updated Post Title"),
     * @OA\Property(property="content", type="string", example="This is the updated content."),
     * @OA\Property(property="user_id", type="integer", example=1),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time"),
     * @OA\Property(property="image_url", type="string", nullable=true),
     * )
     * ),
     * @OA\Response(response=422, description="Validation failed",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="errors", type="object",
     * @OA\Property(property="title", type="array", @OA\Items(type="string")),
     * @OA\Property(property="content", type="array", @OA\Items(type="string")),
     * @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     * )
     * )
     * ),
     * @OA\Response(response=500, description="Failed to update post",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="status", type="integer"),
     * @OA\Property(property="errors", type="object",
     * @OA\Property(property="error", type="string")
     * )
     * )
     * )
     * )
     */
    public function update(PostRequest $request, Post $post)
    {
        try {
            DB::beginTransaction();
            $post->update($request->safe()->only('title', 'content'));
            if ($request->safe()->has('post_categories')) {
                $post->postCategories()->sync($request->safe()->input('post_categories'));
            }
             if ($request->hasFile('image')) {
                $post->clearMediaCollection('posts');
                $post->addMediaFromRequest('image')->toMediaCollection('posts');
            }
            DB::commit();
            $this->clearPostCache();
            return $this->success($post, 'Post updated successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update post: ' . $e->getMessage());
            return $this->error('Failed to update post.', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/posts/{post}",
     * tags={"Posts"},
     * summary="Delete a post",
     * @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=201, description="Post deleted successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Post deleted successfully."),
     * )
     * ),
     * @OA\Response(response=500, description="Failed to delete post",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="status", type="integer"),
     * @OA\Property(property="errors", type="object",
     * @OA\Property(property="error", type="string")
     * )
     * )
     * )
     * )
     */
    public function destroy(Post $post)
    {
        try {
            DB::beginTransaction();
             $post->clearMediaCollection('posts');
             $post->postCategories()->detach();
            $post->delete();
            DB::commit();
            $this->clearPostCache();
            return $this->success([], 'Post deleted successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete post: ' . $e->getMessage());
            return $this->error('Failed to delete post.', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function clearPostCache()
    {
        try{
            DB::table('cache')->where('key', 'like', "%{$this->cacheKey}%")->delete();
        }catch(\Exception $e){
             Log::error('Failed to clear cache: ' . $e->getMessage());
        }

    }
}

