<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel per company — only the company owner may subscribe.
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    return \App\Models\Company::where('id', $companyId)
        ->where('user_id', $user->id)
        ->exists();
});
