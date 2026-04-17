<?php

namespace Tests\Feature\Api\Teacher;

use App\Models\Glossary;
use App\Models\GlossaryTranslation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\BuildsDomainFixtures;
use Tests\TestCase;

class TeacherWorkflowControllerTest extends TestCase
{
    use RefreshDatabase;
    use BuildsDomainFixtures;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_teacher_can_list_only_approvable_glossaries_with_filters(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher_glossary_list@ukf.sk',
            'username' => 'teacher_glossary_list',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher');

        $student = User::factory()->create([
            'email' => 'student_glossary_list@student.ukf.sk',
            'username' => 'student_glossary_list',
            'name' => 'Anna',
            'surname' => 'Student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        $invalidStudent = User::factory()->create([
            'email' => 'student_glossary_invalid@teacher.sk',
            'username' => 'student_glossary_invalid',
            'name' => 'Invalid',
            'surname' => 'Student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $invalidStudent->assignRole('Student');

        $otherTeacher = User::factory()->create([
            'email' => 'teacher_glossary_foreign@ukf.sk',
            'username' => 'teacher_glossary_foreign',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $otherTeacher->assignRole('Teacher');

        $fixtures = $this->createLanguagePairAndField();

        $pendingStudentGlossary = Glossary::query()->create([
            'owner_id' => $student->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $pendingStudentGlossary->id,
            'language_id' => $fixtures['source']->id,
            'title' => 'Network Security Glossary',
            'description' => null,
        ]);

        $approvedStudentGlossary = Glossary::query()->create([
            'owner_id' => $student->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => true,
            'is_public' => true,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $approvedStudentGlossary->id,
            'language_id' => $fixtures['source']->id,
            'title' => 'Already Approved',
            'description' => null,
        ]);

        $invalidStudentGlossary = Glossary::query()->create([
            'owner_id' => $invalidStudent->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $invalidStudentGlossary->id,
            'language_id' => $fixtures['source']->id,
            'title' => 'Invalid Student Domain Glossary',
            'description' => null,
        ]);

        $ownPendingGlossary = Glossary::query()->create([
            'owner_id' => $teacher->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $ownPendingGlossary->id,
            'language_id' => $fixtures['source']->id,
            'title' => 'Teacher Own Pending',
            'description' => null,
        ]);

        $foreignTeacherGlossary = Glossary::query()->create([
            'owner_id' => $otherTeacher->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $foreignTeacherGlossary->id,
            'language_id' => $fixtures['source']->id,
            'title' => 'Foreign Teacher Glossary',
            'description' => null,
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson('/api/teacher/glossaries?status=pending&per_page=20')
            ->assertOk()
            ->assertJsonMissing(['id' => $foreignTeacherGlossary->id])
            ->assertJsonMissing(['id' => $invalidStudentGlossary->id])
            ->assertJsonFragment(['id' => $pendingStudentGlossary->id])
            ->assertJsonFragment(['id' => $ownPendingGlossary->id]);

        $this->getJson('/api/teacher/glossaries?status=approved&search=Approved')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $approvedStudentGlossary->id);
    }

    public function test_teacher_can_approve_student_glossary(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher_approve_student@ukf.sk',
            'username' => 'teacher_approve_student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher');

        $student = User::factory()->create([
            'email' => 'student_for_teacher@student.ukf.sk',
            'username' => 'student_for_teacher',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        $fixtures = $this->createLanguagePairAndField();
        $glossary = Glossary::query()->create([
            'owner_id' => $student->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        Sanctum::actingAs($teacher);
        $this->patchJson("/api/teacher/glossaries/{$glossary->id}/approve")
            ->assertOk()
            ->assertJsonPath('message', 'Glossary approved successfully.')
            ->assertJsonPath('data.approved', true);

        $this->assertDatabaseHas('glossaries', [
            'id' => $glossary->id,
            'approved' => 1,
            'approved_by' => $teacher->id,
        ]);
    }

    public function test_teacher_can_view_allowed_glossary_via_teacher_endpoint_and_cannot_view_foreign_teacher_glossary(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher_view_glossary@ukf.sk',
            'username' => 'teacher_view_glossary',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher');

        $student = User::factory()->create([
            'email' => 'student_view_glossary@student.ukf.sk',
            'username' => 'student_view_glossary',
            'name' => 'View',
            'surname' => 'Student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        $otherTeacher = User::factory()->create([
            'email' => 'foreign_view_teacher@ukf.sk',
            'username' => 'foreign_view_teacher',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $otherTeacher->assignRole('Teacher');

        $fixtures = $this->createLanguagePairAndField();

        $studentGlossary = Glossary::query()->create([
            'owner_id' => $student->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $studentGlossary->id,
            'language_id' => $fixtures['source']->id,
            'title' => 'Teacher Detail Endpoint',
            'description' => 'Preview payload',
        ]);

        $foreignTeacherGlossary = Glossary::query()->create([
            'owner_id' => $otherTeacher->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson("/api/teacher/glossaries/{$studentGlossary->id}")
            ->assertOk()
            ->assertJsonPath('glossary.id', $studentGlossary->id)
            ->assertJsonPath('glossary.owner.id', $student->id)
            ->assertJsonPath('glossary.owner.base_role', 'Student')
            ->assertJsonPath('glossary.translations.0.title', 'Teacher Detail Endpoint');

        $this->getJson("/api/teacher/glossaries/{$foreignTeacherGlossary->id}")
            ->assertForbidden();
    }

    public function test_teacher_without_editor_can_approve_own_glossary_but_not_other_teacher_glossary(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher_own_approve@ukf.sk',
            'username' => 'teacher_own_approve',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher');

        $otherTeacher = User::factory()->create([
            'email' => 'other_teacher@ukf.sk',
            'username' => 'other_teacher',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $otherTeacher->assignRole('Teacher');

        $fixtures = $this->createLanguagePairAndField();
        $ownGlossary = Glossary::query()->create([
            'owner_id' => $teacher->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        $foreignTeacherGlossary = Glossary::query()->create([
            'owner_id' => $otherTeacher->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        Sanctum::actingAs($teacher);
        $this->patchJson("/api/teacher/glossaries/{$ownGlossary->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.approved', true);

        $this->patchJson("/api/teacher/glossaries/{$foreignTeacherGlossary->id}/approve")
            ->assertForbidden();
    }

    public function test_teacher_can_assign_editor_only_to_student_and_not_to_self(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher_assign_editor@ukf.sk',
            'username' => 'teacher_assign_editor',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher');

        $student = User::factory()->create([
            'email' => 'student_for_editor@student.ukf.sk',
            'username' => 'student_for_editor',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        $otherTeacher = User::factory()->create([
            'email' => 'teacher_target@ukf.sk',
            'username' => 'teacher_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $otherTeacher->assignRole('Teacher');

        $invalidStudent = User::factory()->create([
            'email' => 'student_invalid_domain@teacher.sk',
            'username' => 'student_invalid_domain',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $invalidStudent->assignRole('Student');

        Sanctum::actingAs($teacher);
        $this->patchJson("/api/teacher/students/{$student->id}/assign-editor")
            ->assertOk()
            ->assertJsonPath('message', 'Editor role assigned successfully.');

        $student->refresh();
        $this->assertTrue($student->hasRole('Editor'));
        $this->assertTrue($student->hasRole('Student'));

        $this->patchJson("/api/teacher/students/{$teacher->id}/assign-editor")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot assign Editor role to yourself.');

        $this->patchJson("/api/teacher/students/{$otherTeacher->id}/assign-editor")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Teacher can assign Editor role only to Student accounts.');

        $this->patchJson("/api/teacher/students/{$invalidStudent->id}/assign-editor")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Teacher can assign Editor role only to Student accounts.');
    }

    public function test_teacher_can_list_only_student_candidates_for_editor_assignment(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher_students_list@ukf.sk',
            'username' => 'teacher_students_list',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher');

        $eligibleStudent = User::factory()->create([
            'email' => 'eligible.student@student.ukf.sk',
            'username' => 'eligible_student',
            'name' => 'Eligible',
            'surname' => 'Student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $eligibleStudent->assignRole('Student');

        $studentWithEditor = User::factory()->create([
            'email' => 'student_with_editor@student.ukf.sk',
            'username' => 'student_with_editor',
            'name' => 'Editor',
            'surname' => 'Student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $studentWithEditor->assignRole('Student');
        $studentWithEditor->assignRole('Editor');

        $inactiveStudent = User::factory()->create([
            'email' => 'inactive_student@student.ukf.sk',
            'username' => 'inactive_student',
            'activated' => false,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $inactiveStudent->assignRole('Student');

        $invalidDomainStudent = User::factory()->create([
            'email' => 'invalid_domain_student@teacher.sk',
            'username' => 'invalid_domain_student',
            'name' => 'Wrong',
            'surname' => 'Domain',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $invalidDomainStudent->assignRole('Student');

        $externalUser = User::factory()->create([
            'email' => 'external_candidate@gmail.com',
            'username' => 'external_candidate',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $externalUser->assignRole('User');

        Sanctum::actingAs($teacher);

        $this->getJson('/api/teacher/students?search=Eligible&without_editor=true&per_page=20')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $eligibleStudent->id)
            ->assertJsonPath('data.0.has_editor_role', false);

        $this->getJson('/api/teacher/students?without_editor=false&per_page=20')
            ->assertOk()
            ->assertJsonMissing(['id' => $externalUser->id])
            ->assertJsonMissing(['id' => $inactiveStudent->id])
            ->assertJsonMissing(['id' => $invalidDomainStudent->id])
            ->assertJsonFragment(['id' => $eligibleStudent->id])
            ->assertJsonFragment(['id' => $studentWithEditor->id]);
    }

    public function test_student_cannot_access_teacher_routes(): void
    {
        $student = User::factory()->create([
            'email' => 'teacher_route_student@student.ukf.sk',
            'username' => 'teacher_route_student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        $fixtures = $this->createLanguagePairAndField();
        $glossary = Glossary::query()->create([
            'owner_id' => $student->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        Sanctum::actingAs($student);
        $this->getJson('/api/teacher/glossaries')
            ->assertForbidden();

        $this->patchJson("/api/teacher/glossaries/{$glossary->id}/approve")
            ->assertForbidden();

        $this->getJson('/api/teacher/students')
            ->assertForbidden();

        $this->patchJson("/api/teacher/students/{$student->id}/assign-editor")
            ->assertForbidden();
    }
}
