<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private array $providers = ['google', 'github', 'facebook'];

    public function providers()
    {
        return collect($this->providers)
            ->filter(fn ($p) => config("services.$p.client_id") && config("services.$p.client_secret"))
            ->values();
    }

    public function redirect(string $provider)
    {
        if (! $this->isEnabled($provider)) {
            return redirect($this->frontend('/login?error='.urlencode(ucfirst($provider).' login is not configured.')));
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(string $provider)
    {
        if (! $this->isEnabled($provider)) {
            return redirect($this->frontend('/login?error=provider'));
        }

        try {
            $social = Socialite::driver($provider)->stateless()->user();
        } catch (Throwable $e) {
            return redirect($this->frontend('/login?error='.urlencode('Could not log in with '.ucfirst($provider).'.')));
        }

        $email = $social->getEmail() ? strtolower($social->getEmail()) : $provider.'_'.$social->getId().'@users.reelhouse.local';

        $user = User::where('provider', $provider)->where('provider_id', $social->getId())->first()
            ?? User::where('email', $email)->first();

        if (! $user) {
            $user = new User;
            $user->name = $social->getName() ?: ($social->getNickname() ?: 'Movie fan');
            $user->email = $email;
            $user->username = $this->uniqueUsername($social->getNickname() ?: Str::before($email, '@'));
            $user->email_verified_at = now();
        }

        $user->provider = $provider;
        $user->provider_id = $social->getId();
        $user->save();

        if ($user->is_banned) {
            return redirect($this->frontend('/login?error='.urlencode('Your account has been suspended.')));
        }

        $token = $user->createToken($provider)->plainTextToken;

        return redirect($this->frontend('/auth/callback#token='.$token));
    }

    private function isEnabled(string $provider): bool
    {
        return in_array($provider, $this->providers) && config("services.$provider.client_id");
    }

    private function uniqueUsername(string $base): string
    {
        $base = Str::lower(Str::slug($base, '_')) ?: 'user';
        $base = substr($base, 0, 24);
        $name = $base;
        $i = 1;
        while (User::where('username', $name)->exists()) {
            $name = $base.'_'.$i++;
        }

        return $name;
    }

    private function frontend(string $path): string
    {
        return rtrim(config('services.frontend_url'), '/').$path;
    }
}
