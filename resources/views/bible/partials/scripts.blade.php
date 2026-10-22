{{--
    JavaScript compartido de la Biblia (incluir una vez por página):
    - passagePicker: elegir libro → capítulo → versículo (lector y portada).
    - bibleSearch: buscador con sugerencias de libros, citas y palabras.
--}}
<script>
    // Sin tildes ni mayúsculas: «Génesis» y «genesis» son lo mismo.
    function bibleNormalize(text) {
        return (text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function bibleChapterUrl(template, slug, chapter) {
        return template.replace('__LIBRO__', slug).replace(/999$/, chapter);
    }

    /**
     * Pasos del selector. `current` (opcional) es el capítulo que se está leyendo:
     * así no hace falta pedir cuántos versículos tiene.
     */
    function passagePicker({ books, chapterUrl, countUrl, current = null }) {
        return {
            books,
            step: 'books',
            testament: current?.testament ?? 'AT',
            filter: '',
            book: null,
            chapterChoice: null,
            verseCount: 0,
            loading: false,
            loadError: false,

            shownBooks() {
                const q = bibleNormalize(this.filter);
                return q ? books.filter((b) => bibleNormalize(b.name).includes(q)) : books.filter((b) => b.testament === this.testament);
            },
            url(slug, n) { return bibleChapterUrl(chapterUrl, slug, n); },
            verseUrl(v) { return `${this.url(this.book.slug, this.chapterChoice)}?v=${v}#v${v}`; },
            chooseBook(b) { this.book = b; this.filter = ''; this.step = 'chapters'; },
            isCurrentChapter() { return !! current && this.book?.slug === current.slug && this.chapterChoice === current.chapter; },
            async chooseChapter(n) {
                this.chapterChoice = n;
                this.step = 'verses';
                this.loadError = false;
                if (this.isCurrentChapter()) { this.verseCount = current.verses; return; }
                this.loading = true;
                this.verseCount = 0;
                try {
                    const res = await fetch(countUrl.replace('__LIBRO__', this.book.slug).replace(/999(?=\/)/, n), { headers: { Accept: 'application/json' } });
                    if (! res.ok) throw new Error(res.status);
                    this.verseCount = (await res.json()).count;
                } catch { this.loadError = true; }
                this.loading = false;
            },
            // El lector los redefine (capítulo que se lee, versículo resaltado, salto sin recargar).
            isCurrent() { return false; },
            isHighlighted() { return false; },
            pickVerse() {},
        };
    }

    function bibleSearch({ books, indexUrl, chapterUrl, initial = '' }) {
        return {
            q: initial,
            open: false,
            active: -1,

            // «Juan 3:16», «gen 1», «1 co 13:4-7» → pasaje; null si no lo es.
            passage(q) {
                const m = q.match(/^(.+?)\s*(\d{1,3})(?:\s*[:.,]\s*(\d{1,3})(?:\s*-\s*(\d{1,3}))?)?$/);
                if (! m) return null;
                const name = m[1].trim();
                const prefixed = books.filter((b) => bibleNormalize(b.name).startsWith(name));
                const book = books.find((b) => bibleNormalize(b.name) === name) ?? (prefixed.length === 1 ? prefixed[0] : null);
                const chapter = +m[2];
                if (! book || chapter < 1 || chapter > book.chapters) return null;
                const verse = m[3] ? (m[4] ? `${m[3]}-${m[4]}` : m[3]) : null;
                return {
                    type: 'passage',
                    label: `${book.name} ${chapter}${verse ? ':' + verse : ''}`,
                    url: bibleChapterUrl(chapterUrl, book.slug, chapter) + (verse ? `?v=${verse}#v${m[3]}` : ''),
                };
            },
            suggestions() {
                const q = bibleNormalize(this.q);
                if (q.length < 2) return [];
                const items = [];
                const passage = this.passage(q);
                if (passage) items.push(passage);
                if (! passage) {
                    books.filter((b) => bibleNormalize(b.name).split(' ').some((word, i, words) => words.slice(i).join(' ').startsWith(q)))
                        .slice(0, 6)
                        .forEach((b) => items.push({ type: 'book', label: b.name, slug: b.slug, chapters: b.chapters, exact: bibleNormalize(b.name) === q, url: `${indexUrl}?libro=${b.slug}#libros` }));
                }
                if (q.length >= 3 && ! passage) items.push({ type: 'word', label: this.q.trim() });
                return items;
            },
            choose(item) {
                if (! item) return;
                this.open = false;
                if (item.type === 'word') { this.$refs.form.submit(); return; }
                // En la portada, el libro se abre en su ventana sin cambiar de página.
                if (item.type === 'book' && document.getElementById('libro-dialogo')) { this.$dispatch('biblia-libro', item.slug); return; }
                location.href = item.url;
            },
            enter(event) {
                const items = this.suggestions();
                const item = items[this.active] ?? items.find((i) => i.type === 'passage') ?? items.find((i) => i.exact);
                // Sin pasaje ni libro exacto, Enter busca la palabra (envío normal).
                if (item && item.type !== 'word') { event.preventDefault(); this.choose(item); }
            },
            move(step) {
                const n = this.suggestions().length;
                if (! n) return;
                this.open = true;
                this.active = (this.active + step + n) % n;
            },
        };
    }
</script>
