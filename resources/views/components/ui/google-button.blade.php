{{-- "Continue with Google" button — real OAuth flow.
     Renders ONLY when Google sign-in is switched on and the Client ID +
     Secret are saved in Settings; otherwise it renders nothing so the
     pages fall back to email login cleanly.
     Stitch style: white card button with the Google "G".
     Usage: <x-ui.google-button /> or <x-ui.google-button label="Sign up with Google" /> --}}
@props(['label' => 'Continue with Google', 'divider' => 'or', 'dividerAfter' => false])

@if(app(App\Services\GoogleOAuthService::class)->configured())
@if(!$dividerAfter)
<div class="auth-divider"><span>{{ $divider }}</span></div>
@endif
<a href="{{ route('auth.google.redirect') }}" class="google-btn">
    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#4285F4" d="M23.5 12.3c0-.9-.1-1.5-.3-2.3H12v4.3h6.5c-.1 1.1-.8 2.7-2.4 3.8v.1l3.5 2.7h.1c2.2-2 3.8-5 3.8-8.6z"/>
        <path fill="#34A853" d="M12 24c3.2 0 6-1.1 7.9-2.9l-3.8-2.9c-1 .7-2.4 1.2-4.1 1.2-3.1 0-5.8-2.1-6.8-5h-.1l-3.7 2.9v.1C3.5 21.3 7.5 24 12 24z"/>
        <path fill="#FBBC05" d="M5.2 14.4c-.2-.7-.4-1.5-.4-2.4s.1-1.7.4-2.4v-.1L1.6 6.6l-.1.1C.5 8.2 0 10 0 12s.5 3.8 1.4 5.2l3.8-2.8z"/>
        <path fill="#EA4335" d="M12 4.7c1.8 0 3 .8 3.7 1.4l3.3-3.2C17.9 1.1 15.2 0 12 0 7.5 0 3.5 2.7 1.4 6.8l3.8 2.9c1-2.9 3.7-5 6.8-5z"/>
    </svg>
    <span>{{ $label }}</span>
</a>
@if($dividerAfter)
<div class="auth-divider"><span>{{ $divider }}</span></div>
@endif
@endif
