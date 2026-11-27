<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexPostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index(IndexPostRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated('per_page') ?? 5);

        $posts = Post::published()
            ->with('user', 'category', 'tags');

        $category = $request->validated('category');

        if (is_string($category)) {
            $posts->whereRelation('category', 'slug', $category);
        }

        $tag = $request->validated('tag');

        if (is_string($tag)) {
            $posts->whereRelation('tags', 'slug', $tag);
        }

        $paginatedPosts = $posts->latest('id')->paginate($perPage)->withQueryString();

        return PostResource::collection($paginatedPosts);
    }

    public function show(Post $post): PostResource
    {
        Gate::authorize('view', $post);

        return new PostResource($post->load('user', 'category', 'tags'));
    }
}
