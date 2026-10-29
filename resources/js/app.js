// =====================================================
// بوح · Bawh — interactions
// =====================================================

// 🌐 Translate a sentence using the words the page sent (window.i18n), or keep it as it is
const t = (text) => (window.i18n && window.i18n[text]) || text;

// 🧘 Does the visitor want less motion?
const reduceMotion = () =>
    document.documentElement.dataset.motion === 'reduce' ||
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// 🔔 1) Toast messages: small messages that appear and disappear
function showToast(message) {
    const area = document.getElementById('toast-area');
    if (!area || !message) return;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    area.appendChild(toast);

    // remove it after 2.5 seconds
    setTimeout(() => {
        toast.classList.add('toast-hide');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

// Send a POST request to the server and get the answer back as JSON
async function postJson(url, body = null) {
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json', 'Content-Type': 'application/json' },
        body: body ? JSON.stringify(body) : null,
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.message || t('Something went wrong, try again'));
    return data;
}

// Replay a short animation class (used for the star/heart "pop")
function replay(el, className, ms = 500) {
    el.classList.remove(className);
    void el.offsetWidth;
    el.classList.add(className);
    setTimeout(() => el.classList.remove(className), ms);
}

// ✨ 2) Stars in the sky (+ one shooting star)
function makeStars() {
    const sky = document.getElementById('stars');
    if (!sky) return;

    for (let i = 0; i < 90; i++) {
        const star = document.createElement('i');
        star.style.top = `${Math.random() * 100}%`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.setProperty('--s', `${Math.random() * 2 + 1}px`);
        star.style.setProperty('--d', `${Math.random() * 3 + 2}s`);
        star.style.setProperty('--delay', `${Math.random() * -5}s`);
        sky.appendChild(star);
    }

    const shooting = document.createElement('i');
    shooting.className = 'shooting';
    shooting.style.top = '12%';
    shooting.style.left = '75%';
    sky.appendChild(shooting);
}

// 📜 3) Things fade in when you scroll to them
function revealOnScroll() {
    const items = document.querySelectorAll('.reveal');

    if (reduceMotion() || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.12 },
    );

    // Cards next to each other appear one after another
    items.forEach((el, i) => {
        if (el.classList.contains('quote-card')) el.style.setProperty('--delay', `${(i % 2) * 0.08}s`);
        observer.observe(el);
    });
}

// 📊 4) Numbers count up when they appear
function countUp() {
    document.querySelectorAll('[data-count-to]').forEach((el) => {
        const target = Number(el.dataset.countTo);
        if (reduceMotion() || target === 0) return;

        let start = null;
        const step = (time) => {
            start ??= time;
            const progress = Math.min((time - start) / 1200, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3)));
            if (progress < 1) requestAnimationFrame(step);
        };
        el.textContent = '0';
        requestAnimationFrame(step);
    });
}

// 🫧 5) Cards tilt a little with the mouse (only on computers)
function tiltCards() {
    if (reduceMotion() || !window.matchMedia('(hover: hover)').matches) return;

    document.addEventListener('pointermove', (event) => {
        const card = event.target.closest?.('.quote-card');
        document.querySelectorAll('.quote-card[style*="--rx"]').forEach((c) => {
            if (c !== card) {
                c.style.removeProperty('--rx');
                c.style.removeProperty('--ry');
            }
        });
        if (!card) return;

        const box = card.getBoundingClientRect();
        const x = (event.clientX - box.left) / box.width - 0.5;
        const y = (event.clientY - box.top) / box.height - 0.5;
        card.style.setProperty('--rx', `${(-y * 6).toFixed(2)}deg`);
        card.style.setProperty('--ry', `${(x * 6).toFixed(2)}deg`);
    });
}

// 🗂️ 6) Tabs: All / My Quotes / Favorites
let currentTab = 'all';

function render() {
    let favCount = 0;
    let followingCount = 0;

    document.querySelectorAll('.quote-card[data-id]').forEach((card) => {
        const isFav = card.hasAttribute('data-fav');
        const isMine = card.hasAttribute('data-mine');
        if (isFav) favCount++;

        const isFollowing = card.hasAttribute('data-following');
        if (isFollowing) followingCount++;

        card.hidden =
            (currentTab === 'favorites' && !isFav) ||
            (currentTab === 'mine' && !isMine) ||
            (currentTab === 'following' && !isFollowing);
    });

    const empty = document.getElementById('no-favorites');
    if (empty) empty.hidden = !(currentTab === 'favorites' && favCount === 0);

    const noFollowing = document.getElementById('no-following');
    if (noFollowing) noFollowing.hidden = !(currentTab === 'following' && followingCount === 0);

    // "You haven't shared a quote yet" shows only in My Quotes
    const noMine = document.getElementById('no-mine');
    if (noMine) noMine.hidden = currentTab !== 'mine';
}

// 🪞 7) "This is like me" (saved in the favorites table)
async function toggleFavorite(button) {
    let data;
    try {
        data = await postJson(button.dataset.url); // { favorited: true/false, count: 3 }
    } catch (error) {
        showToast(error.message);
        return;
    }

    // The same quote can appear twice on a page (e.g. on the profile), so update all of them
    document.querySelectorAll(`.fav-btn[data-url="${button.dataset.url}"]`).forEach((btn) => {
        btn.closest('.quote-card').toggleAttribute('data-fav', data.favorited);
        btn.classList.toggle('fav-active', data.favorited);
        btn.setAttribute('aria-pressed', data.favorited);
        btn.dataset.tip = data.favorited ? t('Remove from things like me') : t('This is like me');
    });

    const counter = document.getElementById('fav-count');
    if (counter) counter.textContent = data.count;

    if (data.favorited) replay(button, 'pop');
    showToast(data.favorited ? t('Added to things like me 🪞') : t('Removed from things like me'));
    render();
}

// 👥 Follow / unfollow (on the profile page)
async function toggleFollow(button) {
    let data;
    try {
        data = await postJson(button.dataset.url); // { following: true/false, followers: 4 }
    } catch (error) {
        showToast(error.message);
        return;
    }

    button.classList.toggle('is-following', data.following);
    button.setAttribute('aria-pressed', data.following);
    button.querySelector('.follow-label').textContent = data.following ? t('Following ✓') : t('+ Follow');
    document.getElementById('followers-count').textContent = data.followers;

    const name = document.querySelector('.profile-card h1').firstChild.textContent.trim();
    showToast((data.following ? t('You are now following :name 👥') : t('You unfollowed :name')).replace(':name', name));
    replay(document.getElementById('followers-count'), 'count-bump', 600);
    if (data.following) {
        replay(button, 'pop');
        sparkleAround(button, '✦', 12);
    }
}

// ✦ Little sparkles that burst around an element
function sparkleAround(el, symbol = '✦', count = 10) {
    if (reduceMotion()) return;
    const box = el.getBoundingClientRect();
    for (let i = 0; i < count; i++) {
        const spark = document.createElement('span');
        spark.className = 'sparkle small';
        spark.textContent = symbol;
        spark.style.left = `${box.left + box.width / 2}px`;
        spark.style.top = `${box.top + box.height / 2}px`;
        const angle = (i / count) * Math.PI * 2;
        const distance = 35 + Math.random() * 30;
        spark.style.setProperty('--x', `${Math.cos(angle) * distance}px`);
        spark.style.setProperty('--y', `${Math.sin(angle) * distance}px`);
        document.body.appendChild(spark);
        setTimeout(() => spark.remove(), 1200);
    }
}

// 🫂 Soft rings that grow out of an element (like a hug)
function ripple(el) {
    if (reduceMotion()) return;
    const box = el.getBoundingClientRect();
    for (let i = 0; i < 3; i++) {
        const ring = document.createElement('span');
        ring.className = 'hug-ring';
        ring.style.left = `${box.left + box.width / 2}px`;
        ring.style.top = `${box.top + box.height / 2}px`;
        ring.style.animationDelay = `${i * 160}ms`;
        document.body.appendChild(ring);
        setTimeout(() => ring.remove(), 1400);
    }
}

// 🤍 8) Reactions ("I felt you" / "You're not alone" / "This touched me") + floating emojis
function burstHearts(button, emoji = '♥') {
    if (reduceMotion()) return;
    const box = button.getBoundingClientRect();

    for (let i = 0; i < 6; i++) {
        const heart = document.createElement('span');
        heart.className = 'heart-particle';
        heart.textContent = emoji;
        heart.style.left = `${box.left + box.width / 2 - 8}px`;
        heart.style.top = `${box.top}px`;
        heart.style.setProperty('--x', `${(Math.random() - 0.5) * 70}px`);
        heart.style.setProperty('--r', `${(Math.random() - 0.5) * 60}deg`);
        heart.style.animationDelay = `${i * 40}ms`;
        document.body.appendChild(heart);
        setTimeout(() => heart.remove(), 1200);
    }
}

async function toggleReaction(button) {
    let data;
    try {
        data = await postJson(button.dataset.url); // { active: true/false, count: 5 }
    } catch (error) {
        showToast(error.message);
        return;
    }

    // The same quote can appear twice on a page, so update every button of that reaction
    document.querySelectorAll(`.reaction-btn[data-url="${button.dataset.url}"]`).forEach((btn) => {
        btn.classList.toggle('is-active', data.active);
        btn.setAttribute('aria-pressed', data.active);
        btn.querySelector('.reaction-count').textContent = data.count;
    });

    if (data.active) {
        // every reaction has its own effect
        const type = button.dataset.url.split('/').pop();
        replay(button, 'pop');
        if (type === 'felt') burstHearts(button, '🤍');
        else if (type === 'not_alone') ripple(button);
        else sparkleAround(button, '✨', 10);
    }
}

// 💌 9) Night envelope: get a random quote from the server and open the envelope
async function openEnvelope() {
    const envelope = document.getElementById('envelope');
    const letter = document.getElementById('letter');
    const again = document.getElementById('envelope-again');

    envelope.classList.remove('is-open');
    letter.hidden = true;
    again.disabled = true;
    again.textContent = t('Opening…');

    try {
        const response = await fetch(envelope.dataset.url, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message);

        // let the flap open first, then the letter slides out
        requestAnimationFrame(() => envelope.classList.add('is-open'));
        setTimeout(() => {
            letter.style.setProperty('--mood', data.color);
            document.getElementById('letter-mood').textContent = data.mood;
            document.getElementById('letter-text').textContent = data.text;
            const author = document.getElementById('letter-author');
            author.textContent = `— ${data.author}`;
            // 🌙 anonymous quotes have no profile link
            if (data.profile) author.href = data.profile;
            else author.removeAttribute('href');
            const book = document.getElementById('letter-book');
            if (book) book.textContent = data.book || '';
            letter.hidden = false;
            document.getElementById('envelope-hint').hidden = true;
            again.hidden = false;
        }, reduceMotion() ? 0 : 450);
    } catch (error) {
        showToast(error.message || t('Something went wrong, try again'));
    } finally {
        again.disabled = false;
        again.textContent = t('Give me another thought');
    }
}

// 🎉 10) Confetti for the first quote
function confetti() {
    if (reduceMotion()) return;
    const colors = ['#ffe08a', '#a7c7ff', '#ffb3cf', '#b5ecd0', '#ffbf8a', '#f2a54a'];

    for (let i = 0; i < 80; i++) {
        const piece = document.createElement('span');
        piece.className = 'confetti';
        piece.style.left = `${Math.random() * 100}vw`;
        piece.style.background = colors[i % colors.length];
        piece.style.setProperty('--x', `${(Math.random() - 0.5) * 200}px`);
        piece.style.setProperty('--r', `${Math.random() * 720}deg`);
        piece.style.setProperty('--t', `${2 + Math.random() * 1.5}s`);
        piece.style.animationDelay = `${Math.random() * 0.4}s`;
        document.body.appendChild(piece);
        setTimeout(() => piece.remove(), 4200);
    }
}

// 👆 11) All clicks on the page
document.addEventListener('click', async (event) => {
    // 📚 Check the book with AI
    const bookCheck = event.target.closest('.book-check-btn');
    if (bookCheck) return checkBook(bookCheck);

    // 📸 Story picture
    const story = event.target.closest('.story-btn');
    if (story) return makeStory(story);

    // 🚩 Report
    const report = event.target.closest('.report-btn');
    if (report) return sendReport(report);

    // 🎧 Sounds
    const sound = event.target.closest('[data-sound]');
    if (sound) return playSound(sound.dataset.sound);

    const follow = event.target.closest('button#follow-btn');
    if (follow) return toggleFollow(follow);

    const likeMe = event.target.closest('.fav-btn');
    if (likeMe) return toggleFavorite(likeMe);

    // guests have a link to Log In instead of a button
    const reaction = event.target.closest('button.reaction-btn');
    if (reaction) return toggleReaction(reaction);

    // 🪞 Mirror: tap an example to try it
    const example = event.target.closest('[data-example]');
    if (example) {
        document.getElementById('mirror-input').value = example.dataset.example;
        return reflectFeeling();
    }

    // 🪞 Mirror: "write about this feeling" opens the share box with that feeling chosen
    if (event.target.closest('#mirror-write')) {
        const mood = document.getElementById('mirror-result').dataset.mood;
        const radio = document.querySelector(`#quote-popup input[name="mood"][value="${mood}"]`);
        if (radio) radio.checked = true;
        document.getElementById('quote-popup')?.showPopover();
        return;
    }

    // 📋 Copy the quote
    const copyBtn = event.target.closest('.copy-btn');
    if (copyBtn) {
        try {
            await navigator.clipboard.writeText(copyBtn.closest('.quote-card').dataset.quote);
            copyBtn.textContent = '✓';
            flashTip(copyBtn, copyBtn.dataset.done);
            replay(copyBtn.closest('.quote-card').querySelector('.quote-text'), 'copied', 1100);
            showToast(t('Copied to clipboard 📋'));
            setTimeout(() => (copyBtn.textContent = '⧉'), 1500);
        } catch {
            showToast(t("Couldn't copy, try again"));
        }
        return;
    }

    // 🗂️ Tabs
    const tab = event.target.closest('[data-tab]');
    if (tab) {
        currentTab = tab.dataset.tab;
        document.querySelectorAll('[data-tab]').forEach((t) => t.classList.toggle('tab-active', t === tab));
        render();
        // the cards of the new tab come in one after another
        document.querySelectorAll('.quote-grid .quote-card:not([hidden])').forEach((card, i) => {
            card.style.setProperty('--i', i);
            replay(card, 'tab-in', 900);
        });
        return;
    }

    // 💌 Envelope
    if (event.target.closest('#envelope, #envelope-again')) return openEnvelope();

    // ⬆️ Back to top
    if (event.target.closest('#to-top')) {
        replay(document.getElementById('to-top'), 'launch', 800);
        window.scrollTo({ top: 0, behavior: reduceMotion() ? 'auto' : 'smooth' });
        return;
    }

    // ⚙️ Settings
    const option = event.target.closest('[data-setting]');
    if (option) return saveSetting(option.dataset.setting, option.dataset.value, event);

    if (event.target.closest('#reset-settings')) {
        try {
            localStorage.removeItem(SETTINGS_KEY);
        } catch {}
        ['theme', 'size', 'motion'].forEach((name) => delete document.documentElement.dataset[name]);
        applyTheme();
        showSettings();
        showToast(t('Settings reset'));
        return;
    }

    // ✨ AI
    if (event.target.closest('#ai-btn')) return improveWithAi(event.target.closest('#ai-btn'));
    if (event.target.closest('#ai-use')) {
        const textarea = document.getElementById('quote-body');
        textarea.value = document.getElementById('ai-text').textContent;
        updateCounter(textarea);
        document.getElementById('ai-box').hidden = true;
        textarea.focus();
        return;
    }
    if (event.target.closest('#ai-dismiss')) document.getElementById('ai-box').hidden = true;
});

// 🗑️ 12) Delete: ask first, then fade the card out, then send the form
document.addEventListener('submit', (event) => {
    const form = event.target;

    // 📚 Suggest a book
    if (form.id === 'suggest-form') {
        event.preventDefault();
        suggestBook(form);
        return;
    }

    // 🪞 Mirror
    if (form.id === 'mirror-form') {
        event.preventDefault();
        reflectFeeling();
        return;
    }

    // 💌 Letters: play the animation first (keep / publish / let go), then send the form
    if (form.id === 'letter-form' && event.submitter && !form.dataset.flying) {
        if (!form.checkValidity()) return;
        event.preventDefault();
        sendLetter(form, event.submitter.value);
        return;
    }

    if (!form.matches('form[data-confirm]')) return;

    event.preventDefault();
    if (!confirm(form.dataset.confirm)) return;

    form.closest('.quote-card, .letter-card, .answer-card')?.classList.add('removing');
    setTimeout(() => form.submit(), reduceMotion() ? 0 : 480);
});

// 💌 Letter animations
//   keep    ✉️ → the paper folds into an envelope, a seal is stamped, and it slides down into "My letters"
//   publish 🤍 → the paper becomes a glowing star that rises to the sky and sparkles
//   release 🌙 → the paper floats up, fades, and turns into dust
function sendLetter(form, action) {
    form.dataset.flying = '1';

    // the clicked button's choice must be sent with the form
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'action';
    input.value = action;
    form.appendChild(input);

    if (reduceMotion()) return form.submit();

    form.querySelectorAll('button').forEach((b) => (b.disabled = true));
    const box = form.getBoundingClientRect();
    const centerX = box.left + box.width / 2;
    const centerY = Math.min(box.top + box.height / 2, window.innerHeight / 2);

    if (action === 'keep') {
        form.classList.add('folding');
        setTimeout(() => flyEnvelope(centerX, centerY), 550);
        setTimeout(() => form.submit(), 2300);
    } else if (action === 'publish') {
        form.classList.add('to-star');
        setTimeout(() => risingStar(centerX, centerY), 650);
        setTimeout(() => form.submit(), 2500);
    } else {
        // 🌙 the words leave one by one with the wind, then the empty paper fades away
        const total = blowWordsAway(form.querySelector('.letter-textarea'));
        setTimeout(() => {
            form.classList.add('fading-out');
            dust(centerX, centerY);
        }, total);
        setTimeout(() => showMoonGoodbye(), total + 500);
        setTimeout(() => form.submit(), total + 2000);
    }
}

// 🍃 Copies every word of the letter into its own piece, then the wind carries them away one by one
function blowWordsAway(textarea) {
    const box = textarea.getBoundingClientRect();
    const style = getComputedStyle(textarea);
    const layer = document.createElement('div');
    layer.className = 'words-layer';
    ['font', 'lineHeight', 'padding', 'direction', 'textAlign', 'letterSpacing'].forEach((prop) => (layer.style[prop] = style[prop]));
    layer.style.left = `${box.left}px`;
    layer.style.top = `${box.top}px`;
    layer.style.width = `${box.width}px`;
    layer.style.minHeight = `${box.height}px`;
    layer.dir = 'auto';

    const words = textarea.value.split(/(\s+)/);
    const pieces = [];
    words.forEach((word) => {
        if (/^\s+$/.test(word)) return layer.append(word.includes('\n') ? document.createElement('br') : ' ');
        const span = document.createElement('span');
        span.className = 'blown-word';
        span.textContent = word;
        layer.appendChild(span);
        pieces.push(span);
    });
    document.body.appendChild(layer);
    textarea.style.color = 'transparent';

    // the last words leave first (the wind blows from the end of the line)
    const step = Math.min(90, 1400 / Math.max(pieces.length, 1));
    pieces.reverse().forEach((span, i) => {
        span.style.setProperty('--x', `${-160 - Math.random() * 260}px`);
        span.style.setProperty('--y', `${-60 - Math.random() * 180}px`);
        span.style.setProperty('--r', `${(Math.random() - 0.5) * 70}deg`);
        span.style.animationDelay = `${i * step}ms`;
    });

    return pieces.length * step + 900;
}

// 🌙 A crescent moon says goodbye in the middle of the screen
function showMoonGoodbye() {
    const moon = document.createElement('div');
    moon.className = 'moon-goodbye';
    moon.innerHTML = `<span>🌙</span><p>${t('It is gone… lighter now')}</p>`;
    document.body.appendChild(moon);
}

// ✉️ An envelope appears where the paper was, gets a seal, and slides down
function flyEnvelope(x, y) {
    const env = document.createElement('div');
    env.className = 'fly-envelope';
    env.style.left = `${x}px`;
    env.style.top = `${y}px`;
    env.innerHTML = '<span class="fe-body"></span><span class="fe-flap"></span><span class="fe-seal">♥</span>';
    document.body.appendChild(env);
}

// 🤍 A glowing star rises to the sky, then bursts into sparkles
function risingStar(x, y) {
    const star = document.createElement('div');
    star.className = 'fly-star';
    star.style.left = `${x}px`;
    star.style.top = `${y}px`;
    star.style.setProperty('--rise', `${-(y - 60)}px`);
    document.body.appendChild(star);

    setTimeout(() => {
        for (let i = 0; i < 18; i++) {
            const spark = document.createElement('span');
            spark.className = 'sparkle';
            spark.textContent = i % 3 === 0 ? '✦' : '·';
            spark.style.left = `${x}px`;
            spark.style.top = '60px';
            const angle = (i / 18) * Math.PI * 2;
            spark.style.setProperty('--x', `${Math.cos(angle) * (60 + Math.random() * 60)}px`);
            spark.style.setProperty('--y', `${Math.sin(angle) * (60 + Math.random() * 60)}px`);
            document.body.appendChild(spark);
        }
    }, 1100);
}

// 🌙 Little bits of dust float up and disappear
function dust(x, y) {
    for (let i = 0; i < 26; i++) {
        const bit = document.createElement('span');
        bit.className = 'dust';
        bit.style.left = `${x + (Math.random() - 0.5) * 320}px`;
        bit.style.top = `${y + (Math.random() - 0.5) * 160}px`;
        bit.style.setProperty('--x', `${(Math.random() - 0.5) * 120}px`);
        bit.style.setProperty('--y', `${-80 - Math.random() * 220}px`);
        bit.style.animationDelay = `${Math.random() * 0.4}s`;
        document.body.appendChild(bit);
    }
}

// ✨ A short trail of tiny stars follows the mouse over the hero (computers only, night only)
function starTrail() {
    const hero = document.querySelector('.hero');
    if (!hero || reduceMotion() || !window.matchMedia('(hover: hover)').matches) return;

    let last = 0;
    hero.addEventListener('pointermove', (event) => {
        if (document.documentElement.dataset.theme === 'light' || event.timeStamp - last < 45) return;
        last = event.timeStamp;
        const dot = document.createElement('span');
        dot.className = 'trail-star';
        dot.style.left = `${event.clientX}px`;
        dot.style.top = `${event.clientY}px`;
        document.body.appendChild(dot);
        setTimeout(() => dot.remove(), 800);
    });
}

// ⚙️ The gear turns when the settings open and close
function watchSettingsGear() {
    const panel = document.getElementById('settings-panel');
    const gear = document.querySelector('[popovertarget="settings-panel"]');
    panel?.addEventListener('toggle', (event) => gear?.classList.toggle('is-open', event.newState === 'open'));
}

// 📏 A thin orange line at the top shows how far you scrolled
function scrollProgress() {
    const bar = document.getElementById('scroll-progress');
    if (!bar) return;
    const update = () => {
        const max = document.documentElement.scrollHeight - innerHeight;
        bar.style.transform = `scaleX(${max > 0 ? scrollY / max : 0})`;
    };
    addEventListener('scroll', update, { passive: true });
    update();
}

// 📚 Ask the server (and its AI) if the book exists and who wrote it
async function checkBook(button) {
    const field = button.closest('.book-field');
    const titleInput = field.querySelector('[data-book-title]');
    const authorInput = field.querySelector('[data-book-author]');
    const result = field.querySelector('.book-check-result');
    const title = titleInput.value.trim();

    if (title.length < 2) {
        result.textContent = t('Write the book title first 📖');
        titleInput.focus();
        return;
    }

    button.disabled = true;
    result.className = 'book-check-result text-sm';
    result.textContent = t('Checking the book…');

    try {
        const data = await postJson(button.dataset.url, { title, author: authorInput.value.trim() });
        if (data.status === 'found') {
            // ✓ put the correct title and author in the fields
            titleInput.value = data.title;
            if (data.author) authorInput.value = data.author;
            result.textContent = `✓ ${data.title}${data.author ? ' — ' + data.author : ''}`;
            result.classList.add('is-found');
            sparkleAround(button, '✦', 8);
        } else {
            result.textContent = data.message;
            result.classList.add(data.status === 'not_found' ? 'is-missing' : 'is-unchecked');
            if (data.status === 'not_found') replay(titleInput, 'shake', 450);
        }
    } catch (error) {
        result.textContent = error.message;
    } finally {
        button.disabled = false;
    }
}

// ✨ "Suggest me a book" in the library
async function suggestBook(form) {
    const input = document.getElementById('suggest-input');
    const button = form.querySelector('button[type="submit"]');
    const text = input.value.trim();
    if (text.length < 2) {
        showToast(t('Write at least 2 letters ✍️'));
        return;
    }

    button.disabled = true;
    button.textContent = t('Looking on the shelf…');
    replay(document.getElementById('suggest'), 'shining', 1200);

    try {
        const data = await postJson(form.dataset.url, { text });
        const box = document.getElementById('suggest-result');
        document.getElementById('suggest-support').hidden = !data.support;

        if (!data.found) {
            showToast(data.message);
            return;
        }

        const cover = document.getElementById('suggest-book');
        cover.style.setProperty('--cover', data.book.color);
        cover.href = data.book.url;
        document.getElementById('suggest-title').textContent = data.book.title;
        document.getElementById('suggest-author').textContent = data.book.author || '';
        document.getElementById('suggest-reason').textContent = data.reason;
        document.getElementById('suggest-quote').textContent = data.quote || '';
        document.getElementById('suggest-link').href = data.book.url;
        document.getElementById('suggest-by').hidden = data.by !== 'ai';
        box.hidden = false;
        replay(box, 'mirror-flash', 700);
        replay(cover, 'book-pop', 900);
    } catch (error) {
        showToast(error.message);
    } finally {
        button.disabled = false;
        button.textContent = `📖 ${t('Suggest')}`;
    }
}

// 💬 Show a little label above a button for a moment (like "Copied ✓"), then put the old label back
function flashTip(button, text) {
    const old = button.dataset.tip;
    button.dataset.tip = text;
    button.classList.add('tip-show');
    setTimeout(() => {
        button.classList.remove('tip-show');
        button.dataset.tip = old;
    }, 1600);
}

// 🚩 Report: tell the admin about harmful words
async function sendReport(button) {
    try {
        const data = await postJson(button.dataset.url);
        button.disabled = true;
        button.classList.add('is-reported');
        flashTip(button, t('Reported ✓'));
        showToast(data.message);
    } catch (error) {
        showToast(error.message);
    }
}

// 📸 Story picture: draws the quote on a 1080×1920 picture (the size of a story) and saves it
async function makeStory(button) {
    button.classList.add('is-loading');
    showToast(t('Making your story…'));

    // wait for the fonts so the picture uses Marhey
    try {
        await Promise.all([document.fonts.load('600 60px Marhey'), document.fonts.load('600 40px Fraunces')]);
    } catch {}

    const W = 1080;
    const H = 1920;
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    const isDawn = document.documentElement.dataset.theme === 'light';
    const ink = isDawn ? '#2a1a55' : '#f5f0ff';

    // 🌌 the sky (night) or 🌅 the morning (dawn)
    const sky = ctx.createLinearGradient(0, 0, 0, H);
    if (isDawn) {
        sky.addColorStop(0, '#ffe7d6');
        sky.addColorStop(0.45, '#fbe3f0');
        sky.addColorStop(1, '#ece6ff');
    } else {
        sky.addColorStop(0, '#3b1f7a');
        sky.addColorStop(0.5, '#1a0d3d');
        sky.addColorStop(1, '#0a0618');
    }
    ctx.fillStyle = sky;
    ctx.fillRect(0, 0, W, H);

    if (isDawn) {
        // ☀️ the sun
        const sun = ctx.createRadialGradient(W * 0.78, 330, 20, W * 0.78, 330, 260);
        sun.addColorStop(0, 'rgba(255,200,120,1)');
        sun.addColorStop(0.35, 'rgba(255,170,100,0.9)');
        sun.addColorStop(1, 'rgba(255,170,100,0)');
        ctx.fillStyle = sun;
        ctx.fillRect(0, 0, W, 800);
    } else {
        // ✨ stars + 🌙 moon
        for (let i = 0; i < 140; i++) {
            ctx.globalAlpha = 0.2 + Math.random() * 0.7;
            ctx.fillStyle = '#fff';
            ctx.beginPath();
            ctx.arc(Math.random() * W, Math.random() * H, Math.random() * 2.4 + 0.6, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
        ctx.fillStyle = '#fdf3c4';
        ctx.shadowColor = 'rgba(253,243,196,0.6)';
        ctx.shadowBlur = 80;
        ctx.beginPath();
        ctx.arc(W * 0.78, 330, 95, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 0;
    }

    // 💬 the card with the feeling color
    const cardX = 90;
    const cardY = 620;
    const cardW = W - 180;
    const cardH = 760;
    ctx.fillStyle = button.dataset.color;
    ctx.shadowColor = 'rgba(0,0,0,0.35)';
    ctx.shadowBlur = 60;
    ctx.shadowOffsetY = 30;
    roundRect(ctx, cardX, cardY, cardW, cardH, 60);
    ctx.fill();
    ctx.shadowColor = 'transparent';

    // the feeling tag
    const isArabic = /[\u0600-\u06FF]/.test(button.dataset.text);
    ctx.direction = isArabic ? 'rtl' : 'ltr';
    ctx.textAlign = 'center';
    ctx.fillStyle = 'rgba(255,255,255,0.6)';
    roundRect(ctx, W / 2 - 150, cardY + 60, 300, 76, 38);
    ctx.fill();
    ctx.fillStyle = '#1d1240';
    ctx.font = '600 40px Marhey, sans-serif';
    ctx.fillText(button.dataset.mood, W / 2, cardY + 112);

    // the quote, split into lines that fit the card
    let size = 72;
    let lines;
    do {
        ctx.font = `600 ${size}px Marhey, Fraunces, sans-serif`;
        lines = wrapText(ctx, button.dataset.text, cardW - 140);
        size -= 4;
    } while (lines.length * size * 1.65 > cardH - 300 && size > 36);
    const lineHeight = (size + 4) * 1.65;
    let y = cardY + 200 + (cardH - 330 - lines.length * lineHeight) / 2 + lineHeight * 0.7;
    lines.forEach((line) => {
        ctx.fillText(line, W / 2, y);
        y += lineHeight;
    });

    // the writer
    ctx.font = '600 38px Marhey, sans-serif';
    ctx.globalAlpha = 0.75;
    ctx.fillText(`— ${button.dataset.author}`, W / 2, cardY + cardH - (button.dataset.book ? 90 : 60));
    if (button.dataset.book) {
        ctx.font = '600 30px Marhey, sans-serif';
        ctx.fillText(`📖 ${button.dataset.book}`, W / 2, cardY + cardH - 42, cardW - 120);
    }
    ctx.globalAlpha = 1;

    // 🏷️ the logo: بوح + brush line + BAWH
    ctx.direction = 'rtl';
    ctx.fillStyle = ink;
    ctx.font = '600 120px Marhey, sans-serif';
    ctx.fillText('بوح', W / 2, 1600);
    ctx.strokeStyle = '#f2a54a';
    ctx.lineWidth = 9;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(W / 2 - 130, 1660);
    ctx.bezierCurveTo(W / 2 - 60, 1640, W / 2 + 60, 1640, W / 2 + 130, 1652);
    ctx.stroke();
    ctx.fillStyle = '#f2a54a';
    ctx.font = '600 34px Fraunces, serif';
    ctx.fillText('B   A   W   H', W / 2, 1720);
    ctx.fillStyle = ink;
    ctx.globalAlpha = 0.7;
    ctx.font = '600 34px Marhey, sans-serif';
    ctx.fillText(t('Bawh · A space for what we could not say'), W / 2, 1810);
    ctx.globalAlpha = 1;

    // 💾 save it (on phones: open the share menu if possible)
    canvas.toBlob(async (blob) => {
        const file = new File([blob], 'bawh-story.png', { type: 'image/png' });
        button.classList.remove('is-loading');
        if (navigator.canShare?.({ files: [file] }) && window.matchMedia('(pointer: coarse)').matches) {
            try {
                await navigator.share({ files: [file] });
                return;
            } catch {}
        }
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'bawh-story.png';
        link.click();
        setTimeout(() => URL.revokeObjectURL(link.href), 2000);
        showToast(t('Saved! Add it to your story 📸'));
        flashTip(button, t('Saved ✓'));
        sparkleAround(button, '✦', 8);
    }, 'image/png');
}

// Split a sentence into lines that fit a width
function wrapText(ctx, text, maxWidth) {
    const words = text.split(' ');
    const lines = [];
    let line = '';
    words.forEach((word) => {
        const test = line ? `${line} ${word}` : word;
        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = word;
        } else {
            line = test;
        }
    });
    if (line) lines.push(line);
    return lines;
}

// A rectangle with round corners
function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

// 🎧 Night sounds, made with the Web Audio API (no sound files):
//   🌧️ rain  = soft "noise" with high sounds + little random drops
//   🌊 waves = deeper "noise" that slowly gets louder and quieter, like the sea
let audio = null;

function playSound(kind) {
    if (audio) {
        audio.stop();
        audio = null;
    }
    document.querySelectorAll('[data-sound]').forEach((b) => b.classList.toggle('is-active', b.dataset.sound === kind));
    document.querySelector('.sounds-btn')?.classList.toggle('is-playing', kind !== 'off');
    if (kind === 'off') return showToast(t('Sound off'));

    const ctx = new (window.AudioContext || window.webkitAudioContext)();

    // 2 seconds of random noise, played in a loop
    const buffer = ctx.createBuffer(1, ctx.sampleRate * 2, ctx.sampleRate);
    const data = buffer.getChannelData(0);
    let last = 0;
    for (let i = 0; i < data.length; i++) {
        // "brown" noise is softer than pure noise
        const white = Math.random() * 2 - 1;
        last = (last + 0.02 * white) / 1.02;
        data[i] = kind === 'rain' ? white * 0.5 + last * 3 : last * 3.5;
    }
    const noise = ctx.createBufferSource();
    noise.buffer = buffer;
    noise.loop = true;

    const filter = ctx.createBiquadFilter();
    filter.type = kind === 'rain' ? 'highpass' : 'lowpass';
    filter.frequency.value = kind === 'rain' ? 900 : 500;

    const volume = ctx.createGain();
    volume.gain.value = kind === 'rain' ? 0.12 : 0.35;

    noise.connect(filter).connect(volume).connect(ctx.destination);

    const timers = [];
    if (kind === 'waves') {
        // 🌊 the sea: the volume goes up and down every ~8 seconds
        const wave = ctx.createOscillator();
        const depth = ctx.createGain();
        wave.frequency.value = 0.12;
        depth.gain.value = 0.25;
        wave.connect(depth).connect(volume.gain);
        wave.start();
    } else {
        // 🌧️ little drops
        timers.push(setInterval(() => {
            const drop = ctx.createOscillator();
            const dropVolume = ctx.createGain();
            drop.frequency.value = 1800 + Math.random() * 2400;
            dropVolume.gain.setValueAtTime(0.02, ctx.currentTime);
            dropVolume.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.05);
            drop.connect(dropVolume).connect(ctx.destination);
            drop.start();
            drop.stop(ctx.currentTime + 0.06);
        }, 90));
    }

    noise.start();
    audio = {
        stop() {
            timers.forEach(clearInterval);
            ctx.close();
        },
    };
    showToast(t('Sound on 🎧'));
}

// ⌨️ 13) Keyboard shortcuts
document.addEventListener('keydown', (event) => {
    // Ctrl + Enter (or ⌘ + Enter) sends the quote you are writing (anywhere inside its form)
    const form = event.target.closest?.('form');
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && form?.querySelector('textarea[name="content"]')) {
        event.preventDefault();
        form.requestSubmit();
        return;
    }

    // The next shortcuts don't work while typing in a field
    if (event.target.matches('input, textarea') || event.ctrlKey || event.metaKey || event.altKey) return;

    if (event.key === '/') {
        const search = document.getElementById('search-input');
        if (search) {
            event.preventDefault();
            search.focus();
        }
    }
    if (event.key.toLowerCase() === 'n' || event.key === 'ى') {
        const popup = document.getElementById('quote-popup');
        if (popup) {
            event.preventDefault();
            popup.showPopover();
        }
    }
});

// 🔢 14) Character counter: "x / 155" under every textarea that has data-count
function updateCounter(textarea) {
    const counter = textarea.closest('div, form').querySelector('.char-count');
    if (!counter) return;
    const max = textarea.maxLength;
    const length = textarea.value.length;
    counter.textContent = `${length} / ${max}`;
    counter.classList.toggle('near-limit', length >= max - 15);
    if (length >= max) replay(counter, 'shake', 450);
}

document.addEventListener('input', (event) => {
    if (event.target.matches('textarea[data-count]')) updateCounter(event.target);
});

// 🌸 15) Greeting that changes with the time of day (with the user's name)
function setGreeting() {
    const el = document.getElementById('greeting');
    if (!el) return;
    const hour = new Date().getHours();
    // Arabic uses "،" as a comma
    const comma = document.documentElement.lang === 'ar' ? '، ' : ', ';
    const name = el.dataset.name ? `${comma}${el.dataset.name}` : '';

    // ☀️ From 4 AM to 12 PM: "Good morning", the rest of the day: "Good evening"
    if (hour >= 4 && hour < 12) el.textContent = `${t('Good morning')}${name} ☀️`;
    else el.textContent = `${t('Good evening')}${name} 🌙`;
}

// ⚙️ 16) Settings: theme, text size, motion (saved in this browser)
const SETTINGS_KEY = 'quoteit-settings';
const DEFAULTS = { theme: 'dark', size: 'md', motion: 'full' };

function getSettings() {
    try {
        return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(SETTINGS_KEY)) };
    } catch {
        return { ...DEFAULTS };
    }
}

function saveSetting(name, value, event = null) {
    const settings = { ...getSettings(), [name]: value };
    try {
        localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
    } catch {}
    if (name === 'theme') changeThemeWithCircle(event);
    else document.documentElement.dataset[name] = value;
    showSettings();
}

// 🌗 The new theme spreads in a circle from the button you clicked (View Transitions)
function changeThemeWithCircle(event) {
    const html = document.documentElement;

    if (!document.startViewTransition || reduceMotion() || !event) return applyTheme();

    const x = event.clientX;
    const y = event.clientY;
    const radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

    const transition = document.startViewTransition(() => applyTheme());
    transition.ready.then(() => {
        html.animate(
            { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
            { duration: 750, easing: 'ease-in-out', pseudoElement: '::view-transition-new(root)' },
        );
    });
}

// 🕰️ Theme: night, dawn, or auto (dawn from 6 AM to 6 PM, night the rest)
function applyTheme() {
    const theme = getSettings().theme;
    const hour = new Date().getHours();
    document.documentElement.dataset.theme = theme === 'auto' ? (hour >= 6 && hour < 18 ? 'light' : 'dark') : theme;
}

// Highlight the chosen option in the settings panel
function showSettings() {
    const settings = getSettings();
    document.querySelectorAll('[data-setting]').forEach((option) => {
        option.classList.toggle('is-active', settings[option.dataset.setting] === option.dataset.value);
    });
}

// ✨ 17) AI: send the quote to the server, which asks Claude for a better wording
async function improveWithAi(button) {
    const text = document.getElementById('quote-body').value.trim();
    if (text.length < 3) {
        showToast(t('Write at least 3 characters first ✍️'));
        return;
    }

    button.disabled = true;
    button.textContent = t('✨ Thinking…');

    try {
        const data = await postJson(button.dataset.url, { content: text }); // { suggestion: "..." }
        document.getElementById('ai-text').textContent = data.suggestion;
        document.getElementById('ai-box').hidden = false;
    } catch (error) {
        showToast(error.message);
    } finally {
        button.disabled = false;
        button.textContent = t('✨ Improve with AI');
    }
}

// 🪞 18) Feeling mirror: send the words to the server, it finds the feeling with the dictionary
async function reflectFeeling() {
    const form = document.getElementById('mirror-form');
    const input = document.getElementById('mirror-input');
    const button = form.querySelector('button[type="submit"]');
    const text = input.value.trim();

    if (text.length < 2) {
        showToast(t('Write at least 2 letters ✍️'));
        input.focus();
        return;
    }

    button.disabled = true;
    button.textContent = t('Reflecting…');
    // ✨ a light sweeps across the mirror while it "reads" the feeling
    replay(document.getElementById('mirror'), 'shining', 1200);

    try {
        const response = await fetch(`${form.dataset.url}?text=${encodeURIComponent(text)}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message);
        showMirror(data);
    } catch (error) {
        showToast(error.message || t('Something went wrong, try again'));
    } finally {
        button.disabled = false;
        button.textContent = t('🪞 Reflect');
    }
}

function showMirror(data) {
    const box = document.getElementById('mirror-result');
    const label = document.getElementById('mirror-label');
    const list = document.getElementById('mirror-quotes');
    const write = document.getElementById('mirror-write');

    box.hidden = false;
    box.dataset.mood = data.mood || '';
    box.style.setProperty('--mood', data.color || '#ffffff');
    label.hidden = !data.found;
    label.textContent = data.label || '';
    // 💙 support message when the words talk about hurting oneself
    const support = document.getElementById('mirror-support');
    if (support) support.hidden = !data.support;
    const by = document.getElementById('mirror-by');
    if (by) by.hidden = data.by !== 'ai';
    document.getElementById('mirror-message').textContent = data.message;
    if (write) write.hidden = !data.found;

    // the matching quotes (made with textContent, so no code can sneak in)
    list.innerHTML = '';
    const quotes = data.quotes || [];
    if (data.found && quotes.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'opacity-80';
        empty.textContent = data.empty;
        list.appendChild(empty);
    }
    quotes.forEach((quote, i) => {
        const item = document.createElement('figure');
        item.className = 'mirror-quote';
        item.style.setProperty('--mood', quote.color);
        item.style.animationDelay = `${i * 120}ms`;

        const text = document.createElement('blockquote');
        text.className = 'quote-text';
        text.dir = 'auto';
        text.textContent = quote.text;

        const author = document.createElement(quote.profile ? 'a' : 'figcaption');
        author.className = 'mt-2 block text-sm font-bold';
        author.textContent = `— ${quote.author}`;
        if (quote.profile) author.href = quote.profile;

        item.append(text, author);
        if (quote.book) {
            const book = document.createElement('p');
            book.className = 'mt-1 text-xs opacity-75';
            book.dir = 'auto';
            book.textContent = quote.book;
            item.appendChild(book);
        }
        list.appendChild(item);
    });

    replay(box, 'mirror-flash');
}

// 🕰️ 19) The clock above the envelope ("It is 2:17 AM")
function startClock() {
    const el = document.getElementById('night-clock');
    if (!el) return;

    const tick = () => {
        const now = new Date();
        const hour = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const period = hour < 12 ? t('AM') : t('PM');
        let text = `${t('It is')} ${hour % 12 || 12}:${minutes} ${period}`;
        // After midnight (12 AM – 4 AM) is the hour of night thoughts
        if (hour < 4) text += ` · ${t("It's the hour of night thoughts 🌌")}`;
        el.textContent = text;
    };
    tick();
    setInterval(tick, 20000);
}

// ⌨️ 20) Quote of the Day types itself letter by letter
function typeQuoteOfDay() {
    const el = document.getElementById('qod-text');
    if (!el || reduceMotion()) return;

    const text = el.dataset.text;
    let i = 0;
    el.textContent = '';
    el.classList.add('typing');

    const timer = setInterval(() => {
        el.textContent = text.slice(0, ++i);
        if (i >= text.length) {
            clearInterval(timer);
            setTimeout(() => el.classList.remove('typing'), 1200);
        }
    }, 45);
}

// ⬆️ 21) Show "back to top" after scrolling down
function watchScroll() {
    const button = document.getElementById('to-top');
    if (!button) return;
    const update = () => (button.hidden = window.scrollY < 600);
    window.addEventListener('scroll', update, { passive: true });
    update();
}

// 🧭 22) Welcome tour: lights up every button one by one and explains it.
// It starts by itself the first time this device opens the site, and again from ⚙️ Settings.
const TOUR_KEY = 'bawh-tour-seen';

function startTour() {
    // only the steps whose button is on the page and visible (e.g. guests have no profile)
    const steps = (window.tourSteps || []).filter(([selector]) => {
        if (!selector) return true;
        const el = document.querySelector(selector);
        return el && el.getBoundingClientRect().width > 0;
    });
    if (!steps.length || document.querySelector('.tour')) return;

    try {
        localStorage.setItem(TOUR_KEY, '1');
    } catch {}
    document.querySelectorAll('[popover]:popover-open').forEach((p) => p.hidePopover());

    // the dark layer with a "hole" (spot) around the button, and the card that explains it
    const tour = document.createElement('div');
    tour.className = 'tour';
    tour.innerHTML = `
        <div class="tour-spot"></div>
        <div class="tour-card" role="dialog" aria-modal="true" aria-live="polite">
            <p class="tour-count"></p>
            <h3 class="tour-title"></h3>
            <p class="tour-text"></p>
            <div class="tour-dots"></div>
            <div class="tour-buttons">
                <button type="button" class="tour-skip">${t('Skip')}</button>
                <span class="flex-1"></span>
                <button type="button" class="tour-back btn-ghost">${t('Back')}</button>
                <button type="button" class="tour-next btn-primary">${t('Next')}</button>
            </div>
        </div>`;
    document.body.appendChild(tour);
    document.documentElement.classList.add('touring');

    const spot = tour.querySelector('.tour-spot');
    const card = tour.querySelector('.tour-card');
    tour.querySelector('.tour-dots').innerHTML = steps.map(() => '<span></span>').join('');
    let index = 0;
    let target = null;

    // put the spot around the button, and the card under it (or above it if there's no room)
    const place = () => {
        const gap = 12;
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const box = card.getBoundingClientRect();

        if (!target) {
            spot.style.cssText = `top:${vh / 2}px; left:${vw / 2}px; width:0; height:0;`;
            card.style.top = `${Math.max(16, (vh - box.height) / 2)}px`;
            card.style.left = `${Math.max(16, (vw - box.width) / 2)}px`;
            return;
        }

        const r = target.getBoundingClientRect();
        const pad = 8;
        spot.style.cssText = `top:${r.top - pad}px; left:${r.left - pad}px; width:${r.width + pad * 2}px; height:${r.height + pad * 2}px;`;

        const below = r.bottom + pad + gap;
        let top = below + box.height <= vh - 16 ? below : r.top - pad - gap - box.height;
        // big element (no room above or below): keep the card inside the screen, at the bottom
        if (top < 16) top = vh - box.height - 16;
        top = Math.min(Math.max(16, top), vh - box.height - 16);
        let left = r.left + r.width / 2 - box.width / 2;
        left = Math.min(Math.max(16, left), vw - box.width - 16);
        card.style.top = `${top}px`;
        card.style.left = `${left}px`;
    };

    const show = (i) => {
        index = i;
        const [selector, title, text] = steps[i];
        target = selector ? document.querySelector(selector) : null;

        tour.querySelector('.tour-count').textContent = t(':n of :total').replace(':n', i + 1).replace(':total', steps.length);
        tour.querySelector('.tour-title').textContent = title;
        tour.querySelector('.tour-text').textContent = text;
        tour.querySelectorAll('.tour-dots span').forEach((dot, k) => dot.classList.toggle('is-on', k === i));
        tour.querySelector('.tour-back').hidden = i === 0;
        tour.querySelector('.tour-skip').hidden = i === steps.length - 1;
        tour.querySelector('.tour-next').textContent = i === 0 ? t('Start') : i === steps.length - 1 ? t('Finish') : t('Next');

        card.classList.remove('is-in');
        if (target) {
            target.classList.add('is-visible'); // in case it's a section that appears on scroll
            target.scrollIntoView({ block: 'center', behavior: reduceMotion() ? 'auto' : 'smooth' });
        }
        setTimeout(() => {
            place();
            card.classList.add('is-in');
            tour.querySelector('.tour-next').focus({ preventScroll: true });
        }, target ? 450 : 50);
    };

    const end = () => {
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', place);
        document.removeEventListener('keydown', keys);
        document.documentElement.classList.remove('touring');
        tour.classList.add('is-leaving');
        setTimeout(() => tour.remove(), 300);
        if (index === steps.length - 1 && !reduceMotion()) confetti();
    };

    const rtl = document.documentElement.dir === 'rtl';
    const keys = (event) => {
        if (event.key === 'Escape') end();
        if (event.key === (rtl ? 'ArrowLeft' : 'ArrowRight')) tour.querySelector('.tour-next').click();
        if (event.key === (rtl ? 'ArrowRight' : 'ArrowLeft') && index > 0) show(index - 1);
    };

    tour.querySelector('.tour-next').addEventListener('click', () => (index < steps.length - 1 ? show(index + 1) : end()));
    tour.querySelector('.tour-back').addEventListener('click', () => show(index - 1));
    tour.querySelector('.tour-skip').addEventListener('click', end);
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, { passive: true });
    document.addEventListener('keydown', keys);

    show(0);
}

function setupTour() {
    // ⚙️ On the home page, the settings button starts the tour right away (no page reload)
    document.getElementById('start-tour')?.addEventListener('click', (event) => {
        if (!window.tourSteps) return; // other pages: the link goes to the home page with ?tour=1
        event.preventDefault();
        startTour();
    });
    if (!window.tourSteps) return;

    const url = new URL(location.href);
    const asked = url.searchParams.has('tour');
    if (asked) {
        url.searchParams.delete('tour');
        history.replaceState(null, '', url);
    }

    let seen = false;
    try {
        seen = localStorage.getItem(TOUR_KEY) === '1';
    } catch {}
    // don't start it over an open box (e.g. a quote with an error)
    if ((asked || !seen) && !document.querySelector('[popover]:popover-open')) {
        setTimeout(startTour, asked ? 300 : 1400);
    }
}

// 🚀 When the page is ready
document.addEventListener('DOMContentLoaded', () => {
    makeStars();
    setGreeting();
    startClock();
    starTrail();
    watchSettingsGear();
    scrollProgress();
    showSettings();
    render();
    revealOnScroll();
    countUp();
    tiltCards();
    watchScroll();
    typeQuoteOfDay();
    document.querySelectorAll('textarea[data-count]').forEach(updateCounter);

    // Show the message sent from the server (e.g. "Quote shared ✓"), and 🎉 for the first quote
    const toastArea = document.getElementById('toast-area');
    showToast(toastArea?.dataset.flash);
    if (toastArea?.hasAttribute('data-celebrate')) confetti();

    // If the quote had an error, open the share box again so the user can fix it
    document.querySelector('#quote-popup[data-open]')?.showPopover();

    // When editing, scroll to the quote being edited
    document.querySelector('form.quote-card textarea')?.focus();

    setupTour();
});
