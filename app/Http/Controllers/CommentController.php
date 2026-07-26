<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;

class CommentController extends Controller
{
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        return CommentResource::collection(
            $task->comments()->with('user')->latest()->get()
        );
    }

    public function store(StoreCommentRequest $request, Task $task)
    {
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return new CommentResource($comment->load('user'));
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
