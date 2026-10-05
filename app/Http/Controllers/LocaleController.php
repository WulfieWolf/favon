<?php

namespace App\Http\Controllers;

use App\Support\LocaleConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(LocaleConfiguration::codes())],
        ]);

        $request->session()->put('locale', $data['locale']);

        if ($request->user() && $request->user()->locale !== $data['locale']) {
            $request->user()->forceFill(['locale' => $data['locale']])->save();
        }

        return back()->withCookie(cookie(
            'locale',
            $data['locale'],
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'lax',
        ));
    }
}
