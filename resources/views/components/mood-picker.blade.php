@props(['selected' => 'relief', 'anonymous' => false])

{{-- 💔 Pick the feeling of the quote (radio buttons that look like colored chips) --}}
<fieldset class="mood-picker">
    <legend class="mb-2 text-sm font-semibold">
        <x-day-night :night="__('What feeling brought you to Bawh tonight?')" :day="__('What feeling brought you to Bawh today?')" />
    </legend>
    <div class="flex flex-wrap gap-2">
        @foreach (\App\Models\Quote::MOODS as $key => $mood)
            <label class="mood-option" style="--mood: {{ $mood['color'] }}">
                <input type="radio" name="mood" value="{{ $key }}" class="sr-only" @checked($selected === $key)>
                <span>{{ $mood['emoji'] }} {{ __($mood['name']) }}</span>
            </label>
        @endforeach
    </div>
</fieldset>

{{-- 🌙 Publish without my name --}}
<label class="anon-toggle">
    <input type="checkbox" name="is_anonymous" value="1" @checked($anonymous)>
    <span class="anon-switch" aria-hidden="true"></span>
    <span>
        <span class="block font-bold"><span class="anon-moon">🌙</span> {{ __('Publish as an anonymous bawh') }}</span>
        <span class="block text-xs opacity-70">{{ __('Your name will not appear. Only you can see it is yours.') }}</span>
    </span>
</label>
