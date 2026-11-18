<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Tenant private channel - users can only listen to their own tenant's channel
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});
