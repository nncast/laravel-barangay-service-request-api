<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ServiceRequest;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Barangay Clearance',
            'is_active' => true,
        ], $attributes));
    }

    private function user(string $role = 'resident', array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => $role], $attributes));
    }

    private function submitRequest(User $resident, ?Category $category = null): ServiceRequest
    {
        Sanctum::actingAs($resident);
        $id = $this->postJson('/api/requests', [
            'category_id' => ($category ?? $this->category())->id,
            'title' => 'Clearance for employment',
            'description' => 'Needed for a job application.',
        ])->assertCreated()->json('id');

        return ServiceRequest::findOrFail($id);
    }

    public function test_register_accepts_the_name_field_sent_by_the_app(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated()
            ->assertJsonPath('user.name', 'Ana Reyes')
            ->assertJsonPath('user.role', 'resident')
            ->assertJsonStructure(['token']);
    }

    public function test_register_cannot_choose_a_role(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ])->assertCreated()->assertJsonPath('user.role', 'resident');
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $this->user('resident', ['email' => 'off@example.com', 'is_active' => false]);

        $this->postJson('/api/login', ['email' => 'off@example.com', 'password' => 'password'])
            ->assertForbidden();
    }

    public function test_unauthenticated_request_gets_json_401_not_a_redirect(): void
    {
        $this->get('/api/me')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_invalid_request_returns_422_not_500(): void
    {
        Sanctum::actingAs($this->user());

        $this->postJson('/api/requests', ['title' => 'Missing the rest'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'description']);
    }

    public function test_cannot_submit_to_an_inactive_category(): void
    {
        Sanctum::actingAs($this->user());
        $inactive = $this->category(['name' => 'Old service', 'is_active' => false]);

        $this->postJson('/api/requests', [
            'category_id' => $inactive->id,
            'title' => 'Old service request',
            'description' => 'Should not be accepted.',
        ])->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
    }

    public function test_resident_cannot_see_or_cancel_another_residents_request(): void
    {
        $request = $this->submitRequest($this->user());

        Sanctum::actingAs($this->user());
        $this->getJson("/api/requests/{$request->id}")->assertNotFound();
        $this->deleteJson("/api/requests/{$request->id}")->assertNotFound();
    }

    public function test_only_pending_requests_can_be_cancelled(): void
    {
        $resident = $this->user();
        $request = $this->submitRequest($resident);
        $request->update(['status' => 'in_review']);

        $this->deleteJson("/api/requests/{$request->id}")->assertUnprocessable();
        $this->assertSame('in_review', $request->fresh()->status);
    }

    public function test_resident_cannot_reach_admin_endpoints(): void
    {
        Sanctum::actingAs($this->user());

        $this->getJson('/api/admin/requests')->assertForbidden();
        $this->getJson('/api/admin/dashboard')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_staff_cannot_manage_users(): void
    {
        $resident = $this->user();
        Sanctum::actingAs($this->user('staff'));

        $this->putJson("/api/admin/users/{$resident->id}", ['role' => 'admin'])->assertForbidden();
        $this->deleteJson("/api/admin/users/{$resident->id}")->assertForbidden();
    }

    public function test_status_update_notifies_resident_with_readable_labels(): void
    {
        $resident = $this->user();
        $request = $this->submitRequest($resident);

        Sanctum::actingAs($this->user('staff'));
        $this->putJson("/api/admin/requests/{$request->id}/status", ['status' => 'in_review'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_review');

        Sanctum::actingAs($resident);
        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('0.body', "Your request #{$request->tracking_code} changed from Pending to In Review.");
    }

    public function test_cancelled_request_cannot_be_updated_by_staff(): void
    {
        $resident = $this->user();
        $request = $this->submitRequest($resident);
        $this->deleteJson("/api/requests/{$request->id}")->assertOk();

        Sanctum::actingAs($this->user('staff'));
        $this->putJson("/api/admin/requests/{$request->id}/status", ['status' => 'approved'])
            ->assertUnprocessable();
        $this->assertSame('cancelled', $request->fresh()->status);
    }

    public function test_completed_at_is_cleared_when_a_request_is_reopened(): void
    {
        $request = $this->submitRequest($this->user());
        Sanctum::actingAs($this->user('staff'));

        $this->putJson("/api/admin/requests/{$request->id}/status", ['status' => 'completed'])->assertOk();
        $this->assertNotNull($request->fresh()->completed_at);

        $this->putJson("/api/admin/requests/{$request->id}/status", ['status' => 'processing'])->assertOk();
        $this->assertNull($request->fresh()->completed_at);
    }

    public function test_dashboard_counts_only_this_years_month(): void
    {
        $request = $this->submitRequest($this->user());
        $old = $request->replicate(['tracking_code']);
        $old->tracking_code = 'BSR-OLD-00001';
        $old->save();
        $old->forceFill(['created_at' => now()->subYear()])->save();

        Sanctum::actingAs($this->user('admin'));
        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('pending', 2)
            ->assertJsonPath('this_month', 1);
    }

    public function test_admin_sees_deactivated_users_but_staff_does_not(): void
    {
        $this->user('resident', ['is_active' => false]);

        Sanctum::actingAs($this->user('admin'));
        $this->assertCount(2, $this->getJson('/api/admin/users')->assertOk()->json());

        Sanctum::actingAs($this->user('staff'));
        $this->assertCount(2, $this->getJson('/api/admin/users')->assertOk()->json());
    }

    public function test_deactivating_a_user_revokes_their_tokens(): void
    {
        $resident = $this->user();
        $resident->createToken('auth_token');

        Sanctum::actingAs($this->user('admin'));
        $this->putJson("/api/admin/users/{$resident->id}", ['is_active' => false])->assertOk();

        $this->assertSame(0, $resident->tokens()->count());
    }

    public function test_admin_can_clear_a_users_phone(): void
    {
        $resident = $this->user('resident', ['phone' => '09170000000']);

        Sanctum::actingAs($this->user('admin'));
        $this->putJson("/api/admin/users/{$resident->id}", ['phone' => null])->assertOk();

        $this->assertNull($resident->fresh()->phone);
    }

    public function test_admin_cannot_deactivate_or_demote_themselves(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/users/{$admin->id}", ['is_active' => false])->assertForbidden();
        $this->putJson("/api/admin/users/{$admin->id}", ['role' => 'staff'])->assertForbidden();
    }

    public function test_deleting_staff_keeps_the_history_they_wrote(): void
    {
        $request = $this->submitRequest($this->user());
        $staff = $this->user('staff');

        Sanctum::actingAs($staff);
        $this->putJson("/api/admin/requests/{$request->id}/status", ['status' => 'approved'])->assertOk();

        Sanctum::actingAs($this->user('admin'));
        $this->deleteJson("/api/admin/users/{$staff->id}")->assertOk();

        $this->assertSame(2, StatusLog::where('request_id', $request->id)->count());
        $this->assertNull(StatusLog::where('new_status', 'approved')->first()->changed_by);
    }

    public function test_resident_can_mark_one_notification_read(): void
    {
        $resident = $this->user();
        $request = $this->submitRequest($resident);

        Sanctum::actingAs($this->user('staff'));
        $this->putJson("/api/admin/requests/{$request->id}/status", ['status' => 'approved'])->assertOk();

        Sanctum::actingAs($resident);
        $id = $this->getJson('/api/notifications')->json('0.id');
        $this->putJson("/api/notifications/{$id}/read")->assertOk()->assertJsonPath('is_read', true);

        Sanctum::actingAs($this->user());
        $this->putJson("/api/notifications/{$id}/read")->assertNotFound();
    }
}
