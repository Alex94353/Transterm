<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentCollection;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentModerationController extends Controller
{
    public function index(Request $request): CommentCollection
    {
        $query = Comment::query()
            ->with([
                'term',
                'user',
            ]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->integer('term_id'));
        }

        if ($request->filled('is_spam')) {
            $query->where(
                'is_spam',
                filter_var($request->input('is_spam'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where('body', 'like', "%{$search}%");
        }

        return new CommentCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function markSpam(Comment $comment): JsonResponse
    {
        $comment->update([
            'is_spam' => true,
        ]);

        return response()->json([
            'message' => 'Comment marked as spam successfully.',
        ]);
    }

    public function unmarkSpam(Comment $comment): JsonResponse
    {
        $comment->update([
            'is_spam' => false,
        ]);

        return response()->json([
            'message' => 'Comment unmarked as spam successfully.',
        ]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
