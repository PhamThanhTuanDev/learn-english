<?php

namespace App\Actions;

use App\Enums\Rating;
use App\Models\Card;
use App\Models\CardReview;
use App\Support\Sm2;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReviewCard
{
    /**
     * Reschedule the card from the learner's answer and log the review.
     *
     * The due date counts from the learner's own today, so a card answered
     * at 01:00 in Vietnam is not due a day early because UTC is still on the
     * day before.
     */
    public function handle(Card $card, Rating $rating): CardReview
    {
        $next = Sm2::next($card->ease_factor, $card->interval_days, $card->repetitions, $rating);
        $today = CarbonImmutable::parse($card->user->localNow()->toDateString());
        $dueOn = $today->addDays($next['interval_days'])->toDateString();
        $forgotten = $rating->quality() < 3 && $card->repetitions > 0;

        return DB::transaction(function () use ($card, $rating, $next, $dueOn, $forgotten) {
            $card->update([
                ...$next,
                'lapses' => $card->lapses + ($forgotten ? 1 : 0),
                'due_on' => $dueOn,
                'last_reviewed_at' => now(),
            ]);

            return $card->user->cardReviews()->create([
                'card_id' => $card->id,
                'rating' => $rating,
                'ease_factor' => $next['ease_factor'],
                'interval_days' => $next['interval_days'],
                'due_on' => $dueOn,
                'reviewed_at' => now(),
            ]);
        });
    }
}
