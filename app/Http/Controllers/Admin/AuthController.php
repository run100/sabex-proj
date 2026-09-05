<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AccessLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, true)) {
            abort(404);
        }

        $request->session()->regenerate();
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            $ip = AccessLogService::ip($request);
            $admin->last_login_at = now();
            AccessLogService::assign($admin, 'last_login_ip', $ip);
            $admin->save();
            AccessLogService::write('admin', (int) $admin->id, 'login', $ip, 'seo_user', (int) $admin->id, $request);
        }

        return redirect('/items');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
