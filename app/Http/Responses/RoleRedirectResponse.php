<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;

class RoleRedirectResponse implements LoginResponse, PasskeyLoginResponse, RegisterResponse, TwoFactorLoginResponse
{
    public function toResponse($request)
    {
        if ($request->user()?->isStaff()) {
            $request->session()->forget('url.intended');
            $target = route('dashboard');
        } else {
            $target = redirect()->intended(route('account'))->getTargetUrl();
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false, 'redirect' => $target])
            : redirect($target);
    }
}
