<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingListInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->limit(50)
            ->get();

        $invitationIds = $notifications
            ->pluck('data.invitation_id')
            ->filter()
            ->unique()
            ->values();

        $statuses = ShoppingListInvitation::whereIn('id', $invitationIds)
            ->get(['id', 'status'])
            ->mapWithKeys(fn (ShoppingListInvitation $invitation): array => [
                $invitation->id => $invitation->status->value,
            ]);

        $data = $notifications->map(function ($notification) use ($statuses): array {
            $payload = $notification->data;
            $invitationId = $payload['invitation_id'] ?? null;

            return [
                'id' => $notification->id,
                'type' => $payload['type'] ?? null,
                'message' => $payload['message'] ?? '',
                'data' => [
                    ...$payload,
                    'invitation_status' => $invitationId === null
                        ? null
                        : ($statuses[$invitationId] ?? null),
                ],
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Notificaciones obtenidas correctamente.',
            'data' => $data,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Contador de notificaciones obtenido correctamente.',
            'data' => [
                'count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $notification = $request->user()->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Notificación marcada como leída.',
            'data' => null,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Notificaciones marcadas como leídas.',
            'data' => null,
        ]);
    }
}
