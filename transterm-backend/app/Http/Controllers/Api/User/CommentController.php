<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): CommentCollection
    {
        $query = Comment::query()
            ->with([
                'term',
                'user',
            ])
            ->where('user_id', $request->user()->id);

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->integer('term_id'));
        }

        if ($request->filled('is_spam')) {
            $query->where(
                'is_spam',
                filter_var($request->input('is_spam'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        return new CommentCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function store(Request $request, Term $term)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = Comment::create([
            'term_id' => $term->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_spam' => false,
        ]);

        $comment->load([
            'term',
            'user',
        ]);

        return (new CommentResource($comment))
            ->additional([
                'message' => 'Comment created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
        ]);

        $comment->load([
            'term',
            'user',
        ]);

        return (new CommentResource($comment))
            ->additional([
                'message' => 'Comment updated successfully.',
            ])
            ->response();
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
