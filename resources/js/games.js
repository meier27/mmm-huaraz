/**
 * Juegos bíblicos por niveles (/juegos/{juego}/nivel/{n}). Cada juego es un
 * componente de Alpine expuesto como función global, igual que el lector del
 * Doctrinario.
 *
 * El servidor arma el nivel y guarda la solución: aquí no hay respuestas.
 * Cada jugada se envía (send) y el servidor dice si es correcta; al terminar
 * devuelve las estrellas y, con cuenta, guarda el avance. Así el avance y los
 * premios no se pueden falsear desde el navegador.
 */

// Mayúsculas sin tildes, con la Ñ (lo mismo que en el servidor).
const normalize = (text) => text.toUpperCase().normalize('NFD').replace(/Ñ/g, 'Ñ').replace(/[̀-ͯ]/g, '').replace(/[^A-ZÑ]/g, '');

// Reloj, envío de jugadas y final: comunes a todos los juegos.
function base(config) {
    return {
        config,
        seconds: 0,
        timer: null,
        busy: false,
        finished: false,
        outcome: null,
        pending: null,
        message: '',
        error: '',

        startClock() {
            this.stopClock();
            this.timer = setInterval(() => { if (! document.hidden) this.seconds++; }, 1000);
        },
        stopClock() {
            clearInterval(this.timer);
            this.timer = null;
        },
        clock(s = this.seconds) {
            return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
        },
        // Envía una jugada. Con { defer: true } el final se guarda para mostrarlo
        // después (p. ej. tras ver la última respuesta).
        async send(action, payload = {}, { defer = false } = {}) {
            this.error = '';
            try {
                const response = await fetch(config.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    body: JSON.stringify({ action, ...payload }),
                });
                const data = await response.json().catch(() => ({}));
                if (! response.ok) {
                    this.error = response.status === 419
                        ? 'La página caducó: recárgala para seguir jugando.'
                        : (data.errors ? Object.values(data.errors).flat()[0] : data.message) || 'No se pudo comprobar la jugada.';
                    return null;
                }
                if (data.finished) {
                    this.stopClock();
                    defer ? (this.pending = data.finished) : this.end(data.finished);
                }
                return data;
            } catch {
                this.error = 'Sin conexión. Revisa tu internet e inténtalo otra vez.';
                return null;
            }
        },
        end(outcome) {
            this.outcome = outcome;
            this.finished = true;
            this.message = '';
            this.$nextTick(() => {
                this.$root.querySelector('[role=status]')?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
                this.$refs.nextLevel?.focus({ preventScroll: true });
            });
        },
        // Enlace a la cita en la Biblia del sitio («Mateo 2:1 (RVR1960)» → /biblia/buscar?q=Mateo 2:1).
        bibleLink(reference) {
            const url = this.$root.closest('[data-bible]')?.dataset.bible;
            return url && reference ? `${url}?q=${encodeURIComponent(reference.replace(/\s*\(.*\)\s*$/, ''))}` : null;
        },
        destroy() {
            this.stopClock();
        },
    };
}

/* ===================================================================
 * Trivia: una pregunta a la vez; el servidor dice si es correcta.
 * =================================================================== */
window.triviaGame = (config) => ({
    ...base(config),
    questions: config.questions,
    index: 0,
    picked: null,
    answer: null,
    reference: null,
    score: 0,

    init() { this.startClock(); },

    get current() { return this.questions[this.index]; },
    async choose(choice) {
        if (this.picked !== null || this.busy || this.finished) return;
        this.busy = true;
        this.picked = choice;
        const data = await this.send('answer', { index: this.index, choice }, { defer: true });
        this.busy = false;
        if (! data) { this.picked = null; return; }
        Object.assign(this, { answer: data.answer, reference: data.reference, score: data.score });
        this.message = data.correct ? '¡Correcto!' : `No es correcto. La respuesta es: ${data.answer}.`;
        this.$nextTick(() => this.$refs.next?.focus());
    },
    state(choice) {
        if (this.picked === null) return 'idle';
        if (this.answer === null) return choice === this.picked ? 'pending' : 'dim';
        if (choice === this.answer) return 'right';
        return choice === this.picked ? 'wrong' : 'dim';
    },
    next() {
        if (this.index + 1 < this.questions.length) {
            Object.assign(this, { index: this.index + 1, picked: null, answer: null, reference: null, message: '' });
            this.$nextTick(() => this.$refs.question?.focus());
            return;
        }
        this.end(this.pending);
    },
    // Teclas 1 a 4 para responder.
    keys(e) {
        if (e.target.closest('input, textarea, select') || e.ctrlKey || e.metaKey || e.altKey) return;
        const choice = this.current?.choices[+e.key - 1];
        if (/^[1-4]$/.test(e.key) && choice !== undefined) this.choose(choice);
    },
});

/* ===================================================================
 * Completa el versículo: se tocan las palabras para ponerlas en los espacios.
 * =================================================================== */
window.verseGame = (config) => ({
    ...base(config),
    verses: config.verses,
    index: 0,
    tokens: [],
    chips: [],
    active: 0,
    solved: false,
    reference: null,
    mistakes: 0,

    init() {
        this.load();
        this.startClock();
    },

    load() {
        const verse = this.verses[this.index];
        this.tokens = verse.tokens.map((t) => (t.blank !== undefined ? { ...t, filled: null, state: '' } : t));
        this.chips = verse.chips.map((word, id) => ({ id, word, used: false }));
        Object.assign(this, { active: 0, solved: false, reference: null, message: '' });
    },
    blanks() { return this.tokens.filter((t) => t.blank !== undefined); },
    firstEmpty() { return this.blanks().find((t) => ! t.filled)?.blank ?? null; },
    place(chip) {
        if (chip.used || this.solved || this.busy) return;
        const blank = this.blanks().find((t) => t.blank === this.active && ! t.filled) ?? this.blanks().find((t) => ! t.filled);
        if (! blank) return;
        Object.assign(blank, { filled: chip, state: '' });
        chip.used = true;
        this.active = this.firstEmpty();
        if (this.active === null) this.check();
    },
    // Tocar un espacio lleno devuelve la palabra; uno vacío lo elige como destino.
    select(token) {
        if (this.solved || this.busy || token.state === 'right') return;
        if (token.filled) {
            token.filled.used = false;
            Object.assign(token, { filled: null, state: '' });
        }
        this.active = token.blank;
    },
    async check() {
        this.busy = true;
        const blanks = this.blanks();
        const data = await this.send('check', { verse: this.index, words: blanks.map((t) => t.filled.word) }, { defer: true });
        this.busy = false;
        if (! data) return;

        blanks.forEach((t, i) => { t.state = data.results[i] ? 'right' : 'wrong'; });
        this.mistakes = data.mistakes;

        if (data.solved) {
            this.solved = true;
            this.reference = data.reference;
            this.message = `¡Muy bien! ${data.reference}`;
            this.$nextTick(() => this.$refs.next?.focus());
            return;
        }

        const wrong = blanks.filter((t) => t.state === 'wrong');
        this.message = wrong.length === 1 ? 'Una palabra no va ahí. Inténtalo otra vez.' : `${wrong.length} palabras no van ahí. Inténtalo otra vez.`;
        setTimeout(() => {
            wrong.forEach((t) => { t.filled.used = false; Object.assign(t, { filled: null, state: '' }); });
            this.active = this.firstEmpty();
        }, 900);
    },
    next() {
        if (this.index + 1 < this.verses.length) {
            this.index++;
            this.load();
            return;
        }
        this.end(this.pending);
    },
});

/* ===================================================================
 * Ordena los libros: se tocan en el orden de la Biblia.
 * =================================================================== */
window.booksGame = (config) => ({
    ...base(config),
    pool: config.pool,
    placed: [],
    mistakes: 0,
    shake: null,

    init() { this.startClock(); },

    async choose(name) {
        if (this.finished || this.busy || this.placed.includes(name)) return;
        this.busy = true;
        const data = await this.send('pick', { name });
        this.busy = false;
        if (! data) return;

        if (data.correct) {
            this.placed.push(name);
            this.message = `${name}, correcto.`;
            return;
        }
        this.mistakes = data.mistakes;
        this.shake = name;
        setTimeout(() => { if (this.shake === name) this.shake = null; }, 450);
        this.message = data.previous ? `${name} no va ahora. ¿Qué libro sigue después de ${data.previous}?` : `${name} no va primero. ¿Cuál aparece antes en la Biblia?`;
    },
});

/* ===================================================================
 * Sopa de letras: arrastrar de la primera a la última letra, o tocar ambas.
 * =================================================================== */
const COLORS = ['bg-amber-200 text-amber-950', 'bg-sky-200 text-sky-950', 'bg-emerald-200 text-emerald-950', 'bg-rose-200 text-rose-950', 'bg-violet-200 text-violet-950', 'bg-lime-200 text-lime-950', 'bg-orange-200 text-orange-950', 'bg-teal-200 text-teal-950', 'bg-fuchsia-200 text-fuchsia-950'];

window.wordSearch = (config) => ({
    ...base(config),
    size: config.size,
    letters: config.letters,
    targets: config.targets,
    found: [],
    anchor: null,
    hover: null,
    dragging: false,
    cursor: 0,

    init() { this.startClock(); },

    // Celdas en línea recta (horizontal, vertical o diagonal) entre a y b.
    line(a, b) {
        if (a === null || b === null) return [];
        const size = this.size;
        const [ra, ca, rb, cb] = [Math.floor(a / size), a % size, Math.floor(b / size), b % size];
        if (ra !== rb && ca !== cb && Math.abs(rb - ra) !== Math.abs(cb - ca)) return [a];
        const [dr, dc] = [Math.sign(rb - ra), Math.sign(cb - ca)];
        const steps = Math.max(Math.abs(rb - ra), Math.abs(cb - ca));
        return Array.from({ length: steps + 1 }, (_, i) => (ra + dr * i) * size + (ca + dc * i));
    },
    selection() { return this.line(this.anchor, this.hover ?? this.anchor); },
    isSelected(i) { return this.anchor !== null && this.selection().includes(i); },
    cellClass(i) {
        if (this.isSelected(i)) return 'scale-110 bg-brand-strong text-white shadow-md';
        const k = this.found.findIndex((f) => f.cells.includes(i));
        return k >= 0 ? COLORS[k % COLORS.length] : 'bg-cream-light text-primary';
    },
    foundTarget(i) { return this.found.find((f) => f.index === i); },
    cellAt(e) {
        const el = document.elementFromPoint(e.clientX, e.clientY)?.closest('[data-cell]');
        return el && this.$root.contains(el) ? +el.dataset.cell : null;
    },
    down(e) {
        const i = this.cellAt(e);
        if (i === null || this.finished) return;
        e.preventDefault();
        // Segundo toque (modo «tocar inicio y final»): se termina la palabra.
        // Mientras se comprueba la anterior, se puede ir marcando el inicio de la siguiente.
        if (! this.busy && this.anchor !== null && ! this.dragging && this.anchor !== i && this.line(this.anchor, i).length > 1) {
            this.hover = i;
            return this.submit();
        }
        Object.assign(this, { anchor: i, hover: i, dragging: true });
    },
    move(e) {
        if (! this.dragging) return;
        const i = this.cellAt(e);
        if (i !== null) this.hover = i;
    },
    up() {
        if (! this.dragging) return;
        this.dragging = false;
        if (this.hover !== this.anchor) this.submit();
        else this.message = 'Ahora toca la última letra de la palabra.';
    },
    // Teclado: las flechas recorren las letras (una sola parada de tabulación).
    nav(e, i) {
        const size = this.size;
        const moves = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: size, ArrowUp: -size };
        if (! (e.key in moves)) return;
        e.preventDefault();
        const j = i + moves[e.key];
        const sameRow = Math.abs(moves[e.key]) !== 1 || Math.floor(j / size) === Math.floor(i / size);
        if (j < 0 || j >= this.letters.length || ! sameRow) return;
        this.cursor = j;
        if (this.anchor !== null) this.hover = j;
        this.$root.querySelector(`[data-cell="${j}"]`)?.focus();
    },
    // Enter o espacio sobre una letra marca el inicio y luego el final.
    press(i) {
        if (this.finished || this.busy) return;
        if (this.anchor === null || this.anchor === i) { Object.assign(this, { anchor: i, hover: i, message: 'Ahora elige la última letra de la palabra.' }); return; }
        this.hover = i;
        this.submit();
    },
    async submit() {
        const cells = this.selection();
        if (cells.length < 2) { Object.assign(this, { anchor: null, hover: null }); return; }
        this.busy = true;
        Object.assign(this, { anchor: null, hover: null });
        const data = await this.send('select', { cells });
        this.busy = false;
        if (! data) return;
        if (! data.found) { this.message = `«${data.text}» no está en la lista.`; return; }
        this.found.push({ index: data.index, cells: data.cells, reference: data.reference });
        this.message = `¡Encontraste ${data.display}! (${data.reference})`;
    },
});

/* ===================================================================
 * Crucigrama: las letras se comprueban en el servidor.
 * =================================================================== */
window.crosswordGame = (config) => ({
    ...base(config),
    rows: config.rows,
    cols: config.cols,
    entries: config.entries,
    cells: [],
    active: null,
    dir: 'across',
    hints: 0,

    init() {
        this.cells = config.cells.map((c) => c && { number: c.number, entry: '', state: '', across: null, down: null });
        this.entries.forEach((e) => e.cells.forEach((i) => { this.cells[i][e.dir] = e.id; }));
        const first = this.entries[0];
        if (first) Object.assign(this, { dir: first.dir, active: first.cells[0] });
        this.startClock();
    },
    across() { return this.entries.filter((e) => e.dir === 'across').sort((a, b) => a.number - b.number); },
    down() { return this.entries.filter((e) => e.dir === 'down').sort((a, b) => a.number - b.number); },
    // Palabra en la que se está escribiendo.
    current() {
        const cell = this.cells[this.active];
        if (! cell) return null;
        return this.entries[cell[this.dir] ?? cell[this.dir === 'across' ? 'down' : 'across']] ?? null;
    },
    inCurrent(i) { return this.current()?.cells.includes(i) ?? false; },
    isFilled(entry) { return entry.cells.every((i) => this.cells[i].entry); },
    letters() {
        return Object.fromEntries(this.cells.map((c, i) => [i, c?.entry]).filter(([, v]) => v));
    },
    // Al instante: si se escribe rápido, la letra siguiente ya cae en su casilla.
    focus(i) { this.$root.querySelector(`[data-cell="${i}"]`)?.focus(); },
    // Al tocar una casilla ya activa que cruza dos palabras, cambia de sentido.
    tap(i) {
        const cell = this.cells[i];
        if (this.active === i && cell.across !== null && cell.down !== null) this.dir = this.dir === 'across' ? 'down' : 'across';
        else if (cell[this.dir] === null) this.dir = this.dir === 'across' ? 'down' : 'across';
        this.active = i;
    },
    goToEntry(entry) {
        this.dir = entry.dir;
        this.active = entry.cells.find((i) => ! this.cells[i].entry) ?? entry.cells[0];
        this.focus(this.active);
    },
    step(delta) {
        const entry = this.current();
        const next = entry?.cells[entry.cells.indexOf(this.active) + delta];
        if (next !== undefined) { this.active = next; this.focus(next); }
    },
    input(i, e) {
        const value = normalize(e.target.value).slice(-1);
        e.target.value = value;
        Object.assign(this.cells[i], { entry: value, state: '' });
        if (value) this.step(1);
        this.submitIfFull();
    },
    keydown(i, e) {
        const moves = { ArrowRight: [0, 1], ArrowLeft: [0, -1], ArrowDown: [1, 0], ArrowUp: [-1, 0] };
        if (e.key === 'Backspace' && ! this.cells[i].entry) {
            e.preventDefault();
            this.step(-1);
            const prev = this.cells[this.active];
            if (prev && prev !== this.cells[i]) Object.assign(prev, { entry: '', state: '' });
        } else if (moves[e.key]) {
            e.preventDefault();
            const [dr, dc] = moves[e.key];
            this.dir = dr ? 'down' : 'across';
            let [r, c] = [Math.floor(i / this.cols) + dr, (i % this.cols) + dc];
            while (r >= 0 && r < this.rows && c >= 0 && c < this.cols) {
                const j = r * this.cols + c;
                if (this.cells[j]) { this.active = j; this.focus(j); break; }
                r += dr; c += dc;
            }
        } else if (e.key === ' ') {
            e.preventDefault();
            this.tap(i);
        }
    },
    markWrong(wrong) {
        wrong.forEach((i) => { this.cells[i].state = 'wrong'; });
    },
    // Marca en rojo las letras equivocadas (comprobar muchas veces resta estrellas).
    async check() {
        if (this.busy) return;
        this.busy = true;
        const data = await this.send('check', { letters: this.letters() });
        this.busy = false;
        if (! data) return;
        this.markWrong(data.wrong);
        this.message = data.wrong.length ? `Hay ${data.wrong.length} ${data.wrong.length === 1 ? 'letra equivocada' : 'letras equivocadas'} (en rojo).` : 'Todo lo que escribiste está bien. ¡Sigue!';
    },
    async hint() {
        const entry = this.current();
        const pending = (j) => this.cells[j] && (! this.cells[j].entry || this.cells[j].state === 'wrong');
        // Primero la palabra actual; si ya está llena, la primera casilla pendiente del crucigrama.
        const i = entry?.cells.find(pending) ?? this.cells.findIndex((c, j) => pending(j));
        if (i < 0) return;
        if (this.busy) return;
        this.busy = true;
        const data = await this.send('hint', { cell: i });
        this.busy = false;
        if (! data) return;
        Object.assign(this.cells[i], { entry: data.letter, state: 'hint' });
        this.hints = data.hints;
        this.active = i;
        this.focus(i);
        this.submitIfFull();
    },
    // Con todas las casillas llenas, el servidor dice si está completo.
    async submitIfFull() {
        if (this.finished || this.busy || ! this.cells.every((c) => ! c || c.entry)) return;
        this.busy = true;
        const data = await this.send('submit', { letters: this.letters() });
        this.busy = false;
        if (data && ! data.done) {
            this.markWrong(data.wrong);
            this.message = `Casi: hay ${data.wrong.length} ${data.wrong.length === 1 ? 'letra equivocada' : 'letras equivocadas'} (en rojo).`;
        }
    },
});

/* ===================================================================
 * Rompecabezas: piezas que se intercambian tocando dos o arrastrando.
 * =================================================================== */
window.puzzleGame = (config) => ({
    ...base(config),
    image: config.image,
    n: config.n,
    tiles: config.tiles,
    swaps: [],
    selected: null,
    dragFrom: null,
    ratio: 1,
    peek: false,
    loading: true,
    verse: null,

    async init() {
        this.ratio = await new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(img.naturalWidth / img.naturalHeight || 1);
            img.onerror = () => resolve(1);
            img.src = this.image.url;
        });
        this.loading = false;
        this.startClock();
    },
    tileStyle(piece) {
        const n = this.n;
        const [x, y] = [piece % n, Math.floor(piece / n)];
        return `background-image: url("${this.image.url}"); background-size: ${n * 100}% ${n * 100}%; background-position: ${(x * 100) / (n - 1)}% ${(y * 100) / (n - 1)}%`;
    },
    boardStyle() {
        // El mayor tamaño que cabe en la pantalla sin deformar la imagen.
        return `aspect-ratio: ${this.ratio}; width: min(100%, (100dvh - 15rem) * ${this.ratio}, 40rem)`;
    },
    placedCount() { return this.tiles.filter((p, i) => p === i).length; },
    tileAt(e) {
        const el = document.elementFromPoint(e.clientX, e.clientY)?.closest('[data-pos]');
        return el && this.$root.contains(el) ? +el.dataset.pos : null;
    },
    down(e, pos) {
        if (this.finished || this.loading || this.busy) return;
        // Sin seleccionar texto ni el «arrastre» nativo del navegador, que cortaría el gesto.
        if (e.pointerType === 'mouse') e.preventDefault();
        this.dragFrom = pos;
    },
    up(e) {
        if (this.dragFrom === null) return;
        const from = this.dragFrom;
        const to = this.tileAt(e);
        this.dragFrom = null;
        if (to !== null && to !== from) return this.swap(from, to);
        this.tap(from);
    },
    // Toque o teclado: elegir una pieza y luego otra para cambiarlas.
    tap(pos) {
        if (this.finished || this.loading || this.busy) return;
        if (this.selected === null) { Object.assign(this, { selected: pos, message: 'Ahora toca la pieza con la que quieres cambiarla.' }); return; }
        if (this.selected === pos) { Object.assign(this, { selected: null, message: '' }); return; }
        this.swap(this.selected, pos);
    },
    async swap(a, b) {
        const tiles = [...this.tiles];
        [tiles[a], tiles[b]] = [tiles[b], tiles[a]];
        Object.assign(this, { tiles, selected: null });
        this.swaps.push([a, b]);
        this.message = `${this.placedCount()} de ${tiles.length} piezas en su lugar.`;

        if (this.placedCount() === tiles.length) {
            this.busy = true;
            const data = await this.send('submit', { swaps: this.swaps });
            this.busy = false;
            if (data?.solved) this.verse = data.verse;
        }
    },
});
