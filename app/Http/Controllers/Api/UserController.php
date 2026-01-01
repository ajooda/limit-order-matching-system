<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getProfile(Request $request): UserResource
    {
        $user = $request->user()->load([
            'assets:id,user_id,symbol,amount,locked_amount',
        ]);

        return new UserResource($user);
    }
}
