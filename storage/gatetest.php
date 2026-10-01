<?php

$user = App\Models\User::find(4);
echo "user: {$user->email} hasRole admin: ".var_export($user->hasRole('admin'), true)."\n";
echo "checkPermissionTo staff.view: ".var_export($user->checkPermissionTo('staff.view'), true)."\n";
echo "default guard: ".config('auth.defaults.guard')."\n";
Illuminate\Support\Facades\Auth::guard('api')->setUser($user);
echo "api guard user: ".(Illuminate\Support\Facades\Auth::guard('api')->user() ? 'set' : 'null')."\n";
echo "default guard user: ".(Illuminate\Support\Facades\Auth::user() ? 'set' : 'null')."\n";
echo "Gate check (no user passed): ".var_export(Illuminate\Support\Facades\Gate::allows('staff.view'), true)."\n";
echo "Gate check (api user passed): ".var_export(Illuminate\Support\Facades\Gate::forUser($user)->allows('staff.view'), true)."\n";