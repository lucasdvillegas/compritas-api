<?php

namespace App\Enums;

enum ShoppingListInvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
