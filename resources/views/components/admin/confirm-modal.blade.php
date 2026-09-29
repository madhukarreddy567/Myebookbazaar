{{-- Global destructive-action confirmation modal.
     Open from anywhere: window.confirmAction({title, message, confirmLabel, requireText, formId})
     - requireText: user must type this exact word (e.g. "DELETE") to enable confirm.
     - formId: id of the form to submit on confirm; otherwise dispatches "admin-confirmed". --}}
<div x-data="confirmModal()" @open-confirm.window="open($event.detail)"
     x-show="show" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4"
     @keydown.escape.window="cancel()" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="absolute inset-0 bg-black/50" @click="cancel()"></div>
    <div class="surface relative w-full max-w-md p-6" style="border-radius: var(--radius-modal)">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[rgb(201_60_60/0.12)] text-[var(--color-danger)]">
                <x-admin.icon name="alert" class="w-5 h-5" />
            </div>
            <div class="min-w-0">
                <h3 id="confirm-title" class="font-display text-xl font-bold text-[var(--color-text)]" x-text="title"></h3>
                <p class="mt-1 text-sm text-[var(--color-muted)]" x-text="message"></p>
            </div>
        </div>

        <template x-if="requireText">
            <div class="mt-4">
                <label class="field-label">Type <span class="font-mono font-bold" x-text="requireText"></span> to confirm</label>
                <input type="text" x-model="typed" class="input" autocomplete="off">
            </div>
        </template>

        <template x-if="needPassword">
            <div class="mt-4">
                <label class="field-label">Enter your password to continue</label>
                <input type="password" x-model="password" class="input" autocomplete="current-password">
            </div>
        </template>

        <div class="mt-6 flex justify-end gap-3 border-t border-[var(--color-border)] pt-4">
            <button type="button" class="btn btn-ghost" @click="cancel()">Cancel</button>
            <button type="button" class="btn btn-danger" :disabled="!canConfirm()" @click="confirm()">
                <span x-text="confirmLabel"></span>
            </button>
        </div>
    </div>
</div>

<script>
function confirmModal() {
    return {
        show: false, title: '', message: '', confirmLabel: 'Confirm',
        requireText: null, typed: '', needPassword: false, password: '',
        formId: null,
        open(d) {
            this.title = d.title || 'Are you sure?';
            this.message = d.message || '';
            this.confirmLabel = d.confirmLabel || 'Confirm';
            this.requireText = d.requireText || null;
            this.needPassword = !!d.needPassword;
            this.formId = d.formId || null;
            this.typed = ''; this.password = '';
            this.show = true;
            document.body.style.overflow = 'hidden';
        },
        cancel() { this.show = false; document.body.style.overflow = ''; },
        canConfirm() {
            if (this.requireText && this.typed !== this.requireText) return false;
            if (this.needPassword && !this.password) return false;
            return true;
        },
        confirm() {
            if (!this.canConfirm()) return;
            const form = this.formId ? document.getElementById(this.formId) : null;
            if (form) {
                if (this.needPassword) {
                    let i = document.createElement('input');
                    i.type = 'hidden'; i.name = '_confirm_password'; i.value = this.password;
                    form.appendChild(i);
                }
                form.submit();
            } else {
                window.dispatchEvent(new CustomEvent('admin-confirmed', { detail: { password: this.password } }));
            }
            this.cancel();
        }
    };
}
window.confirmAction = function (detail) {
    window.dispatchEvent(new CustomEvent('open-confirm', { detail: detail || {} }));
};
</script>
