{{-- Reusable listing card partial
     Props: $listing (required), $isFeatured (optional, default false)
--}}
<a href="{{ route('listings.show', $listing->id) }}"
   class="block bg-white rounded-2xl border border-zinc-100 overflow-hidden transition-all duration-200 group"
   style="box-shadow:0 1px 3px rgba(0,0,0,0.05);"
   onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'"
   onmouseleave="this.style.transform='';this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">

    {{-- Image area --}}
    <div class="relative bg-zinc-50 overflow-hidden" style="height:180px;">
        {{-- Favorite (heart) toggle — additive, self-contained Alpine island.
             The card root is a single <a>, so @click.prevent.stop keeps the heart
             from navigating. Guests are sent to /login (same pattern as revealPhone). --}}
        <div class="absolute top-2 start-2 z-10"
             x-data="{
                 fav: {{ (auth()->check() && auth()->user()->isFavorited($listing->id)) ? 'true' : 'false' }},
                 loading: false,
                 toggle() {
                     @guest window.location.href = '{{ route('login') }}'; return; @endguest
                     if (this.loading) return;
                     this.loading = true;
                     fetch('{{ route('listings.favorite', $listing->id) }}', {
                         method: 'POST',
                         headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                     })
                     .then(r => r.status === 401 ? (window.location.href = '{{ route('login') }}', null) : r.json())
                     .then(d => { if (d) this.fav = d.favorited; })
                     .finally(() => this.loading = false);
                 }
             }">
            <button type="button" @click.prevent.stop="toggle()" :disabled="loading"
                    :aria-pressed="fav"
                    :aria-label="fav ? '{{ __('ui.favorites.remove_tooltip') }}' : '{{ __('ui.favorites.add_tooltip') }}'"
                    :title="fav ? '{{ __('ui.favorites.remove_tooltip') }}' : '{{ __('ui.favorites.add_tooltip') }}'"
                    class="w-9 h-9 rounded-full bg-white/90 backdrop-blur-sm shadow-sm flex items-center justify-center transition-all hover:scale-110 active:scale-95 disabled:opacity-60">
                <svg class="w-5 h-5 transition-colors" viewBox="0 0 24 24"
                     :fill="fav ? '#ef4444' : 'none'" :stroke="fav ? '#ef4444' : '#71717a'" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>
        </div>

        @if($listing->hasMedia('images'))
            <img src="{{ $listing->getFirstMediaUrl('images', 'card') }}"
                 alt="{{ $listing->title }}"
                 class="w-full h-full object-cover"
                 loading="lazy">
        @else
            <div class="w-full h-full flex flex-col items-center justify-center"
                 style="background:linear-gradient(135deg,#f0faf5,#d6f3e7);">
                <svg class="w-8 h-8 text-nilex/25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        {{-- Featured badge --}}
        @if($isFeatured ?? false)
            <div class="absolute top-2 end-2">
                <span class="text-[10px] font-black px-2.5 py-1 rounded-full"
                      style="background:#f59e0b; color:#451a03;">
                    {{ __('ui.sections.featured_plain') }}
                </span>
            </div>
        @endif
    </div>

    {{-- Body --}}
    <div class="p-4">
        {{-- Category --}}
        @if($listing->category)
            <span class="text-[11px] font-semibold text-nilex bg-nilex/8 px-2 py-0.5 rounded-full inline-block mb-2">
                {{ $listing->category->name }}
            </span>
        @endif

        {{-- Title --}}
        <h3 class="font-bold text-zinc-900 text-sm mb-2 line-clamp-1 group-hover:text-nilex transition-colors duration-200">
            {{ $listing->title }}
        </h3>

        @if($listing->relationLoaded('user') ? $listing->user : $listing->user()->first())
            @include('frontend.partials.business-badge', ['seller' => $listing->user])
        @endif

        {{-- Footer --}}
        <div class="flex items-center justify-between pt-3 border-t border-zinc-50">
            <span class="font-black text-nilex text-sm">
                @if($listing->price > 0)
                    {{ number_format($listing->price) }}
                    <span class="text-zinc-400 font-semibold text-[10px] ms-0.5">{{ __('ui.sections.currency') }}</span>
                @else
                    <span class="text-zinc-400 font-semibold text-xs">{{ __('ui.sections.price_on_contact') }}</span>
                @endif
            </span>
            <span class="text-[10px] text-zinc-400">
                {{ $listing->created_at->diffForHumans() }}
            </span>
        </div>
    </div>
</a>
