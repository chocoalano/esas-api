<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserCompanyResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    public function auth_office()
    {
        try {
            $user = Auth::user();
            $response = new UserCompanyResource($user->company);
            return $this->sendResponse($response, 'Permit show list successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Registration failed.', ['error' => $e->getMessage()], 500);
        }

    }
}
