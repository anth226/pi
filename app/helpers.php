<?php

use App\ActionsLog;

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