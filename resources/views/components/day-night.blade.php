@props(['night', 'day'])

{{-- 🌙☀️ Shows the night words in the Night theme, and the morning words in the Dawn theme --}}
<span class="t-night">{{ $night }}</span><span class="t-day">{{ $day }}</span>
