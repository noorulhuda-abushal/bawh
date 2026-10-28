<?php

namespace App\Support;

/**
 * 💙 Safety: if someone writes words about hurting themselves,
 * the site gently shows them where to find help (it never blocks or judges them).
 */
class Safety
{
    // 📞 Ministry of Health – psychological consultations call center
    public const HELPLINE = '920033360';

    // Words and phrases that may mean the person wants to hurt themselves
    public const WORDS = [
        'انتحر', 'انتحار', 'بنتحر', 'ابي انتحر', 'ابغى انتحر',
        'اقتل نفسي', 'بقتل نفسي', 'اذي نفسي', 'اذيت نفسي', 'بأذي نفسي', 'اجرح نفسي', 'جرحت نفسي',
        'ابي اموت', 'ابغى اموت', 'ودي اموت', 'نفسي اموت', 'تمنيت اموت', 'ليتني اموت', 'ليتني مت',
        'انهي حياتي', 'انهاء حياتي', 'ما ابي اعيش', 'مابي اعيش', 'ما ابغى اعيش', 'تعبت من الحياه', 'كرهت الحياه', 'كرهت حياتي',
        'suicide', 'kill myself', 'end my life', 'want to die', 'hurt myself', 'self harm', 'self-harm', 'cut myself',
    ];

    /**
     * true if the text has any of the words above.
     */
    public static function needsSupport(?string $text): bool
    {
        $text = ' ' . FeelingMirror::clean((string) $text) . ' ';

        foreach (self::WORDS as $word) {
            if (str_contains($text, FeelingMirror::clean($word))) {
                return true;
            }
        }

        return false;
    }
}
