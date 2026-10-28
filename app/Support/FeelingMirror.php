<?php

namespace App\Support;

/**
 * 🪞 Feeling mirror: reads what the user wrote and guesses the feeling.
 *
 * How it works (a small dictionary, no AI needed):
 * 1) Clean the text (remove tashkeel, make أ إ آ = ا, ة = ه, ى = ي …)
 * 2) Look for the words of every feeling inside the text
 * 3) The feeling with the most matching words wins
 */
class FeelingMirror
{
    /**
     * The words of every feeling (Arabic: Saudi dialect + standard, and English).
     * Write them in any form, they are cleaned the same way as the text.
     */
    public const WORDS = [
        'sad' => [
            'حزين', 'حزينه', 'حزن', 'زعلان', 'زعلانه', 'زعل', 'زعلت', 'ضايق', 'ضايقه', 'ضيقه', 'ضيق', 'مكتوم', 'مكتومه',
            'تعبان', 'تعبانه', 'تعب', 'تعبت', 'تعبني', 'منهك', 'منهكه', 'مرهق', 'مرهقه', 'ابكي', 'بكيت', 'بكاء', 'دموع', 'دمعه',
            'مكسور', 'مكسوره', 'انكسرت', 'كسرني', 'موجوع', 'موجوعه', 'وجع', 'الم', 'متالم', 'متالمه', 'قهر', 'مقهور', 'مقهوره',
            'بخاطري', 'في خاطري', 'بقلبي', 'في قلبي', 'ثقيل', 'ثقيله', 'ثقل', 'شايل هم', 'شايله هم',
            'مالي خلق', 'ما لي خلق', 'مالي نفس', 'طفشان', 'طفشانه', 'طفش', 'مخنوق', 'مخنوقه', 'خنقه', 'كئيب', 'كئيبه', 'كآبه', 'هم', 'مهموم', 'مهمومه',
            'sad', 'tired', 'cry', 'crying', 'hurt', 'broken', 'pain', 'exhausted', 'down', 'depressed',
        ],
        'nostalgia' => [
            'اشتقت', 'اشتاق', 'مشتاق', 'مشتاقه', 'شوق', 'اشتياق', 'حنين', 'احن', 'حنيت', 'افتقد', 'فقدت', 'وحشني', 'وحشتني', 'توحشني',
            'ذكريات', 'ذكرى', 'تذكرت', 'اتذكر', 'زمان', 'الماضي', 'ايام زمان', 'ايام راحت', 'رجعت لي', 'ليت الايام', 'طفولتي',
            'miss', 'missing', 'memories', 'memory', 'nostalgia', 'nostalgic', 'old days', 'remember',
        ],
        'letdown' => [
            'خذلني', 'خذلوني', 'خذلان', 'انخذلت', 'خيبه', 'خاب', 'خاب ظني', 'خيبة امل', 'توقعت اكثر', 'كنت متوقع', 'كنت متوقعه', 'صدمه', 'انصدمت', 'مصدوم', 'مصدومه',
            'خانني', 'خيانه', 'غدر', 'غدروا', 'طعنه', 'كذب علي', 'كذبوا', 'باعني', 'تخلى', 'تخلوا', 'تركني', 'تركوني', 'ما وقف معي', 'ما احد وقف',
            'letdown', 'let down', 'disappointed', 'betrayed', 'betrayal', 'lied', 'abandoned',
        ],
        'love' => [
            'احب', 'احبه', 'احبها', 'احبك', 'حب', 'حبيبي', 'حبيبتي', 'عشق', 'اعشق', 'غرام', 'مغرم', 'مغرمه', 'قلبي', 'يشغل بالي', 'شاغل بالي', 'ممتن', 'ممتنه',
            'سعيد', 'سعيده', 'فرحان', 'فرحانه', 'مبسوط', 'مبسوطه', 'فرح', 'فرحت', 'سعاده', 'دفء', 'دافي', 'حنان', 'ونيس',
            'love', 'in love', 'crush', 'happy', 'grateful', 'warm', 'adore',
        ],
        'lonely' => [
            'وحيد', 'وحيده', 'وحده', 'وحدي', 'لحالي', 'بروحي', 'محد', 'ما احد', 'ماحد', 'محد يفهمني', 'محد معي', 'ما عندي احد', 'منعزل', 'منعزله', 'عزله',
            'غريب', 'غريبه', 'غربه', 'فاضي', 'فراغ', 'بعيد', 'بعيده', 'منسي', 'منسيه', 'مهمل', 'مهمله', 'ما احد يسال', 'محد يسال',
            'lonely', 'alone', 'nobody', 'no one', 'isolated', 'empty', 'forgotten',
        ],
        'hope' => [
            'امل', 'متفائل', 'متفائله', 'تفاؤل', 'بكره', 'بكرا', 'بكره احلى', 'بكره اجمل', 'احلام', 'حلم', 'احلم', 'ان شاء الله', 'بيتحسن', 'بتتحسن', 'بيصير خير',
            'طموح', 'هدف', 'بداية', 'بدايه جديده', 'فرصه', 'متحمس', 'متحمسه', 'حماس', 'قادر', 'قادره', 'اقدر', 'بقدر', 'نجاح', 'انجاز',
            'hope', 'hopeful', 'tomorrow', 'dream', 'dreams', 'excited', 'better', 'motivated', 'new start',
        ],
        'relief' => [
            'مرتاح', 'مرتاحه', 'راحه', 'ارتحت', 'هادي', 'هاديه', 'هدوء', 'اهدا', 'سلام', 'سكينه', 'طمانينه', 'مطمن', 'مطمنه', 'رضا', 'راضي', 'راضيه',
            'الحمد لله', 'الحمدلله', 'خف', 'خفيف', 'خفيفه', 'انزاح', 'تنفست', 'استرخاء', 'صفا', 'بخير', 'تمام',
            'calm', 'relief', 'relieved', 'peace', 'peaceful', 'relaxed', 'fine', 'okay', 'grateful',
        ],
    ];

    /**
     * 💬 A short kind sentence for every feeling.
     */
    public const MESSAGES = [
        'sad' => 'It sounds heavy tonight. Here are words from people who felt the same 💔',
        'nostalgia' => 'Some things stay in the heart. Here is what others wrote when they missed someone 🌙',
        'letdown' => 'You expected more, and that is okay. You are not the only one 🥀',
        'love' => 'There is love in your words. Here is more of it 🤍',
        'lonely' => 'You are not alone, even if it feels like it. Others felt it too 🌧️',
        'hope' => 'Hold on to that light. Here is some more hope ✨',
        'relief' => 'Peace looks good on you. Breathe and read these 🕊️',
    ];

    /**
     * Returns the feeling key (like "sad") or null if no word matched.
     */
    public static function detect(string $text): ?string
    {
        $text = ' ' . self::clean($text) . ' ';
        $scores = [];

        foreach (self::WORDS as $feeling => $words) {
            $scores[$feeling] = 0;
            foreach ($words as $word) {
                $word = self::clean($word);
                // Short words and English words must be a whole word ("هم" shouldn't match "مهم"),
                // longer Arabic words can be part of a word ("حزين" matches "والحزين")
                $wholeWord = mb_strlen($word) <= 3 || preg_match('/^[a-z ]+$/', $word);
                $found = $wholeWord ? str_contains($text, " $word ") : str_contains($text, $word);

                if ($word !== '' && $found) {
                    // longer phrases ("محد يفهمني") count more than one short word
                    $scores[$feeling] += mb_strlen($word) >= 6 ? 2 : 1;
                }
            }
        }

        arsort($scores);
        $best = array_key_first($scores);

        return $scores[$best] > 0 ? $best : null;
    }

    /**
     * Makes different spellings the same: removes tashkeel and tatweel,
     * أ إ آ ٱ => ا, ة => ه, ى => ي, ؤ => و, ئ => ي, and English becomes lowercase.
     */
    public static function clean(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);
        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي',
        ]);
        // keep only letters, numbers and spaces
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
