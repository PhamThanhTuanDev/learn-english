import Alpine from 'alpinejs';
import flashcardReview from './flashcard-review';

window.Alpine = Alpine;

Alpine.data('flashcardReview', flashcardReview);

Alpine.start();
