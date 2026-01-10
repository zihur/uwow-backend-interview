<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function __construct(public PostService $postService)
    {
        //
    }

    /**
     * 呈現所有文章
     *
     * @response 200 {"status": "success", "message": "Posts retrieved successfully", "data": {...}}
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Posts retrieved successfully',
            'data' => Post::orderBy('is_pinned', 'desc')
                ->orderBy('sort_order', 'desc')
                ->paginate(10),
        ], 200);
    }

    /**
     * 新增一筆文章資料
     *
     * @bodyParam title string required 標題 Example: 範例文章標題
     * @bodyParam slug string 文章網址識別碼 Example: example-post-title
     * @bodyParam img_file file 上傳圖片
     * @bodyParam content string required 文章內容 Example: 這是一篇範例文章的內容。
     * @bodyParam status integer required 文章狀態(0:未發布,1:發佈,2:排程用) Example: 1
     * @bodyParam is_pinned integer 文章是否置頂(0:否,1:是) Example: 0
     * @bodyParam published_at string 發佈時間 Example: 2024-12-31 10:00:00
     * @bodyParam finished_at string 結束時間 Example: 2025-01-31 23:59:59
     * @response 201 {"status": "success", "message": "Post created successfully"}
     */
    public function store(StorePostRequest $request)
    {
        $post = DB::transaction(function () use ($request) {

            $maxSort = Post::lockForUpdate()->max('sort_order');
            $sort = ($maxSort ?? 0) + 100000;

            $path = null;
            if ($request->hasFile('img_file')) {
                $path = $request->file('img_file')->store('posts', 'public');
            }

            return Post::create([
                'title' => $request->title,
                'img_path' => $path,
                'content' => clean($request->content),
                'sort_order' => $sort,
                'status' => $request->status,
                'published_at' => $request->published_at,
                'finished_at' => $request->finished_at,
            ]);
        });


        return response()->json([
            'status' => 'success',
            'message' => 'Post created successfully',
            'data' => $post,
        ], 201);
    }

    /**
     * 取得後台單筆文章資料
     *
     * 這邊我都是先假定是後台操作，前台 api 才使用 slug
     *
     * @response 200 {"status": "success", "message": "Post found", "data": {...}}
     */
    public function show(Request $request, $id)
    {
        $post = $this->postService->findPost($id);

        if (!$post) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Post not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Post found',
            'data' => $post,
        ], 200);
    }

    /**
     * 更新指定文章資料
     *
     * 圖片我選 file 型態上傳，因為 base64 會增加資料量
     *
     * @bodyParam title string required 標題 Example: 範例文章標題
     * @bodyParam slug string 文章網址識別碼 Example: example-post-title
     * @bodyParam img_file file 上傳圖片
     * @bodyParam content string required 文章內容 Example: 這是一篇範例文章的內容。
     * @bodyParam sort_order float 中值排序數值 Example: 150000.0
     * @bodyParam status integer required 文章狀態(0:未發布,1:發佈,2:排程用) Example: 1
     * @bodyParam is_pinned integer 文章是否置頂(0:否,1:是) Example: 0
     * @bodyParam published_at string 發佈時間 Example: 2024-12-31 10:00:00
     * @bodyParam finished_at string 結束時間 Example: 2025-01-31 23:59:59
     * @response 200 {"status": "success", "message": "Post updated successfully", "data": {...}}
     */
    public function update(UpdatePostRequest $request, $id)
    {
        $post = Post::where('id', $id)->first();
        if (!$post) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Post not found',
                'data' => null,
            ], 404);
        }

        $path = $post->img_path;
        if ($request->hasFile('img_file')) {
            $path = $request->file('img_file')->store('posts', 'public');
        }

        // sort 沒特別處理，是因為預期上前端使用 中值算法 進行排序
        $post->update([
            'title' => $request->title,
            'img_path' => $path,
            'content' => clean($request->content),
            'sort_order' => $request->sort_order,
            'status' => $request->status,
            'published_at' => $request->published_at,
            'finished_at' => $request->finished_at,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Post updated successfully',
            'data' => $post,
        ], 200);
    }

    /**
     * 刪除指定文章資料（軟刪除）
     *
     * 因為沒做權限設計，所以先暫時忽略
     *
     * @response 200 {"message": "文章已移至回收桶"}
     */
    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json(['message' => '文章已移至回收桶']);
    }

    /**
     * 搜尋文章
     * 資料量大時，可以改用全文索引解決方案，如 Elasticsearch 或 MySQL 的全文索引功能
     *
     * @queryParam query string required 搜尋關鍵字 Example: 範例
     * @response 200 {"status": "success", "message": "Search results retrieved successfully", "data": {...}}
     */
    public function search(Request $request)
    {
        $query = $request->query('query');

        $posts = Post::query()
            ->select(['id', 'title', 'slug', 'published_at'])
            ->whereLike('title', '%' . $query . '%')
            ->orderBy('sort_order', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'message' => 'Search results retrieved successfully',
            'data' => $posts,
        ], 200);
    }

    /**
     * 顯示所有啟用中的文章
     *
     * @resoponse 200 {"status": "success", "message": "Posts retrieved successfully", "data": {...}}
     */
    public function activeIndex()
    {
        $now = now();
        $posts = Post::query()
            ->where(function ($query) use ($now) {
                $query->where('status', 1)
                    ->orWhere(function ($sub) use ($now) {
                        $sub->where('status', 2)
                            ->where('published_at', '<=', $now)
                            ->where('finished_at', '>=', $now);
                    });
            })
            ->whereNull('deleted_at')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'message' => 'Posts retrieved successfully',
            'data' => $posts,
        ], 200);
    }

    /**
     * 呈現資源回收桶的文章.
     *
     * @response 200 {"status": "success", "message": "Posts retrieved successfully", "data": {...}}
     */
    public function withTrashIndex()
    {
        $posts = Post::withTrashed()
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'message' => 'Posts retrieved successfully',
            'data' => $posts,
        ], 200);
    }
}
