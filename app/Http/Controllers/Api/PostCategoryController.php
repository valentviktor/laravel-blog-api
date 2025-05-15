<?php

namespace App\Http\Controllers\Api;

use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\HasApiResponses;
use Illuminate\Database\Eloquent\Builder;

/**
 * @OA\Tag(
 * name="Post Category",
 * description="API Endpoints for Post Categories"
 * )
 */
class PostCategoryController extends Controller
{
    use HasApiResponses;

        /**
     * @OA\Get(
     *     path="/api/post-categories",
     *     tags={"Post Category"},
     *     summary="Display a listing of post categories",
     *     description="Get paginated list of post categories, optionally filtered by search query",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Optional. Search for categories by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Optional. Number of items per page. Default is 10",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Category 1"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-26T12:00:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-26T12:00:00.000000Z"),
     *                     @OA\Property(property="posts_count", type="integer", example=5)
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=2),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="to", type="integer", example=2),
     *             @OA\Property(property="offset", type="integer", example=0),
     *             @OA\Property(property="limit", type="integer", example=10),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong. Try again later."),
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="error", type="string", example="Detailed error message.")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $posts = PostCategory::query()
            ->when($search, function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->withCount('posts')
            ->paginate($request->get('per_page', 10));

        return $this->success([
            'data' => $posts->items(),
            'total' => $posts->total(),
            'per_page' => $posts->perPage(),
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'from' => $posts->firstItem(),
            'to' => $posts->lastItem(),
            'offset' => ($posts->currentPage() - 1) * $posts->perPage(),
            'limit' => $posts->perPage(),
        ], 'Post categories retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/post-categories",
     *     tags={"Post Category"},
     *     summary="Create a new post category",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="New Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=3),
     *             @OA\Property(property="name", type="string", example="New Category"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-26T14:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-26T14:00:00.000000Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong. Try again later."),
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="error", type="string", example="Detailed error message.")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $category = PostCategory::create([
                'name' => $request->name,
            ]);

            return $this->success($category, 'Post category created successfully.', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create a post category.', ['error' => $e->getMessage()]);

            return $this->error('Something went wrong. Try again later.', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/post-categories/{postCategory}",
     *     tags={"Post Category"},
     *     summary="Retrieve a specific post category by ID",
     *     @OA\Parameter(
     *         name="postCategory",
     *         in="path",
     *         description="ID of the post category to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post category retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Category 1"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-26T12:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-26T12:00:00.000000Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post category not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong. Try again later."),
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="error", type="string", example="Detailed error message.")
     *             )
     *         )
     *     )
     * )
     */
    public function show(PostCategory $postCategory)
    {
        try {
            return $this->success($postCategory, 'Post category retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve post category.', ['error' => $e->getMessage()]);

            return $this->error('Something went wrong. Try again later.', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/post-categories/{postCategory}",
     *     tags={"Post Category"},
     *     summary="Update an existing post category",
     *     @OA\Parameter(
     *         name="postCategory",
     *         in="path",
     *         description="ID of the post category to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Category Name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Updated Category Name"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-26T12:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-26T15:00:00.000000Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post category not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong. Try again later."),
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="error", type="string", example="Detailed error message.")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, PostCategory $postCategory)
    {
        $request->validate(['name' => 'required|string|max:100']);

        try {
            $postCategory->update(['name' => $request->name]);

            return $this->success($postCategory, 'Post category updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update post category.', ['error' => $e->getMessage()]);

            return $this->error('Something went wrong. Try again later.', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/post-categories/{postCategory}",
     *     tags={"Post Category"},
     *     summary="Delete a post category",
     *     @OA\Parameter(
     *         name="postCategory",
     *         in="path",
     *         description="ID of the post category to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Data deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post category not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong. Try again later."),
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="error", type="string", example="Detailed error message.")
     *             )
     *         )
     *     )
     * )
     */
    public function destroy(PostCategory $postCategory)
    {
        try {
            if ($postCategory->posts()->exists()) {
                return $this->error('Post categories cannot be deleted because they still have posts.', 400);
            }

            $postCategory->delete();

            return $this->success(null, 'Post category deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete post category.', ['error' => $e->getMessage()]);

            return $this->error('Something went wrong. Try again later.', 500, ['error' => $e->getMessage()]);
        }
    }
}

