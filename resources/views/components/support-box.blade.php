{{-- 💙 Gentle support message with the Ministry of Health psychological consultations number --}}
<div class="support-box" role="note">
    <span class="text-3xl" aria-hidden="true">💙</span>
    <div>
        <p class="font-bold">{{ __('If you are not okay, you don\'t have to carry this alone.') }}</p>
        <p class="mt-1 text-sm opacity-90">
            {{ __('Talk to someone you trust, or call a specialist for free advice at the psychological consultations center:') }}
            <a href="tel:{{ \App\Support\Safety::HELPLINE }}" class="helpline" dir="ltr">{{ \App\Support\Safety::HELPLINE }}</a>
        </p>
    </div>
</div>
