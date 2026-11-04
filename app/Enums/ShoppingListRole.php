<?php

namespace App\Enums;

enum ShoppingListRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
