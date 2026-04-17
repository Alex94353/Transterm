<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditorAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        if (! $actor->can('admin.access') && ! $actor->supportsTeacherRoleByEmail()) {
            return response()->json([
                'message' => 'Teacher tools are available only for @ukf.sk teacher accounts.',
            ], 403);
        }

        $query = User::query()
            ->select([
                'id',
                'name',
                'surname',
                'email',
                'activated',
                'banned',
            ])
            ->with([
                'roles:id,name',
            ])
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'Student');
            })
            ->whereRaw('LOWER(email) LIKE ?', ['%@student.ukf.sk']);

        $withoutEditor = $request->filled('without_editor')
            ? filter_var($request->input('without_editor'), FILTER_VALIDATE_BOOLEAN)
            : true;
        if ($withoutEditor) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('name', 'Editor');
            });
        }

        $onlyActive = $request->filled('only_active')
            ? filter_var($request->input('only_active'), FILTER_VALIDATE_BOOLEAN)
            : true;
        if ($onlyActive) {
            $query->where('activated', true)
                ->where('banned', false);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $searchTerms = preg_split('/\s+/', $search) ?: [];

            $query->where(function ($q) use ($search, $searchTerms) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('surname', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');

                if (count($searchTerms) > 1) {
                    $q->orWhere(function ($fullNameQuery) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $fullNameQuery->where(function ($termQuery) use ($term) {
                                $termQuery->where('name', 'like', '%' . $term . '%')
                                    ->orWhere('surname', 'like', '%' . $term . '%');
                            });
                        }
                    });
                }
            });
        }

        $paginator = $query
            ->orderBy('name')
            ->orderBy('surname')
            ->orderBy('id')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        $paginator->through(fn (User $student) => $this->toListItem($student));

        return response()->json($paginator);
    }

    public function grant(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        if (! $actor->can('admin.access') && ! $actor->supportsTeacherRoleByEmail()) {
            return response()->json([
                'message' => 'Teacher tools are available only for @ukf.sk teacher accounts.',
            ], 403);
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot assign Editor role to yourself.',
            ], 422);
        }

        if (! $user->hasRole('Student')) {
            return response()->json([
                'message' => 'Teacher can assign Editor role only to Student accounts.',
            ], 422);
        }

        if (! $user->supportsStudentRoleByEmail()) {
            return response()->json([
                'message' => 'Teacher can assign Editor role only to Student accounts.',
            ], 422);
        }

        if ($user->hasRole('Editor')) {
            return response()->json([
                'message' => 'User already has Editor role.',
            ], 422);
        }

        if (! $user->activated || $user->banned) {
            return response()->json([
                'message' => 'Editor role can be assigned only to active, non-banned students.',
            ], 422);
        }

        $user->assignRole('Editor');
        AuditLogger::log(
            $request,
            $request->user(),
            'teacher.editor.assigned-to-student',
            $user,
            $user,
            [
                'teacher_id' => $request->user()->id,
            ]
        );

        return response()->json([
            'message' => 'Editor role assigned successfully.',
            'user' => $user->fresh([
                'roles:id,name',
            ]),
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return min(max($perPage, 5), 20);
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(User $student): array
    {
        $roleNames = $student->roles->pluck('name');
        $fullName = trim(implode(' ', array_filter([
            $student->name,
            $student->surname,
        ])));

        return [
            'id' => $student->id,
            'name' => $student->name,
            'surname' => $student->surname,
            'full_name' => $fullName !== '' ? $fullName : null,
            'email' => $student->email,
            'base_role' => 'Student',
            'has_editor_role' => $roleNames->contains('Editor'),
        ];
    }
}
