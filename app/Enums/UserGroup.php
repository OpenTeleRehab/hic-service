<?php

namespace App\Enums;

enum UserGroup: string
{
    case ADMIN = "admin";

    case MODERATOR = "moderator";
}
