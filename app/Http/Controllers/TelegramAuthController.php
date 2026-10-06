<?php

namespace App\Http\Controllers;

use App\Services\TelegramLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class TelegramAuthController extends Controller
{
    public function callback(Request $request, TelegramLoginService $telegram): RedirectResponse
    {
        try {
            $telegramUserId = $telegram->verify($request->all());
            $user = $telegram->resolveUser($telegramUserId, app()->getLocale());
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()
                ->route('home')
                ->withErrors(['telegram' => __('auth.telegram_failed')]);
        }

        if (($user->account_status ?? 'active') === 'deleted') {
            return redirect()
                ->route('home')
                ->withErrors(['telegram' => __('auth.telegram_blocked')]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
