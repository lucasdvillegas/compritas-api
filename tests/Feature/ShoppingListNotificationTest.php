<?php

use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('completing a list notifies the other members but not the actor', function () {
    [$owner, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    Sanctum::actingAs($invitee);
    $this->postJson("/api/invitations/{$invitation->id}/accept")->assertOk();

    Sanctum::actingAs($owner);
    $this->patchJson("/api/lists/{$list->id}/complete")->assertOk();

    $completion = $invitee->notifications()
        ->get()
        ->first(fn ($notification) => ($notification->data['type'] ?? null) === 'list_completed');

    expect($completion)->not->toBeNull()
        ->and($completion->data['message'])->toContain('marcó Compra Semanal como completa')
        ->and($owner->notifications()->count())->toBe(0);
});

test('notifications endpoint returns payloads and the unread count', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    inviteToList($this, $list, $invitee);

    Sanctum::actingAs($invitee);

    $this->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'list_invitation')
        ->assertJsonPath('data.0.data.invitation_status', 'pending');

    $this->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.count', 1);

    $notificationId = $invitee->notifications()->firstOrFail()->id;

    $this->patchJson("/api/notifications/{$notificationId}/read")->assertOk();

    $this->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.count', 0);
});

test('all notifications can be marked as read', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $firstList = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $secondList = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra del sábado',
    ]);

    $invitee = User::factory()->create();

    inviteToList($this, $firstList, $invitee);
    inviteToList($this, $secondList, $invitee);

    Sanctum::actingAs($invitee);

    $this->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.count', 2);

    $this->postJson('/api/notifications/read-all')->assertOk();

    $this->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.count', 0);
});

test('notification actions are scoped to the recipient', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    inviteToList($this, $list, $invitee);

    $notificationId = $invitee->notifications()->firstOrFail()->id;

    $stranger = User::factory()->create();
    Sanctum::actingAs($stranger);

    $this->patchJson("/api/notifications/{$notificationId}/read")->assertNotFound();
});

test('accepting an invitation updates the notification status', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    Sanctum::actingAs($invitee);
    $this->postJson("/api/invitations/{$invitation->id}/accept")->assertOk();

    $this->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.data.invitation_status', 'accepted')
        ->assertJsonPath('data.0.read_at', fn ($value) => $value !== null);

    $this->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.count', 0);

    $this->getJson('/api/lists')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $list->id);
});

test('declining an invitation updates the notification status', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    Sanctum::actingAs($invitee);
    $this->postJson("/api/invitations/{$invitation->id}/decline")->assertOk();

    $this->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.data.invitation_status', 'declined');

    $this->getJson('/api/lists')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('notifications endpoints require authentication', function () {
    $this->getJson('/api/notifications')->assertUnauthorized();
    $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
    $this->postJson('/api/notifications/read-all')->assertUnauthorized();
});
