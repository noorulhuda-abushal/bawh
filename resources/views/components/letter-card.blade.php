@props(['letter', 'mine' => false])

@php
    $to = $letter->recipientInfo();
    // 🛡️ Admins can delete shared letters
    $canDelete = $mine || (auth()->check() && auth()->user()->is_admin && $letter->is_public);
@endphp

{{-- 💌 One letter. It never shows the writer's name. --}}
<article id="letter-{{ $letter->id }}"
    class="letter-card reveal scroll-mt-28 {{ session('new_letter') === $letter->id ? 'just-arrived is-visible' : '' }}">
    <div class="mb-3 flex items-center justify-between gap-2">
        <span class="mood-tag">{{ __('To:') }} {{ $to['emoji'] }} {{ __($to['name']) }}</span>
        @if ($mine)
            <span class="mine-tag">{{ $letter->is_public ? '🤍 ' . __('Shared') : '🔒 ' . __('Private') }}</span>
        @endif
    </div>

    <p class="letter-body" dir="auto">{{ $letter->content }}</p>

    <div class="mt-4 flex items-center justify-between text-xs opacity-70">
        <span>🌙 {{ __('Anonymous bawh') }} · {{ $letter->created_at->translatedFormat('j M Y') }}</span>

        <span class="flex items-center gap-1">
        {{-- 🚩 Report a shared letter (not my own) --}}
        @if (auth()->check() && ! $mine && $letter->is_public)
            <button type="button" class="report-btn icon-btn" data-url="{{ route('report', ['letter', $letter->id]) }}"
                data-tip="{{ __('Report') }}" aria-label="{{ __('Report') }}">🚩</button>
        @endif

        @if ($canDelete)
            <form method="POST" action="{{ route('delete_letter', $letter) }}"
                data-confirm="{{ __('Delete this letter? This can\'t be undone.') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="icon-btn" aria-label="{{ __('Delete') }}" data-tip="{{ __('Delete') }}">⌫</button>
            </form>
        @endif
        </span>
    </div>
</article>
