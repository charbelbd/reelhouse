<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|alpha_dash|min:3|max:30|unique:users,username',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $user = new User;
        $user->name = $data['name'];
        $user->username = strtolower($data['username']);
        $user->email = strtolower($data['email']);
        $user->password = $data['password'];
        $user->save();

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully !',
            'token' => $token,
            'user' => $this->profile($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower($data['email']))->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Wrong email or password.'], 401);
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'Your account has been suspended by an administrator.'], 403);
        }

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully !',
            'token' => $token,
            'user' => $this->profile($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return $this->profile($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|alpha_dash|min:3|max:30|unique:users,username,'.$user->id,
            'email' => 'required|email|max:150|unique:users,email,'.$user->id,
            'bio' => 'nullable|string|max:500',
            'avatar_url' => 'nullable|url|max:500',
        ]);

        $user->fill($data);
        $user->username = strtolower($data['username']);
        $user->save();

        return response()->json(['message' => 'Profile updated !', 'user' => $this->profile($user)]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => $user->password ? 'required|string' : 'nullable',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        if ($user->password && ! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'The current password is not correct.', 'errors' => ['current_password' => ['The current password is not correct.']]], 422);
        }

        $user->password = $request->password;
        $user->save();

        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password changed !']);
    }

    public function updatePrivacy(Request $request)
    {
        $data = $request->validate([
            'show_reviews' => 'required|boolean',
            'show_ratings' => 'required|boolean',
            'show_lists' => 'required|boolean',
            'show_watch_activity' => 'required|boolean',
            'show_follows' => 'required|boolean',
        ]);

        $user = $request->user();
        $user->fill($data)->save();

        return response()->json(['message' => 'Privacy settings saved !', 'user' => $this->profile($user)]);
    }

    private function profile(User $user): array
    {
        return array_merge($user->toArray(), [
            'has_password' => ! empty($user->password),
            'is_admin' => $user->isAdmin(),
        ]);
    }
}
