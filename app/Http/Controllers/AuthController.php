<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('editor');

        $standardPresets = [
            [
                'name' => 'Google E-E-A-T Standard',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.7,
                'custom_instructions' => 'Focus on Google E-E-A-T standards. Provide deep first-hand experience insights, expert breakdown, actionable steps, and clear bullet points.',
                'is_active' => true,
            ],
            [
                'name' => 'Affiliate Buyer Guide',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.7,
                'custom_instructions' => 'Focus on commercial intent. Compare features, highlight pros & cons, present clear buyer recommendations, and end with a strong purchasing verdict CTA.',
                'is_active' => false,
            ],
        ];

        foreach ($standardPresets as $presetData) {
            $user->presets()->create($presetData);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($request->expectsJson() || $request->is('api/*')) {
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'token' => $token,
                    'user' => $user->load('roles', 'permissions'),
                ]);
            }

            return redirect()->intended(route('dashboard'));
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Logged out successfully']);
        }

        return redirect()->route('login');
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('roles', 'permissions'));
    }
}
