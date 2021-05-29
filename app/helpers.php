<?php

use App\ActionsLog;
use App\Errors;

if (!function_exists('log_action')) {
    function log_action($model, $action, $relatedId)
    {
        return ActionsLog::create([
            'user_id' => 1, // change to 1 because 0 is invalid with foreign key
            'model' => $model,
            'action' => $action,
            'related_id' => $relatedId
        ]);
    }
}

if (!function_exists('log_error')) {
    function log_error($err, $controller, $function)
    {
        return Errors::create([
            'error' => $err,
            'controller' => $controller,
            'function' => $function
        ]);
    }
}