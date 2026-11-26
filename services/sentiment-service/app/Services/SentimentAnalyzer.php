<?php

namespace App\Services;

class SentimentAnalyzer
{
    private const LEXICON = [
        'amazing' => 3, 'awesome' => 3, 'brilliant' => 3, 'excellent' => 3, 'fantastic' => 3, 'masterpiece' => 3,
        'perfect' => 3, 'outstanding' => 3, 'wonderful' => 3, 'superb' => 3, 'magnificent' => 3, 'incredible' => 3,
        'loved' => 3, 'love' => 3, 'best' => 3, 'magic' => 2, 'magical' => 2, 'gorgeous' => 3, 'stunning' => 3,
        'beautiful' => 2, 'great' => 2, 'good' => 2, 'enjoyed' => 2, 'enjoy' => 2, 'fun' => 2, 'funny' => 2,
        'hilarious' => 2, 'clever' => 2, 'smart' => 2, 'moving' => 2, 'touching' => 2, 'inspiring' => 2,
        'heartwarming' => 2, 'entertaining' => 2, 'gripping' => 2, 'tense' => 1, 'thrilling' => 2, 'exciting' => 2,
        'charming' => 2, 'sweet' => 2, 'recommend' => 2, 'recommended' => 2, 'liked' => 2, 'like' => 1,
        'nice' => 1, 'solid' => 1, 'decent' => 1, 'interesting' => 1, 'atmospheric' => 1, 'honest' => 1,
        'favourite' => 2, 'favorite' => 2, 'powerful' => 2, 'memorable' => 2, 'fresh' => 1, 'original' => 1,
        'cried' => 1, 'pure' => 1, 'wow' => 2, 'epic' => 2, 'well' => 1, 'strong' => 1, 'impressive' => 2,
        'awful' => -3, 'terrible' => -3, 'horrible' => -3, 'worst' => -3, 'garbage' => -3, 'trash' => -3,
        'disaster' => -3, 'hate' => -3, 'hated' => -3, 'unwatchable' => -3, 'waste' => -3, 'pathetic' => -3,
        'bad' => -2, 'boring' => -2, 'dull' => -2, 'weak' => -2, 'poor' => -2, 'stupid' => -2, 'annoying' => -2,
        'disappointing' => -2, 'disappointed' => -2, 'predictable' => -2, 'messy' => -2, 'ugly' => -2,
        'lame' => -2, 'cheap' => -2, 'pointless' => -2, 'overrated' => -2, 'mess' => -2, 'flat' => -1,
        'slow' => -1, 'long' => -1, 'confusing' => -1, 'average' => -1, 'meh' => -1, 'forgettable' => -2,
        'loud' => -1, 'silly' => -1, 'cliche' => -1, 'cliché' => -1, 'bland' => -2, 'sleep' => -1, 'asleep' => -2,
        'mediocre' => -2, 'problem' => -1, 'problems' => -1, 'fail' => -2, 'failed' => -2, 'fails' => -2,
    ];

    private const NEGATIONS = ['not', 'no', 'never', 'nothing', 'hardly', 'barely', 'isnt', 'wasnt', 'dont', 'didnt', 'doesnt', 'cant', 'couldnt', 'wont', 'aint'];

    private const BOOSTERS = ['very' => 1.5, 'really' => 1.5, 'so' => 1.4, 'extremely' => 1.8, 'super' => 1.5, 'absolutely' => 1.8, 'truly' => 1.4, 'too' => 1.2, 'quite' => 1.1];

    private const DIMINISHERS = ['bit' => 0.5, 'little' => 0.6, 'slightly' => 0.5, 'somewhat' => 0.6, 'kinda' => 0.6, 'mostly' => 0.9];

    public function analyze(string $text): array
    {
        $clean = mb_strtolower(str_replace(["'", '’'], '', $text));
        $words = preg_split('/[^\p{L}]+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $total = 0.0;
        $positive = [];
        $negative = [];

        $butIndex = -1;
        foreach ($words as $i => $word) {
            if (in_array($word, ['but', 'however', 'although', 'though'])) {
                $butIndex = $i;
            }
        }

        foreach ($words as $i => $word) {
            if (! isset(self::LEXICON[$word])) {
                continue;
            }

            $score = self::LEXICON[$word];

            for ($j = max(0, $i - 2); $j < $i; $j++) {
                if (in_array($words[$j], self::NEGATIONS)) {
                    $score = -$score * 0.8;
                }
                if (isset(self::BOOSTERS[$words[$j]])) {
                    $score *= self::BOOSTERS[$words[$j]];
                }
                if (isset(self::DIMINISHERS[$words[$j]])) {
                    $score *= self::DIMINISHERS[$words[$j]];
                }
            }

            if ($butIndex >= 0) {
                $score *= $i > $butIndex ? 1.5 : 0.5;
            }

            $total += $score;
            if ($score > 0) {
                $positive[] = $word;
            } elseif ($score < 0) {
                $negative[] = $word;
            }
        }

        $total *= 1 + min(substr_count($text, '!'), 3) * 0.1;

        $normalised = $total / sqrt(($total * $total) + 15);

        $label = 'neutral';
        if ($normalised > 0.25) {
            $label = 'positive';
        } elseif ($normalised < -0.25) {
            $label = 'negative';
        }

        return [
            'label' => $label,
            'score' => round($normalised, 2),
            'positive_words' => array_values(array_unique($positive)),
            'negative_words' => array_values(array_unique($negative)),
            'word_count' => count($words),
        ];
    }
}
