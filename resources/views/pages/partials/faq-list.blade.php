<div class="divide-y divide-line rounded-card border border-line bg-white">
    @foreach ($faqs as $faq)
        <details class="group p-4">
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                {{ $faq->question }}
                <svg class="size-5 shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                </svg>
            </summary>
            <p class="mt-2 text-muted whitespace-pre-line">{{ $faq->answer }}</p>
        </details>
    @endforeach
</div>
