<?php

namespace App\Services\Tas\Enums;

enum AuthStatus: int
{
    case NOT_LOGGED_IN = 0;
    case WAITING_CODE = 1;
    case WAITING_SIGNUP = -1;
    case WAITING_PASSWORD = 2;
    case LOGGED_IN = 3;
    case NOT_EXIST = 666;
}
