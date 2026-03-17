<?php

namespace Tests\Feature;

use App\Models\AdminDocument;
use App\Models\User;
use App\Policies\AdminDocumentPolicy;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_roles_can_access_shared_admin_documents(): void
    {
        $admin = User::factory()->make([
            'role' => User::ROLE_ADMIN,
        ]);

        $operator = User::factory()->make([
            'role' => User::ROLE_OPERATOR,
        ]);

        $nonStaff = User::factory()->make([
            'role' => 'customer',
        ]);

        $document = AdminDocument::query()->create([
            'title' => 'Кредитни шаблони',
            'description' => 'Вътрешен пакет документи.',
            'file_path' => 'admin-documents/templates.zip',
            'original_file_name' => 'templates.zip',
            'uploaded_by_user_id' => null,
        ]);

        $policy = new AdminDocumentPolicy;
        $adminPanel = (new Panel)->id('admin');

        $this->assertTrue($admin->canAccessPanel($adminPanel));
        $this->assertTrue($operator->canAccessPanel($adminPanel));
        $this->assertFalse($nonStaff->canAccessPanel($adminPanel));

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->viewAny($operator));
        $this->assertTrue($policy->view($admin, $document));
        $this->assertTrue($policy->view($operator, $document));
        $this->assertTrue($policy->create($operator));
        $this->assertTrue($policy->update($operator, $document));
        $this->assertTrue($policy->delete($operator, $document));

        $this->assertFalse($policy->viewAny($nonStaff));
        $this->assertFalse($policy->view($nonStaff, $document));
        $this->assertFalse($policy->create($nonStaff));
    }
}
