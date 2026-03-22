<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\User;
use App\Policies\BlogPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\FaqPolicy;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_manage_content_resources_and_contact_messages(): void
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

        $blog = Blog::query()->create([
            'title' => 'Тестова статия',
            'slug' => 'testova-statiya',
            'content' => 'Съдържание',
            'is_published' => true,
        ]);

        $faq = Faq::query()->create([
            'question' => 'Тестов въпрос',
            'answer' => 'Тестов отговор',
            'is_published' => true,
        ]);

        $assignedOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $contactMessage = ContactMessage::query()->create([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение.',
            'assigned_user_id' => $assignedOperator->id,
        ]);

        $blogPolicy = new BlogPolicy;
        $faqPolicy = new FaqPolicy;
        $contactMessagePolicy = new ContactMessagePolicy;
        $adminPanel = (new Panel)->id('admin');

        $this->assertTrue($admin->canAccessPanel($adminPanel));
        $this->assertTrue($operator->canAccessPanel($adminPanel));
        $this->assertFalse($nonStaff->canAccessPanel($adminPanel));

        $this->assertTrue($blogPolicy->viewAny($admin));
        $this->assertTrue($blogPolicy->create($admin));
        $this->assertTrue($blogPolicy->update($admin, $blog));
        $this->assertTrue($faqPolicy->viewAny($admin));
        $this->assertTrue($faqPolicy->update($admin, $faq));
        $this->assertTrue($contactMessagePolicy->viewAny($admin));
        $this->assertTrue($contactMessagePolicy->view($admin, $contactMessage));
        $this->assertTrue($contactMessagePolicy->update($admin, $contactMessage));

        $this->assertFalse($blogPolicy->viewAny($operator));
        $this->assertFalse($blogPolicy->update($operator, $blog));
        $this->assertFalse($faqPolicy->viewAny($operator));
        $this->assertFalse($faqPolicy->update($operator, $faq));
        $this->assertTrue($contactMessagePolicy->viewAny($assignedOperator));
        $this->assertTrue($contactMessagePolicy->view($assignedOperator, $contactMessage));
        $this->assertFalse($contactMessagePolicy->update($assignedOperator, $contactMessage));

        $this->assertFalse($blogPolicy->viewAny($nonStaff));
        $this->assertFalse($faqPolicy->viewAny($nonStaff));
        $this->assertFalse($contactMessagePolicy->viewAny($nonStaff));
    }
}
