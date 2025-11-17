<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /** 
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt (email OR contact number).
     */
    public function login(Request $request)
    {
        $request->validate([
'login' => [
    'required',
    'string',
    'max:50',
    function ($attribute, $value, $fail) {
        $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^(09\d{9}|9\d{9}|639\d{9}|\+639\d{9})$/', $value);

        if (!$isEmail && !$isPhone) {
            $fail("Enter a valid email or Philippine mobile number.");
        }
    }
],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($request->input('login'));
        $password   = $request->input('password');
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = null;

        if ($isEmail) {
            // Standard email login
            $user = User::where('email', $identifier)->first();
        } else {
            // Login via contact number linked in employees table
            $msisdn = $this->normalizeMsisdn($identifier);

            // Attempt to find the related user via Employee record
            $userId = Employee::where('contact_number', $msisdn)->value('user_id');
            if ($userId) {
                $user = User::find($userId);
            }
        }

        // Validation: invalid user or wrong password
        if (!$user || !Hash::check($password, $user->password)) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'These credentials do not match our records.']);
        }

        // Log user in
        Auth::login($user, remember: (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        // Update last login if column exists
        if (\Schema::hasColumn('users', 'last_login')) {
            $user->last_login = now();
            $user->save();
        }

        // Redirect based on role
        $intended = ($user->role && strtolower($user->role->name) === 'employee')
            ? route('dashboard.employee')
            : route('dashboard');

        return redirect()->intended($intended);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Normalize PH mobile numbers to 11-digit format (e.g., 09171234567)
     * Accepts: 0917..., 917..., +63917..., 63917...
     */
    private function normalizeMsisdn(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return $raw;
        }

        // Convert +63 or 63 to 0
        if (str_starts_with($digits, '63')) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            // 9171234567 -> 09171234567
            $digits = '0' . $digits;
        }

        // Ensure 11 digits max
        if (strlen($digits) > 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 0, 11);
        }

        return $digits;
    }
}
