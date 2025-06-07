<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;

class Logger
{
    public static function log(?string $action = null, Model $model, array $payload = []): void
    {
        if (!$model instanceof Model) {
            throw new InvalidArgumentException('Logger: model must be an instance of Eloquent Model');
        }

        ActivityLog::create([
            'user_id'    => Auth::id(),
            'method'     => Request::method(),
            'url'        => Request::fullUrl(),
            'action'     => $action,
            'model_type' => $model->getMorphClass(), // lebih baik untuk morph
            'model_id'   => $model->getKey(),
            'payload'    => !empty($payload) ? $payload : Request::except(['password', '_token']),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
