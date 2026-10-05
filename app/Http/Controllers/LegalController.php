<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function imprint(): View
    {
        return view('legal.page', [
            'page' => __('legal.imprint'),
            'active' => 'imprint',
        ]);
    }

    public function privacy(): View
    {
        return view('legal.page', [
            'page' => __('legal.privacy'),
            'active' => 'privacy',
        ]);
    }

    public function terms(): View
    {
        return view('legal.page', [
            'page' => __('legal.terms'),
            'active' => 'terms',
        ]);
    }
}
