<?php

namespace App\Enums;

enum MfaEnforcement: string
{
    case RECOMMEND = 'recommend';

    case DISABLED = 'skip';

    case ENFORCE = 'force';
}
