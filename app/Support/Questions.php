<?php

namespace App\Support;

/**
 * 🌙 The question of the day: a different question every day, for everyone.
 */
class Questions
{
    public const LIST = [
        "What's the thing you wished you had said, but its time passed?",
        'If you got one more chance, who would you give it to?',
        "What changed in you that nobody noticed?",
        'What small thing made you smile this week?',
        'Who do you miss without telling them?',
        'What would you tell yourself five years ago?',
        'What word do you need to hear today?',
        'What are you grateful for that you never said out loud?',
        'Which place feels like home to you?',
        'What are you still waiting for?',
        'What did you learn the hard way?',
        'What makes you feel safe?',
        'What would you do if you were not afraid?',
        'Which memory do you go back to when you feel tired?',
        'What do you want to let go of?',
        'Who taught you something you will never forget?',
        'What is a dream you never told anyone?',
        'What is the kindest thing someone did for you?',
        'If your heart could write one sentence today, what would it be?',
        'What are you proud of, even if nobody saw it?',
        'What do you wish people understood about you?',
    ];

    // Today's question (the same one for everyone today, a new one tomorrow)
    public static function today(): string
    {
        return self::LIST[now()->dayOfYear % count(self::LIST)];
    }
}
