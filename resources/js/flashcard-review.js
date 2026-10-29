/**
 * The flashcard review screen (P4-04). The queue holds today's due cards;
 * an answer is saved on the server and a card still due today (Again) goes
 * back to the end of the queue.
 */
export default (cards) => ({
    queue: cards,
    flipped: false,
    busy: false,
    error: '',
    answered: 0,

    get card() {
        return this.queue[0] ?? null;
    },

    get newCount() {
        return this.queue.filter((card) => card.is_new).length;
    },

    flip() {
        if (!this.card || this.flipped) {
            return;
        }

        this.flipped = true;
        this.speak(this.card.word);
    },

    async answer(rating) {
        if (!this.card || !this.flipped || this.busy) {
            return;
        }

        this.busy = true;
        this.error = '';

        try {
            const response = await fetch(this.card.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ rating }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            const card = this.queue.shift();

            if (result.due_today) {
                this.queue.push({ ...card, is_new: false });
            }

            this.answered++;
            this.flipped = false;
        } catch {
            this.error = 'Chưa lưu được câu trả lời. Kiểm tra mạng rồi bấm lại, hoặc tải lại trang nếu đã lâu không dùng.';
        } finally {
            this.busy = false;
        }
    },

    /**
     * Read English text aloud with the browser's own voices, British first
     * to match the dictionary's pronunciation.
     */
    speak(text) {
        if (!text || !('speechSynthesis' in window)) {
            return;
        }

        const voices = window.speechSynthesis.getVoices();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-GB';
        utterance.rate = 0.9;
        utterance.voice = voices.find((voice) => voice.lang === 'en-GB')
            ?? voices.find((voice) => voice.lang.startsWith('en'))
            ?? null;

        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utterance);
    },

    onKey(event) {
        const target = event.target instanceof Element ? event.target : null;

        if (event.ctrlKey || event.metaKey || event.altKey || target?.closest('a, input, textarea, select')) {
            return;
        }

        if (event.key === ' ' || event.key === 'Enter') {
            if (this.card && !this.flipped) {
                event.preventDefault();
                this.flip();
            }
        } else if (['1', '2', '3', '4'].includes(event.key)) {
            event.preventDefault();
            this.answer(Number(event.key));
        } else if (event.key === 'p' || event.key === 'P') {
            event.preventDefault();
            this.speak(this.card?.word);
        }
    },
});
