<?php

namespace App\Http\Controllers;

use App\Actions\AddNewCards;
use App\Actions\ReviewCard;
use App\Enums\Rating;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FlashcardController extends Controller
{
    /**
     * The review screen: today's new words are added first, then every card
     * due by today is queued, old cards before new ones.
     */
    public function index(Request $request, AddNewCards $addNewCards): View
    {
        $user = $request->user();
        $addNewCards->handle($user);

        // "Before tomorrow" rather than "<= today": SQLite stores the date
        // cast as "Y-m-d 00:00:00", which a plain date string sorts after.
        $cards = $user->cards()
            ->with('word')
            ->where('due_on', '<', $user->localNow()->addDay()->toDateString())
            ->orderByRaw('last_reviewed_at is null')
            ->orderBy('due_on')
            ->orderBy('id')
            ->get();

        return view('flashcards.index', [
            'user' => $user,
            'cards' => $cards->map(fn (Card $card) => $this->payload($card))->all(),
            'ratings' => array_map(fn (Rating $rating) => ['value' => $rating->value, 'label' => $rating->label()], Rating::cases()),
        ]);
    }

    /**
     * Record an answer. A card still due today (Again) goes back to the end
     * of the queue on the page.
     */
    public function review(Request $request, Card $card, ReviewCard $reviewCard): JsonResponse
    {
        $user = $request->user();
        abort_unless($card->user_id === $user->id, 404);

        $request->validate(['rating' => ['required', Rule::enum(Rating::class)]]);
        $reviewCard->handle($card, Rating::from($request->integer('rating')));

        return response()->json([
            'due_on' => $card->due_on->toDateString(),
            'due_today' => $card->due_on->toDateString() <= $user->localNow()->toDateString(),
        ]);
    }

    /**
     * What the Alpine review screen needs to show one card.
     *
     * @return array<string, mixed>
     */
    private function payload(Card $card): array
    {
        return [
            ...$card->word->only(['word', 'part_of_speech', 'pronunciation', 'meaning', 'definition', 'definition_vi', 'example', 'example_vi', 'level']),
            'id' => $card->id,
            'is_new' => $card->last_reviewed_at === null,
            'url' => route('flashcards.review', $card),
        ];
    }
}
