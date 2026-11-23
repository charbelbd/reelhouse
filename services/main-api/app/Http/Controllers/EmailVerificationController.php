<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Your email is already verified.', 'verified' => true]);
        }

        $token = Crypt::encryptString(json_encode(['id' => $user->id, 'exp' => now()->addHours(24)->timestamp]));
        $url = rtrim(config('services.frontend_url'), '/').'/verify-email?token='.urlencode($token);

        Mail::raw("Hi {$user->name},\n\nConfirm your email address to finish setting up Reelhouse:\n\n{$url}\n\nThe link works for 24 hours. If you didn't create an account, you can ignore this message.", function ($m) use ($user) {
            $m->to($user->email)->subject('Confirm your Reelhouse email');
        });

        return response()->json(['message' => 'We sent a confirmation link to '.$user->email.'.', 'verified' => false]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        try {
            $data = json_decode(Crypt::decryptString($request->input('token')), true);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'invalid', 'message' => 'This link isn’t valid.'], 422);
        }

        if (($data['exp'] ?? 0) < now()->timestamp) {
            return response()->json(['status' => 'expired', 'message' => 'This link has expired.'], 422);
        }

        $user = User::find($data['id'] ?? 0);
        if (! $user) {
            return response()->json(['status' => 'invalid', 'message' => 'This link isn’t valid.'], 422);
        }

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        return response()->json(['status' => 'verified', 'message' => 'Your email is confirmed.']);
    }
}
