<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountDeletionController extends Controller
{
    public function show(Request $request, AccountDeletionService $deletions): View
    {
        return view('settings.account-deletion', [
            'statistics' => $deletions->statistics((int) $request->user()->id),
            'user' => $request->user(),
            'graceDays' => AccountDeletionService::GRACE_DAYS,
        ]);
    }

    public function request(Request $request, AccountDeletionService $deletions): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:grace,immediate'],
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ]);

        if (! Hash::check($validated['password'], (string) $request->user()->password)) {
            return back()->withErrors(['password' => __('account_deletion.password_invalid')]);
        }

        $userId = (int) $request->user()->id;
        $immediate = $validated['mode'] === 'immediate';
        $deletions->request($request->user(), $immediate);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('ui_dialog', [
            'variant' => 'success',
            'message' => $immediate
                ? __('account_deletion.finalized')
                : __('account_deletion.scheduled', ['days' => AccountDeletionService::GRACE_DAYS]),
        ]);
    }

    public function cancel(Request $request, AccountDeletionService $deletions): RedirectResponse
    {
        $deletions->cancel($request->user());

        return redirect()->route('profile.edit')->with('ui_toast', __('account_deletion.cancelled'));
    }
}
