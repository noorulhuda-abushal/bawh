@extends('Layout.app')

@section('title', __('Home'))
@section('content')
    @php
        // Which quote is being edited right now (from the link ?edit=ID)
        $editingId = request()->filled('edit') ? (int) request('edit') : null;

        // Keep the current filters when building a link (e.g. change the feeling but keep the search)
        $filters = array_filter(['mood' => $mood, 'q' => $search, 'sort' => $sort === 'top' ? 'top' : null]);
    @endphp

    {{-- 🌙 HERO --}}
    <section class="hero">
        <div class="moon" aria-hidden="true"></div>

        <div class="mx-auto max-w-5xl px-6 pt-10 pb-6 md:pt-16">
            {{-- 🏷️ The slogan --}}
            <p class="kicker reveal">{{ __('BAWH') }} · {{ __('A space for what we could not say') }}</p>

            {{-- One line on big screens --}}
            {{-- ✨ Every word rises on its own, one after another.
                 🌙 At night: "Say what is in your heart tonight."  ☀️ In the morning (Dawn theme): "Start your day with a word that describes what is in your heart." --}}
            @php
                $titles = [
                    'night' => [[__('Say what is in'), false], [__('your heart'), true], [__('tonight.'), false]],
                    'day' => [[__('Start your day with a word that describes what is in'), false], [__('your heart'), true], ['.', false]],
                ];
            @endphp
            @foreach ($titles as $time => $parts)
                @php
                    $words = [];
                    foreach ($parts as [$text, $orange]) {
                        foreach (explode(' ', $text) as $w) $words[] = [$w, $orange];
                    }
                    // the final dot sticks to the word before it
                    if (end($words)[0] === '.') { array_pop($words); $words[count($words) - 1][0] .= '.'; }
                    $label = implode(' ', array_column($words, 0));
                @endphp
                <h1 class="hero-title t-{{ $time }} {{ $time === 'day' ? 'hero-title-day' : '' }}" aria-label="{{ $label }}">
                    @foreach ($words as $i => [$word, $orange])
                        <span class="word {{ $orange ? 'text-tuwaiq-orange' : '' }}" style="--i: {{ $i }}" aria-hidden="true">{{ $word }}</span>
                    @endforeach
                </h1>
            @endforeach

            @auth
                <p class="hero-sub reveal flex flex-wrap items-center gap-3">
                    <span id="greeting" data-name="{{ auth()->user()->name }}"></span>
                    {{-- 🔥 Days in a row I wrote something --}}
                    @if ($streak > 0)
                        <span class="streak-pill" title="{{ __('Days in a row you wrote something') }}">🔥 {{ __('Writing streak: :count', ['count' => $streak]) }}</span>
                    @endif
                </p>
            @endauth
            <p class="hero-sub reveal">{{ __('Write it, pick a feeling, and let your words light up someone else\'s sky.') }}</p>

            <div class="mt-7 flex flex-wrap gap-3 reveal">
                @auth
                    <button type="button" popovertarget="quote-popup" class="btn-glow">✍️ {{ __('Write your bawh') }}</button>
                @else
                    <a href="{{ route('signup') }}" class="btn-glow">✍️ {{ __('Start writing') }}</a>
                @endauth
                <a href="#mirror" class="btn-ghost">🪞 {{ __('Feeling mirror') }}</a>
                <a href="{{ route('letters') }}" class="btn-ghost">💌 {{ __('A letter that won\'t be sent') }}</a>
                <a href="{{ route('library') }}" class="btn-ghost">📚 {{ __('Library') }}</a>
            </div>

            {{-- 📊 Numbers about the site (they count up when they appear) --}}
            <dl class="stats reveal">
                <div><dt>{{ __('Quotes') }}</dt><dd data-count-to="{{ $stats['quotes'] }}">{{ $stats['quotes'] }}</dd></div>
                <div><dt>{{ __('Writers') }}</dt><dd data-count-to="{{ $stats['writers'] }}">{{ $stats['writers'] }}</dd></div>
                <div><dt>{{ __('Reactions') }}</dt><dd data-count-to="{{ $stats['reactions'] }}">{{ $stats['reactions'] }}</dd></div>
                <div><dt>{{ __('Books') }}</dt><dd data-count-to="{{ $stats['books'] }}">{{ $stats['books'] }}</dd></div>
            </dl>
        </div>
    </section>

    <div class="mx-auto max-w-5xl space-y-14 px-6 pb-24">

        {{-- 🎲 Quote of the Day --}}
        @if ($quoteOfDay)
            @php $qodMood = $quoteOfDay->moodInfo(); @endphp
            <section class="quote-of-day reveal" style="--mood: {{ $qodMood['color'] }}">
                <span class="badge">{{ __('✨ Quote of the Day') }}</span>
                {{-- ⌨️ The text types itself letter by letter (app.js). Screen readers read the full text. --}}
                <p id="qod-text" dir="auto" data-text="{{ $quoteOfDay->quoted() }}" aria-label="{{ $quoteOfDay->content }}"
                    class="qod-text">{{ $quoteOfDay->quoted() }}</p>
                @if ($quoteOfDay->is_anonymous)
                    <p class="mt-5 text-sm opacity-90">🌙 {{ __('Anonymous bawh') }} <span class="opacity-70">· {{ $qodMood['emoji'] }} {{ __($qodMood['name']) }}</span></p>
                @else
                    <a href="{{ route('profile', $quoteOfDay->user) }}" class="mt-5 inline-flex items-center gap-2 text-sm hover:underline">
                        <x-avatar :user="$quoteOfDay->user" size="sm" />
                        <span class="opacity-90">{{ $quoteOfDay->user->name }}</span>
                        <span class="opacity-70">· {{ $qodMood['emoji'] }} {{ __($qodMood['name']) }}</span>
                    </a>
                @endif
            </section>
        @endif

        {{-- 🪞 FEELING MIRROR: write how you feel, the dictionary finds the feeling and shows quotes --}}
        <section id="mirror" class="mirror reveal scroll-mt-24">
            <p class="kicker">01 / {{ __('Feeling mirror') }} 🪞</p>
            <h2 class="section-title">{{ __('If your feeling today was a word, what would it be?') }}</h2>

            <form id="mirror-form" data-url="{{ route('mirror') }}" class="mirror-form">
                <input id="mirror-input" type="text" maxlength="200" required autocomplete="off"
                    class="input" aria-label="{{ __('Your feeling') }}"
                    placeholder="{{ __('e.g. tired, I miss the old days, nobody gets me…') }}">
                <button type="submit" class="btn-glow shrink-0">🪞 {{ __('Reflect') }}</button>
            </form>

            {{-- Quick examples: tap one to try it --}}
            <div class="mt-3 flex flex-wrap gap-2">
                {{-- 🌙/☀️ the first example changes with the time of day --}}
                <button type="button" class="mirror-chip t-night" data-example="{{ __('I feel heavy tonight') }}">{{ __('I feel heavy tonight') }}</button>
                <button type="button" class="mirror-chip t-day" data-example="{{ __('I feel heavy today') }}">{{ __('I feel heavy today') }}</button>
                @foreach (['I miss days that are gone', 'Nobody understands me', 'Tomorrow will be better', 'I expected more', 'I need some peace'] as $example)
                    <button type="button" class="mirror-chip" data-example="{{ __($example) }}">{{ __($example) }}</button>
                @endforeach
            </div>

            {{-- The answer appears here (app.js) --}}
            <div id="mirror-result" class="mirror-result" hidden aria-live="polite">
                <div class="flex flex-wrap items-center gap-2">
                    <span id="mirror-label" class="mood-tag"></span>
                    <span id="mirror-by" class="ai-tag" hidden>✨ {{ __('Read by AI') }}</span>
                </div>
                <p id="mirror-message" class="mt-3 font-bold"></p>
                <div id="mirror-support" hidden class="mt-4"><x-support-box /></div>
                <div id="mirror-quotes" class="mt-4 grid gap-3"></div>
                @auth
                    <button type="button" id="mirror-write" class="btn-ghost mt-4">✍️ {{ __('Write about this feeling') }}</button>
                @endauth
            </div>
        </section>

        {{-- ✍️ Composer: logged-in users open the share box, guests go to Log In --}}
        @auth
            <button type="button" popovertarget="quote-popup" class="composer reveal">
                <x-avatar :user="auth()->user()" />
                <span class="flex-1 text-start opacity-70"><x-day-night :night="__('What\'s on your mind tonight? ✍️')" :day="__('What\'s on your mind today? ✍️')" /></span>
                <span class="composer-btn">{{ __('Share') }}</span>
            </button>
        @else
            <a href="{{ route('login') }}" class="composer reveal">
                <span class="flex-1 ps-2 text-start opacity-80">{{ __('Log in to share a quote ✍️') }}</span>
                <span class="composer-btn">{{ __('Log In') }}</span>
            </a>
        @endauth

        {{-- 💬 QUOTES --}}
        <section id="quotes" class="scroll-mt-24 space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="kicker">02 / {{ __('From the heart') }}</p>
                    <h2 class="section-title">{{ __('How do you feel right now?') }}</h2>
                </div>

                {{-- 🔍 Search (sent to the server) --}}
                <form method="GET" action="{{ route('home') }}#quotes" class="search" role="search">
                    @if ($mood) <input type="hidden" name="mood" value="{{ $mood }}"> @endif
                    @if ($sort === 'top') <input type="hidden" name="sort" value="top"> @endif
                    <span aria-hidden="true">🔍</span>
                    <input id="search-input" type="search" name="q" value="{{ $search }}"
                        placeholder="{{ __('Search quotes…') }}" aria-label="{{ __('Search') }}">
                </form>
            </div>

            {{-- 💔 Filter by feeling --}}
            <nav class="flex flex-wrap gap-2" data-tour="moods" aria-label="{{ __('Feelings') }}">
                <a href="{{ route('home', \Illuminate\Support\Arr::except($filters, 'mood')) }}#quotes"
                    class="mood-chip {{ $mood ? '' : 'is-active' }}" style="--mood: #ffffff">✦ {{ __('All') }}</a>
                @foreach (\App\Models\Quote::MOODS as $key => $info)
                    <a href="{{ route('home', [...$filters, 'mood' => $key]) }}#quotes"
                        class="mood-chip {{ $mood === $key ? 'is-active' : '' }}" style="--mood: {{ $info['color'] }}">
                        {{ $info['emoji'] }} {{ __($info['name']) }}
                    </a>
                @endforeach
            </nav>

            {{-- 🗂️ Tabs (logged-in users) + 🔥 sort --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                @auth
                    <div class="tabs">
                        <button type="button" data-tab="all" class="tab tab-active">{{ __('All') }}</button>
                        <button type="button" data-tab="mine" class="tab">{{ __('✎ My Quotes') }} ({{ $myQuotesCount }})</button>
                        <button type="button" data-tab="following" class="tab">👥 {{ __('Following') }} ({{ count($followingIds) }})</button>
                        <button type="button" data-tab="favorites" class="tab">🪞 {{ __('Things like me') }} (<span id="fav-count">{{ count($favoriteIds) }}</span>)</button>
                    </div>
                @else
                    <span></span>
                @endauth

                <div class="tabs">
                    <a href="{{ route('home', \Illuminate\Support\Arr::except($filters, 'sort')) }}#quotes" class="tab {{ $sort === 'new' ? 'tab-active' : '' }}">🕊️ {{ __('Newest') }}</a>
                    <a href="{{ route('home', [...$filters, 'sort' => 'top']) }}#quotes" class="tab {{ $sort === 'top' ? 'tab-active' : '' }}">🔥 {{ __('Most felt') }}</a>
                </div>
            </div>

            @if ($search !== '')
                <p class="opacity-80">
                    {{ __(':count results for “:q”', ['count' => $quotes->count(), 'q' => $search]) }}
                    · <a href="{{ route('home', \Illuminate\Support\Arr::except($filters, 'q')) }}#quotes" class="underline">{{ __('Clear search') }}</a>
                </p>
            @endif

            {{-- Error message if the quote wasn't valid --}}
            @if ($errors->any())
                <div class="rounded-2xl bg-red-100 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <p id="no-following" hidden class="empty-box">{{ __('Nothing here yet. Open someone\'s profile and tap "Follow" to see their words here.') }}</p>
            <p id="no-favorites" hidden class="empty-box">{{ __('Nothing here yet. Tap 🪞 on any quote that feels like you.') }}</p>

            @auth
                @if ($myQuotesCount === 0)
                    <button type="button" id="no-mine" hidden popovertarget="quote-popup" class="empty-box w-full border-dashed">
                        {{ __("✍️ You haven't shared a quote yet —") }}
                        <span class="font-semibold text-tuwaiq-orange">{{ __('tap here to write your first one') }}</span>
                    </button>
                @endif
            @endauth

            <div class="quote-grid">
                @forelse ($quotes as $quote)
                    @if (auth()->id() === $quote->user_id && $quote->id === $editingId)
                        {{-- ✎ Edit mode (my quote only) --}}
                        <form id="quote-{{ $quote->id }}" method="POST" action="{{ route('edit_quote', $quote) }}"
                            class="quote-card space-y-4" style="--mood: {{ $quote->moodInfo()['color'] }}">
                            @csrf
                            @method('PUT')

                            <textarea name="content" rows="3" required minlength="3" maxlength="155" class="input"
                                data-count autofocus>{{ old('content', $quote->content) }}</textarea>
                            <p class="char-count text-end text-xs opacity-70">0 / 155</p>

                            <x-mood-picker :selected="old('mood', $quote->mood)" :anonymous="$quote->is_anonymous" />
                            <x-book-field :book="$quote->book" />

                            <div class="flex gap-2">
                                <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                                <a href="{{ route('home') }}#quotes" class="px-4 py-2 hover:underline">{{ __('Cancel') }}</a>
                            </div>
                        </form>
                    @else
                        {{-- The card design lives in resources/views/components/quote-card.blade.php --}}
                        <x-quote-card :quote="$quote" :favorite-ids="$favoriteIds" :reacted="$reacted" :following-ids="$followingIds" />
                    @endif
                @empty
                    <p class="empty-box col-span-full">
                        @if ($search !== '' || $mood)
                            {{ __('Nothing here yet. Try another feeling or word.') }}
                        @else
                            {{ __('No quotes yet. Be the first to share one!') }}
                        @endif
                    </p>
                @endforelse
            </div>
        </section>

        {{-- 🟠 Moving band --}}
        <div class="band" aria-hidden="true">
            <div class="band-track">
                @for ($i = 0; $i < 2; $i++)
                    <span>✦ {{ __('A space for what we could not say') }} ✦ {{ __('Your words matter') }} ✦ {{ __('Someone needs to read this') }} ✦ {{ __('You are not alone') }}</span>
                @endfor
            </div>
        </div>

        {{-- 🌙 Question of the day: everyone answers, without names --}}
        <section id="question" class="question-box reveal scroll-mt-24">
            <p class="kicker">03 / <x-day-night :night="__('Tonight\'s question')" :day="__('Today\'s question')" /> 🌙</p>
            <h2 class="section-title question-text">«{{ $question }}»</h2>

            @auth
                <form method="POST" action="{{ route('store_answer') }}" class="mt-5 flex flex-wrap gap-3">
                    @csrf
                    <input type="text" name="answer" required minlength="2" maxlength="200" class="input flex-[1_1_16rem]"
                        placeholder="{{ __('Your answer… (no one will see your name)') }}" aria-label="{{ __('Your answer') }}">
                    <button type="submit" class="btn-glow shrink-0">🌙 {{ __('Answer') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn-ghost mt-5">{{ __('Log in to answer') }}</a>
            @endauth

            <div class="answers-grid mt-6">
                @forelse ($answers as $answer)
                    <article id="answer-{{ $answer->id }}"
                        class="answer-card scroll-mt-28 {{ session('new_answer') === $answer->id ? 'just-arrived' : '' }}" style="--i: {{ $loop->index }}">
                        <p dir="auto">{{ $answer->content }}</p>
                        <div class="mt-3 flex items-center justify-between text-xs opacity-70">
                            <span>🌙 {{ __('Anonymous') }} · {{ $answer->created_at->diffForHumans() }}</span>
                            <span class="flex items-center gap-1">
                                @auth
                                    @if ($answer->user_id !== auth()->id())
                                        <button type="button" class="report-btn icon-btn" data-url="{{ route('report', ['answer', $answer->id]) }}"
                                            data-tip="{{ __('Report') }}" aria-label="{{ __('Report') }}">🚩</button>
                                    @endif
                                    @if ($answer->user_id === auth()->id() || auth()->user()->is_admin)
                                        <form method="POST" action="{{ route('delete_answer', $answer) }}" data-confirm="{{ __('Delete this answer?') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn" aria-label="{{ __('Delete') }}" data-tip="{{ __('Delete') }}">⌫</button>
                                        </form>
                                    @endif
                                @endauth
                            </span>
                        </div>
                    </article>
                @empty
                    <p class="empty-box col-span-full">{{ __('No answers yet. Be the first to answer 🌙') }}</p>
                @endforelse
            </div>
        </section>

        {{-- 🌌 Night thoughts: the time now + a random quote in an envelope --}}
        <section class="reveal">
            <p class="kicker">04 / {{ __('Night thoughts') }} 🌌</p>
            <h2 class="section-title"><x-day-night :night="__('Open tonight\'s envelope')" :day="__('Open today\'s envelope')" /> 💌</h2>

            <div class="envelope-box">
                {{-- 🕰️ The time now, e.g. "2:17 AM" (app.js) --}}
                <p id="night-clock" class="night-clock" aria-live="off"></p>

                <button type="button" id="envelope" class="envelope" data-url="{{ route('random_quote') }}"
                    aria-label="{{ __('Open the envelope') }}">
                    <span class="env-heart">♥</span>
                    <span class="env-flap"></span>
                    <span class="env-body"></span>
                </button>

                <div id="letter" class="letter" hidden>
                    <span id="letter-mood" class="mood-tag"></span>
                    <p id="letter-text" class="quote-text mt-3" dir="auto"></p>
                    <a id="letter-author" class="mt-3 inline-block font-bold hover:underline"></a>
                    <p id="letter-book" class="mt-1 text-sm opacity-75" dir="auto"></p>
                </div>

                <p id="envelope-hint" class="opacity-80">{{ __('Every time, a random bawh from someone. Tap the envelope.') }}</p>
                <button type="button" id="envelope-again" class="btn-ghost" hidden>{{ __('Give me another thought') }}</button>
            </div>
        </section>

        {{-- 💌 Letters that won't be sent --}}
        <section class="letters-teaser reveal">
            <div>
                <p class="kicker">05 / {{ __('A letter that won\'t be sent') }} 💌</p>
                <h2 class="section-title">{{ __('Who do you wish you could say something to, but couldn\'t?') }}</h2>
                <p class="hero-sub mt-2">{{ __('Write it here. Keep it, let it go, or share it without your name.') }}</p>
            </div>
            <a href="{{ route('letters') }}" class="btn-glow shrink-0">✉️ {{ __('Write your letter') }}</a>
        </section>
    </div>

    @auth
        {{-- ➕ Floating button --}}
        <button type="button" popovertarget="quote-popup" aria-label="{{ __('Share a quote') }}" class="fab">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                stroke="currentColor" class="h-8 w-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </button>

        {{-- ✍️ Share pop-up (opens again by itself if the quote had an error) --}}
        <div id="quote-popup" popover @if ((($errors->has('content') || $errors->has('book_title')) && ! $editingId) || $prefillBook) data-open @endif class="panel">
            <form method="POST" action="{{ route('store_quote') }}" class="space-y-4">
                @csrf
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold">{{ __('Share a quote') }}</h2>
                    <button type="button" popovertarget="quote-popup" popovertargetaction="hide"
                        aria-label="{{ __('Close') }}" class="close-btn">✕</button>
                </div>

                <div>
                    <textarea id="quote-body" name="content" rows="4" required minlength="3" maxlength="155" autofocus
                        data-count placeholder="{{ __('Write your quote…') }}"
                        class="input peer resize-none user-invalid:border-red-500">{{ $editingId ? '' : old('content') }}</textarea>
                    <div class="mt-1 flex justify-between text-xs opacity-70">
                        <span class="peer-user-invalid:text-red-600">{{ __('3–155 characters.') }}</span>
                        {{-- 🔢 Character counter --}}
                        <span class="char-count">0 / 155</span>
                    </div>
                </div>

                <x-mood-picker :selected="old('mood', 'relief')" :anonymous="(bool) old('is_anonymous')" />
                <x-book-field :book="$prefillBook" />

                {{-- ✨ AI: suggests a better wording (only if the AI key is set in .env) --}}
                @if ($aiEnabled)
                    <div class="space-y-3">
                        <button type="button" id="ai-btn" data-url="{{ route('ai_improve') }}" class="ai-btn">
                            {{ __('✨ Improve with AI') }}
                        </button>

                        <div id="ai-box" hidden class="rounded-2xl bg-tuwaiq-purple/10 p-4 ring-1 ring-tuwaiq-purple/20">
                            <p class="text-xs font-semibold">{{ __('✨ AI suggestion') }}</p>
                            <p id="ai-text" class="mt-1"></p>
                            <div class="mt-3 flex gap-2">
                                <button type="button" id="ai-use" class="btn-secondary text-sm">{{ __('Use it') }}</button>
                                <button type="button" id="ai-dismiss" class="px-3 text-sm opacity-70 hover:underline">{{ __('Keep mine') }}</button>
                            </div>
                        </div>
                    </div>
                @endif

                <button type="submit" class="btn-primary w-full">{{ __('Send') }} <span class="opacity-60 text-sm">(Ctrl + Enter)</span></button>
            </form>
        </div>
    @endauth

    {{-- 🧭 Welcome tour: every step = [what to light up, title, text]. app.js shows them one by one --}}
    @php
        $tourSteps = [
            [null, __('Welcome to Bawh 🤍'), __('A space for what we couldn\'t say. Let me show you around in one minute.')],
            ['.logo', __('Home'), __('Tap the logo anytime to come back to the home page.')],
            auth()->check()
                ? ['.hero [popovertarget="quote-popup"]', __('Write your bawh ✍️'), __('Write what is in your heart, pick a feeling, and share it with your name or anonymously.')]
                : ['.site-nav .btn-primary', __('Log in'), __('Log in or create an account to write your own quotes and react to others.')],
            ['.quote-of-day', __('✨ Quote of the Day'), __('A new quote every day, chosen from what people wrote.')],
            ['#mirror', __('🪞 Feeling mirror'), __('Write how you feel in a few words, and we will find quotes that feel the same.')],
            ['[data-tour="moods"]', __('Feelings'), __('Show only the quotes of one feeling: sad, love, hope…')],
            ['#search-input', __('Search'), __('Look for any word inside the quotes.')],
            ['#quotes .reactions', __('Reactions'), __('Tell the writer how their words reached you: 🤍 I felt it, 🫂 You\'re not alone, ✨ It touched me.')],
            ['#quotes .card-actions', __('Quote buttons'), __('Copy the quote, save it as a story picture, or report it if it is not okay.')],
            ['#question', __('Tonight\'s question'), __('A new question every day. Answer it and read what others answered.')],
            ['#envelope', __('💌 The envelope'), __('Open it and get a random quote, like a letter from a stranger.')],
            ['.site-nav a[href$="/library"]', __('📚 Library'), __('Books that people quoted from, a free reading link when there is one, and an AI that suggests a book for your feeling.')],
            ['.site-nav a[href$="/letters"]', __('💌 Letters'), __('Write a letter you will never send. Keep it for yourself or share it without your name.')],
            ['.sounds-btn', __('🎧 Sounds'), __('Rain, waves, a fireplace… calm sounds to write with.')],
            ['[popovertarget="settings-panel"]', __('⚙️ Settings'), __('Language, night or dawn theme, text size, motion, and this tour again.')],
            auth()->check() ? ['.site-nav a[href*="/users/"]', __('👤 Your profile'), __('Your quotes, your followers and the people you follow.')] : null,
            [null, __('That\'s it! 🎉'), __('Say what is in your heart tonight. You can watch this tour again from ⚙️ Settings.')],
        ];
    @endphp
    <script>
        window.tourSteps = @json(array_values(array_filter($tourSteps)));
    </script>
@endsection
