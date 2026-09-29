{{-- Phase 6: Cloudflare Turnstile widget markup.
     Rendered by App\View\Components\Turnstile, which already checked
     TurnstileService::requiredFor($form). Expects $form and $siteKey. --}}
<div class="mt-4">
    <div class="cf-turnstile"
         data-sitekey="{{ $siteKey ?? '' }}"
         data-theme="auto"
         data-form="{{ $form ?? 'register' }}"></div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
    @error('cf-turnstile-response')
        <p style="color:#b3372f;font-size:.85rem;margin-top:.4rem" role="alert">{{ $message }}</p>
    @enderror
</div>
