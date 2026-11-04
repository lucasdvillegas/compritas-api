<?php

namespace App\Http\Controllers\Api;

use App\Enums\ShoppingListInvitationStatus;
use App\Enums\ShoppingListRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListInvitationRequest;
use App\Models\ShoppingListInvitation;
use App\Models\User;
use App\Notifications\ListInvitationReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ShoppingListInvitationController extends Controller
{
    public function store(StoreListInvitationRequest $request, int $list): JsonResponse
    {
        $shoppingList = $request->user()->shoppingLists()->findOrFail($list);

        Gate::authorize('manageMembers', $shoppingList);

        $invitee = User::where('pin', $request->validated('pin'))->first();

        if (! $invitee) {
            throw ValidationException::withMessages([
                'pin' => ['PIN inválido.'],
            ]);
        }

        if ($invitee->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'pin' => ['No podés invitarte a vos mismo.'],
            ]);
        }

        if ($shoppingList->members()->where('users.id', $invitee->id)->exists()) {
            throw ValidationException::withMessages([
                'pin' => ['Ese usuario ya participa en la lista.'],
            ]);
        }

        $existing = $shoppingList->invitations()
            ->where('invitee_id', $invitee->id)
            ->first();

        if ($existing?->isPending()) {
            return response()->json([
                'status' => true,
                'message' => 'Ya hay una invitación pendiente para ese usuario.',
                'data' => $this->invitationData($existing),
            ]);
        }

        $invitation = DB::transaction(function () use ($request, $shoppingList, $invitee): ShoppingListInvitation {
            $invitation = $shoppingList->invitations()->updateOrCreate(
                ['invitee_id' => $invitee->id],
                [
                    'inviter_id' => $request->user()->id,
                    'status' => ShoppingListInvitationStatus::Pending,
                    'responded_at' => null,
                ],
            );

            $invitee->notify(new ListInvitationReceived($invitation));

            return $invitation;
        });

        return response()->json([
            'status' => true,
            'message' => 'Invitación enviada correctamente.',
            'data' => $this->invitationData($invitation),
        ], 201);
    }

    public function index(Request $request, int $list): JsonResponse
    {
        $shoppingList = $request->user()->shoppingLists()->findOrFail($list);

        Gate::authorize('manageMembers', $shoppingList);

        $invitations = $shoppingList->invitations()
            ->with('invitee:id,name,email')
            ->where('status', ShoppingListInvitationStatus::Pending)
            ->latest()
            ->get()
            ->map(fn (ShoppingListInvitation $invitation): array => $this->invitationData($invitation));

        return response()->json([
            'status' => true,
            'message' => 'Invitaciones obtenidas correctamente.',
            'data' => $invitations,
        ]);
    }

    public function destroy(Request $request, int $list, int $invitation): JsonResponse
    {
        $shoppingList = $request->user()->shoppingLists()->findOrFail($list);

        Gate::authorize('manageMembers', $shoppingList);

        $invitation = $shoppingList->invitations()->findOrFail($invitation);

        Gate::authorize('delete', $invitation);

        $invitation->delete();

        return response()->json([
            'status' => true,
            'message' => 'Invitación revocada correctamente.',
            'data' => null,
        ]);
    }

    public function accept(Request $request, int $invitation): JsonResponse
    {
        $invitation = ShoppingListInvitation::with('shoppingList')->findOrFail($invitation);

        Gate::authorize('accept', $invitation);

        DB::transaction(function () use ($request, $invitation): void {
            $invitation->shoppingList->members()->syncWithoutDetaching([
                $request->user()->id => ['role' => ShoppingListRole::Member->value],
            ]);

            $invitation->update([
                'status' => ShoppingListInvitationStatus::Accepted,
                'responded_at' => now(),
            ]);
        });

        $this->markNotificationAsRead($request, $invitation->id);

        return response()->json([
            'status' => true,
            'message' => 'Invitación aceptada correctamente.',
            'data' => [
                'list_id' => $invitation->shopping_list_id,
                'list_name' => $invitation->shoppingList->name,
            ],
        ]);
    }

    public function decline(Request $request, int $invitation): JsonResponse
    {
        $invitation = ShoppingListInvitation::findOrFail($invitation);

        Gate::authorize('decline', $invitation);

        $invitation->update([
            'status' => ShoppingListInvitationStatus::Declined,
            'responded_at' => now(),
        ]);

        $this->markNotificationAsRead($request, $invitation->id);

        return response()->json([
            'status' => true,
            'message' => 'Invitación rechazada correctamente.',
            'data' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function invitationData(ShoppingListInvitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'list_id' => $invitation->shopping_list_id,
            'status' => $invitation->status->value,
            'invitee' => $invitation->relationLoaded('invitee')
                ? [
                    'id' => $invitation->invitee->id,
                    'name' => $invitation->invitee->name,
                ]
                : null,
            'created_at' => $invitation->created_at,
        ];
    }

    private function markNotificationAsRead(Request $request, int $invitationId): void
    {
        $request->user()->unreadNotifications->each(function ($notification) use ($invitationId): void {
            if (($notification->data['invitation_id'] ?? null) === $invitationId) {
                $notification->markAsRead();
            }
        });
    }
}
