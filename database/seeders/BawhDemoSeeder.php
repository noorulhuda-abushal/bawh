<?php

namespace Database\Seeders;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 🌱 Fills Bawh with demo accounts, quotes, reactions, follows, letters and answers,
 * so the site looks alive for the demo video.
 *
 * Run it with:  php artisan db:seed --class=BawhDemoSeeder
 * Every demo account has the password: password123
 * Running it again does nothing (it checks if the demo users are already there).
 */
class BawhDemoSeeder extends Seeder
{
    // 👥 Demo users: [name, email]
    private const USERS = [
        ['سارة', 'sara@bawh.test'],
        ['ريم', 'reem@bawh.test'],
        ['لمى', 'lama@bawh.test'],
        ['جود', 'joud@bawh.test'],
        ['شهد', 'shahad@bawh.test'],
        ['رهف', 'rahaf@bawh.test'],
        ['غلا', 'ghala@bawh.test'],
        ['دانة', 'dana@bawh.test'],
        ['ياسمين', 'yasmeen@bawh.test'],
        ['مها', 'maha@bawh.test'],
        ['Lina', 'lina@bawh.test'],
        ['نوف', 'nouf@bawh.test'],
    ];

    // ✍️ Demo quotes: [text, feeling, anonymous?]
    private const QUOTES = [
        ['بعض الناس يمرون في حياتنا مثل المطر، يتركون الأرض أجمل.', 'love', false],
        ['اشتقت لنسخة مني كانت تضحك من قلبها.', 'nostalgia', false],
        ['تعبت أشرح نفسي لناس قرروا ما يفهمون.', 'sad', true],
        ['محد يدري كم معركة خضتها بصمت.', 'lonely', false],
        ['توقعت منك أكثر، بس تعلمت.', 'letdown', true],
        ['بكرة أجمل، وأنا متأكدة.', 'hope', false],
        ['الحمد لله على راحة البال، أغلى من أي شي.', 'relief', false],
        ['بعض الأماكن ما نزورها، بس نشتاق لها كل يوم.', 'nostalgia', false],
        ['كل نهاية تعلمني بداية جديدة.', 'hope', false],
        ['أحياناً نحتاج أحد يسمعنا بس، بدون نصيحة.', 'lonely', false],
        ['قلبي صار يعرف متى يسكت.', 'sad', false],
        ['أجمل شي إنك تكون نفسك قدام أحد يحبك كذا.', 'love', false],
        ['ما راح أرجع لنفس المكان اللي كسرني.', 'letdown', false],
        ['اليوم قررت أكون لطيفة مع نفسي.', 'relief', false],
        ['ريحة القهوة الصبح تذكرني بأمي.', 'nostalgia', false],
        ['مو كل اللي يضحك مرتاح.', 'sad', true],
        ['الشخص الصح يجي بوقته، لا تستعجل.', 'hope', false],
        ['صرت أحب الهدوء أكثر من أي وقت.', 'relief', false],
        ['كنت أظن إني قوية، طلعت أقوى.', 'hope', false],
        ['في ناس وجودهم وطن.', 'love', false],
        ['اشتقت لأيام ما كان عندي فيها هم.', 'nostalgia', true],
        ['أصعب شي إنك تكون بين ناس وتحس إنك لحالك.', 'lonely', false],
        ['ما ندمت إني أعطيت، ندمت إني ما عرفت لمين أعطي.', 'letdown', false],
        ['خلاص، تعلمت أفرح بالأشياء الصغيرة.', 'relief', false],
        ['صوت المطر يرتب فوضى قلبي.', 'relief', false],
        ['لا تنطفي عشان أحد ما شاف نورك.', 'hope', false],
        ['كل ما أحاول أنسى، تجي أغنية وترجعني.', 'nostalgia', false],
        ['أحبك بطريقة ما أعرف أشرحها.', 'love', true],
        ['تعودت أقول «تمام» حتى لما ما كنت تمام.', 'sad', false],
        ['Some words stay with us forever.', 'nostalgia', false],
        ['Be gentle with yourself, you are doing your best.', 'hope', false],
        ['اللي يبيك يدورك، واللي ما يبيك لا تدوره.', 'letdown', false],
        ['الليل طويل، بس الصبح دايم يجي.', 'hope', false],
        ['أكثر شي يوجع: الكلام اللي ما انقال.', 'sad', false],
        ['صرت أختار راحتي بدون ما أعتذر.', 'relief', false],
        ['وحشتني ضحكتنا القديمة.', 'nostalgia', false],
    ];

    // 💌 Shared letters: [recipient, text]
    private const LETTERS = [
        ['missed', "كل ما مرّت أغنيتنا تذكرتك.\nما قدرت أقولها لك، بس اشتقت."],
        ['old_self', 'سامحيني إني ما سمعتك وقتها. كنتِ على حق.'],
        ['lost', 'لو تدري كم أحتاجك اليوم. الله يرحمك ويجمعنا فيك.'],
        ['loved', 'شكراً إنك بقيت، حتى لما كنت صعبة.'],
        ['letdown', 'ما زعلت إنك رحت، زعلت إني صدقتك.'],
        ['old_self', 'كنتِ خايفة من أشياء ما صارت أبداً. ارتاحي.'],
    ];

    // 🌙 Answers to today's question
    private const ANSWERS = [
        'إني كافية مثل ما أنا.',
        'ليتني قلت لأبوي إني أحبه كل يوم.',
        'كلمة «فخورين فيك».',
        'إن الأشياء الحلوة جاية.',
        'إني ما كنت غلطانة.',
    ];

    public function run(): void
    {
        if (User::where('email', self::USERS[0][1])->exists()) {
            $this->command?->warn('The demo data is already there ✓');

            return;
        }

        // 👥 Users (joined on different days)
        $users = collect(self::USERS)->map(function ($info, $i) {
            $user = User::create(['name' => $info[0], 'email' => $info[1], 'password' => 'password123']);
            $user->created_at = now()->subDays(40 - $i * 2);
            $user->save();

            return $user;
        });

        // ✍️ Quotes, spread over the last 4 weeks and at different hours
        $quotes = collect(self::QUOTES)->map(function ($info, $i) use ($users) {
            [$text, $mood, $anonymous] = $info;
            $quote = $users[$i % $users->count()]->quotes()->create([
                'content' => $text,
                'mood' => $mood,
                'is_anonymous' => $anonymous,
            ]);
            $quote->created_at = now()->subDays(28 - intdiv($i * 28, count(self::QUOTES)))->setTime(($i * 5) % 24, ($i * 13) % 60);
            $quote->save();

            return $quote;
        });

        // 🤍 🫂 ✨ Reactions: every quote gets some, from different people
        $types = array_keys(Quote::REACTIONS);
        foreach ($quotes as $i => $quote) {
            foreach ($users as $j => $user) {
                if ($user->id === $quote->user_id || ($i + $j) % 3 !== 0) {
                    continue;
                }
                $quote->reactions()->create(['user_id' => $user->id, 'type' => $types[($i + $j) % 3]]);
            }
        }

        // 🪞 "Like me" + 👥 follows
        foreach ($users as $j => $user) {
            $user->favorites()->syncWithoutDetaching($quotes->filter(fn ($q, $i) => ($i + $j) % 7 === 0)->pluck('id'));
            $user->following()->syncWithoutDetaching(
                $users->filter(fn ($other, $k) => $other->id !== $user->id && ($j + $k) % 3 === 0)->pluck('id')
            );
        }

        // 💌 Shared letters (without names)
        foreach (self::LETTERS as $i => [$recipient, $text]) {
            $users[($i * 2) % $users->count()]->letters()->create([
                'recipient' => $recipient,
                'content' => $text,
                'is_public' => true,
            ]);
        }

        // 🌙 Answers to today's question
        foreach (self::ANSWERS as $i => $text) {
            $users[($i * 3 + 1) % $users->count()]->answers()->create([
                'question_date' => now()->toDateString(),
                'content' => $text,
            ]);
        }

        $this->command?->info('🌱 Bawh is full of life now! ' . $users->count() . ' users, ' . $quotes->count() . ' quotes.');
    }
}
