<?php

namespace App\Enum;

enum NotificationType: string
{
    case INFO = 'info';
    case ERROR = 'error';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case DANGER = 'danger';
    case DEFAULT = 'default';
}
