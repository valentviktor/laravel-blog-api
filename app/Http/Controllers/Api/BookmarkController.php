<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Traits\HasApiResponses;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Bookmark",
 *     description="API Endpoints for Bookmarks"
 * )
 *
 *  * @OA\SecurityScheme(
 *     type="http",
 *     description="Use a bearer token to access this API",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth",
 * )
 */
class BookmarkController extends Controller
{
    use HasApiResponses;

    /**
     * Get the authenticated user's bookmarks.
     *
     * @OA\Get(
     *     path="/api/bookmarks",
     *     summary="Get user's bookmarks",
     *     tags={"Bookmark"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Bookmarks retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Post 1"),
     *                 @OA\Property(property="content", type="string", example="Content of post 1..."),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="message", type="string", example="Bookmarks retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not authenticated"),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     )
     * )
     */
    public function index()
    {
        if (Auth::check()) {
            $bookmarks = Auth::user()->bookmarks()->get();
            return $this->success($bookmarks, 'Bookmarks retrieved successfully');
        } else {
            return $this->error('User not authenticated', 401);
        }
    }

    /**
     * Toggle bookmark for a post.
     *
     * @OA\Post(
     *     path="/api/bookmarks/{post}",
     *     summary="Bookmark or unbookmark a post",
     *     tags={"Bookmark"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="The ID of the post to bookmark or remove",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bookmark toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Post 1"),
     *                 @OA\Property(property="content", type="string", example="Content of post 1..."),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="message", type="string", example="Post bookmarked")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to toggle bookmark",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to toggle bookmark"),
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="error", type="string", example="Detailed error message.")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Post $post)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $exists = $user->bookmarks()->where('post_id', $post->id)->exists();

            if ($exists) {
                $user->bookmarks()->detach($post->id);
                DB::commit();
                return $this->success($user->bookmarks()->get()->except(['media']), 'Bookmark removed');
            }

            $user->bookmarks()->attach($post->id);
            DB::commit();
            return $this->success($user->bookmarks()->get()->except(['media']), 'Post bookmarked');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to toggle bookmark', 500, ['error' => $e->getMessage()]);
        }
    }
}
