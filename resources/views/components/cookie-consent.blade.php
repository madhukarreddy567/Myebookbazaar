{{-- Phase 6: cookie consent banner.
     Shows only when the consent switch is ON and the visitor has not
     chosen yet. Choice is stored in the `meb_cookie_consent` cookie
     (accepted|declined, 1 year). Optional tracking scripts pushed to the
     `consent-scripts` stack render ONLY when the cookie says "accepted"
     (server-side gate in layouts/app.blade.php). --}}
@if(settings_bool('security.cookie_consent_enabled', false) && request()->cookie('meb_cookie_consent') === null)
    <div id="cookie-consent-banner" role="dialog" aria-live="polite" aria-label="Cookie consent"
         class="fixed bottom-0 inset-x-0 z-[90] px-4 pb-4">
        <div class="max-w-container mx-auto rounded-2xl shadow-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4"
             style="background: #0d3b47; color: #eaf4f5">
            <p class="text-sm flex-1">
                <strong>We use cookies.</strong>
                Essential cookies keep you signed in and your cart working.
                Optional analytics cookies help us improve the store — only if you accept.
                Read our <a href="{{ route('privacy') }}" class="underline underline-offset-2 font-bold">privacy policy</a>.
            </p>
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="mebCookieConsent('declined')"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold border border-white/30 hover:bg-white/10 min-h-[44px]">
                    Decline
                </button>
                <button type="button" onclick="mebCookieConsent('accepted')"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold min-h-[44px]"
                        style="background: var(--color-saffron-500); color: #1a1a1a">
                    Accept
                </button>
            </div>
        </div>
    </div>
    <script>
        function mebCookieConsent(choice) {
            // Set via server-side endpoint for reliable persistence.
            fetch('{{ route('cookie.consent') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ choice: choice })
            }).finally(function() {
                // Fallback: also set via JS in case the fetch fails.
                var expires = new Date();
                expires.setFullYear(expires.getFullYear() + 1);
                var secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = 'meb_cookie_consent=' + choice
                    + '; expires=' + expires.toUTCString()
                    + '; path=/' + secure + '; SameSite=Lax';
                var banner = document.getElementById('cookie-consent-banner');
                if (banner) banner.remove();
                if (choice === 'accepted') location.reload();
            });
        }
    </script>
@endif
