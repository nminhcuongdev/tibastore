<?php

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], [
            'code.required' => 'Vui lòng nhập mã đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt([
            'code' => $credentials['code'],
            'password' => $credentials['password'],
            'delflag' => false,
        ], $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return back()
            ->withErrors(['code' => 'Mã đăng nhập hoặc mật khẩu không đúng.'])
            ->onlyInput('code');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}


