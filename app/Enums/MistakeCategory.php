<?php

namespace App\Enums;

/**
 * The eight kinds of mistake in the roadmap's mistake log (appendix A).
 */
enum MistakeCategory: string
{
    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';
    case Pronunciation = 'pronunciation';
    case Spelling = 'spelling';
    case Listening = 'listening';
    case Question = 'question';
    case Coherence = 'coherence';
    case Timing = 'timing';

    public function label(): string
    {
        return match ($this) {
            self::Grammar => 'Ngữ pháp',
            self::Vocabulary => 'Từ vựng/collocation',
            self::Pronunciation => 'Phát âm',
            self::Spelling => 'Chính tả',
            self::Listening => 'Nghe nhầm',
            self::Question => 'Hiểu sai câu hỏi',
            self::Coherence => 'Liên kết/ý',
            self::Timing => 'Quản lý thời gian',
        };
    }
}
