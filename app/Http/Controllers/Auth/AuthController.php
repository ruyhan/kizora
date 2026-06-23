<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Recaptcha\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private readonly RecaptchaService $recaptcha) {}

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($msg = $this->recaptcha->verifyRequest($request, 'login')) {
            return back()->withErrors(['email' => $msg]);
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        $user = Auth::user();

        // Block suspended / inactive accounts
        if ($user->status !== 'active') {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account is ' . $user->status . '. Please contact an administrator.']);
        }

        $request->session()->regenerate();
        $user->updateQuietly(['last_login_at' => now()]); // quiet → no audit noise
        AuditLog::record('login', 'Auth', 'Signed in');

        if ($user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('home'));
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($msg = $this->recaptcha->verifyRequest($request, 'register')) {
            return back()->withErrors(['email' => $msg]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => Role::where('slug', 'consumer')->value('id'),
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        AuditLog::record('logout', 'Auth', 'Signed out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /** Invitation acceptance (public — invitee sets their own password) */
    public function showInviteAccept(string $token): Response
    {
        $invite = UserInvitation::with('role')->where('token', $token)->first();

        if (! $invite || ! $invite->isValid()) {
            return Inertia::render('Auth/AcceptInvite', ['invalid' => true]);
        }

        return Inertia::render('Auth/AcceptInvite', [
            'token' => $token,
            'email' => $invite->email,
            'role'  => $invite->role?->name,
        ]);
    }

    public function acceptInvite(Request $request, string $token)
    {
        $invite = UserInvitation::where('token', $token)->first();
        abort_if(! $invite || ! $invite->isValid(), 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $invite->email,
            'password' => Hash::make($request->password),
            'role_id' => $invite->role_id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $invite->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->updateQuietly(['last_login_at' => now()]);

        return redirect()->route($user->canAccessAdmin() ? 'admin.dashboard' : 'home');
    }
}
