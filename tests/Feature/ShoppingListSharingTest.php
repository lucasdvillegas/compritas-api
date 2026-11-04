<?php

use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListInvitation;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function acceptInvitationAs($test, ShoppingListInvitation $invitation, User $invitee): void
{
    Sanctum::actingAs($invitee);

    $test->postJson("/api/invitations/{$invitation->id}/accept")->assertOk();
}

test('owner invites a registered user by pin and creates a notification', function () {
    [$owner, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();

    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => $invitee->pin])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $invitation = ShoppingListInvitation::firstOrFail();
    $notification = $invitee->notifications()->firstOrFail();

    expect($invitation->shopping_list_id)->toBe($list->id)
        ->and($invitation->inviter_id)->toBe($owner->id)
        ->and($invitation->invitee_id)->toBe($invitee->id)
        ->and($notification->data['type'])->toBe('list_invitation')
        ->and($notification->data['message'])->toContain('te invitó a participar en Compra Semanal');
});

test('invitee accepts and sees the shared list as member', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    acceptInvitationAs($this, $invitation, $invitee);

    $this->getJson('/api/lists')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $list->id)
        ->assertJsonPath('data.0.role', 'member');

    $this->getJson("/api/lists/{$list->id}")->assertOk();

    $this->assertDatabaseHas('shopping_list_user', [
        'shopping_list_id' => $list->id,
        'user_id' => $invitee->id,
        'role' => 'member',
    ]);
});

test('member can add products from their own catalog', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $owner = $workspace->owner;
    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    acceptInvitationAs($this, $invitation, $invitee);

    $product = Product::factory()->create([
        'workspace_id' => $invitee->currentWorkspace()->id,
        'name' => 'Manzana',
    ]);

    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('data.product_name', 'Manzana');

    Sanctum::actingAs($owner);

    $this->getJson("/api/lists/{$list->id}")
        ->assertOk()
        ->assertJsonPath('data.items.0.product_name', 'Manzana');
});

test('member cannot invite, rename, delete or remove members', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    acceptInvitationAs($this, $invitation, $invitee);

    $third = User::factory()->create();

    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => $third->pin])
        ->assertForbidden();

    $this->patchJson("/api/lists/{$list->id}", ['name' => 'Otra'])
        ->assertForbidden();

    $this->deleteJson("/api/lists/{$list->id}")
        ->assertForbidden();

    $this->deleteJson("/api/lists/{$list->id}/members/{$third->id}")
        ->assertForbidden();
});

test('non members cannot access the list', function () {
    [$owner, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $stranger = User::factory()->create();
    $product = Product::factory()->create([
        'workspace_id' => $stranger->currentWorkspace()->id,
        'name' => 'Pan',
    ]);

    Sanctum::actingAs($stranger);

    $this->getJson("/api/lists/{$list->id}")->assertNotFound();
    $this->postJson("/api/lists/{$list->id}/items", ['product_id' => $product->id])->assertNotFound();
    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => $owner->pin])->assertNotFound();
});

test('owner can remove a member and a member can leave', function () {
    [$owner, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    acceptInvitationAs($this, $invitation, $invitee);

    Sanctum::actingAs($owner);
    $this->deleteJson("/api/lists/{$list->id}/members/{$invitee->id}")->assertOk();

    Sanctum::actingAs($invitee);
    $this->getJson("/api/lists/{$list->id}")->assertNotFound();

    Sanctum::actingAs($owner);
    $invitation = inviteToList($this, $list, $invitee);

    acceptInvitationAs($this, $invitation, $invitee);
    $this->deleteJson("/api/lists/{$list->id}/members/me")->assertOk();
    $this->getJson("/api/lists/{$list->id}")->assertNotFound();
});

test('the owner cannot leave or be removed', function () {
    [$owner, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $this->deleteJson("/api/lists/{$list->id}/members/me")->assertStatus(422);
    $this->deleteJson("/api/lists/{$list->id}/members/{$owner->id}")->assertStatus(422);
});

test('invitation validates the pin and the invitee state', function () {
    [$owner, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();

    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => '1234567'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pin');

    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => '00000000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pin');

    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => $owner->pin])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pin');

    $invitation = inviteToList($this, $list, $invitee);

    acceptInvitationAs($this, $invitation, $invitee);

    Sanctum::actingAs($owner);

    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => $invitee->pin])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pin');
});

test('a pending invitation is not duplicated', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();

    inviteToList($this, $list, $invitee);

    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => $invitee->pin])
        ->assertOk()
        ->assertJsonPath('message', 'Ya hay una invitación pendiente para ese usuario.');

    expect(ShoppingListInvitation::count())->toBe(1)
        ->and($invitee->notifications()->count())->toBe(1);
});

test('pending invitations can be listed and revoked', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    $this->getJson("/api/lists/{$list->id}/invitations")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->deleteJson("/api/lists/{$list->id}/invitations/{$invitation->id}")
        ->assertOk();

    $this->getJson("/api/lists/{$list->id}/invitations")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('an invitee can decline and be invited again', function () {
    [, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    Sanctum::actingAs($invitee);
    $this->postJson("/api/invitations/{$invitation->id}/decline")->assertOk();
    $this->getJson('/api/lists')->assertOk()->assertJsonCount(0, 'data');

    Sanctum::actingAs($workspace->owner);
    $this->postJson("/api/lists/{$list->id}/invitations", ['pin' => $invitee->pin])
        ->assertCreated();

    expect(ShoppingListInvitation::count())->toBe(1)
        ->and(ShoppingListInvitation::firstOrFail()->status->value)->toBe('pending');
});

test('shared completed lists appear in the member stats', function () {
    [$owner, $workspace] = actingAsWorkspaceUser();

    $list = ShoppingList::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Compra Semanal',
    ]);

    ShoppingListItem::factory()->create([
        'shopping_list_id' => $list->id,
        'product_name' => 'Manzana',
        'category_name' => 'Frutas',
        'unit_price' => 100,
        'quantity' => 1,
    ]);

    $invitee = User::factory()->create();
    $invitation = inviteToList($this, $list, $invitee);

    acceptInvitationAs($this, $invitation, $invitee);

    Sanctum::actingAs($owner);
    $this->patchJson("/api/lists/{$list->id}/complete")->assertOk();

    Sanctum::actingAs($invitee);

    $this->getJson('/api/stats/summary?from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertJsonPath('data.lists_count', 1)
        ->assertJsonPath('data.total_spent', 100);
});
