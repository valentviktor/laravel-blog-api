<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HasApiResponses;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 * name="Users",
 * description="API Endpoints for Users Management"
 * )
 */
class UserController extends Controller
{
    use HasApiResponses;

    /**
     * @OA\Schema(
     *   schema="UserWithBookmarks",
     *   type="object",
     *   title="UserWithBookmarks",
     *   description="User object with bookmarks relationship",
     *   required={"id", "name", "email"},
     *   @OA\Property(property="id", type="integer", example=1),
     *   @OA\Property(property="name", type="string", example="John Doe"),
     *   @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *   @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-01T12:00:00Z"),
     *   @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-01T12:00:00Z"),
     *   @OA\Property(
     *     property="bookmarks",
     *     type="array",
     *     @OA\Items(
     *       type="object",
     *       description="Bookmark object",
     *       @OA\Property(property="id", type="integer", example=10),
     *       @OA\Property(property="title", type="string", example="Example Bookmark"),
     *       @OA\Property(property="url", type="string", example="https://example.com")
     *     )
     *   )
     * )
    * @OA\Schema(
    *     schema="UserBasicResponse",
    *     type="object",
    *     @OA\Property(property="data", type="object",
    *         @OA\Property(property="id", type="integer", example=1),
    *         @OA\Property(property="name", type="string", example="John Doe"),
    *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
    *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-01T12:00:00Z"),
    *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-01T12:00:00Z"),
    *     ),
    *     @OA\Property(property="message", type="string", example="User created successfully")
    * )
     */

    private $cacheKey = 'user_index_';

    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Get all users",
     *     description="Returns a paginated list of users, with optional search filter.",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of users per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search users by name or email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/UserWithBookmarks")
     *                 ),
     *                 @OA\Property(property="links", type="object",
     *                     @OA\Property(property="first", type="string"),
     *                     @OA\Property(property="last", type="string"),
     *                     @OA\Property(property="prev", nullable=true, type="string"),
     *                     @OA\Property(property="next", nullable=true, type="string"),
     *                 ),
     *                 @OA\Property(property="meta", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="from", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="path", type="string"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="to", type="integer"),
     *                     @OA\Property(property="total", type="integer"),
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $cacheKey = $this->cacheKey . $page . '_' . $perPage . '_' . $search;

        $users = Cache::remember($cacheKey, 60, function () use ($perPage, $search) {
            $query = User::query()->with('bookmarks');
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            }
            return $query->paginate($perPage);
        });

        return $this->success($users, 'Users retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     description="Creates a new user record.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/UserBasicResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="errors", type="object",
     *                 example={
     *                     "name": {"The name field is required."},
     *                     "email": {"The email field is required."},
     *                     "password": {"The password confirmation does not match."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $this->clearUserCache();

        return $this->success($user, 'User created successfully', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Get a specific user",
     *     description="Returns user data by ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/UserWithBookmarks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not Found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $user = User::with('bookmarks')->findOrFail($id);

        return $this->success($user, 'User retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Update a user",
     *     description="Updates user data. User must be authenticated and can only update own data.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/UserBasicResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->id !== Auth::id()) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user),
            ],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        $user->update($request->only('name', 'email'));

        $this->clearUserCache();

        return $this->success($user, 'User updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Delete a user",
     *     description="Deletes user by ID. User must be authenticated and can only delete own account.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id !== Auth::id()) {
            return $this->error('Unauthorized', 403);
        }
        $user->delete();

        $this->clearUserCache();

        return $this->success(null, 'User deleted successfully');
    }

    private function clearUserCache()
    {
        DB::table('cache')->where('key', 'like', "%{$this->cacheKey}%")->delete();
    }
}
