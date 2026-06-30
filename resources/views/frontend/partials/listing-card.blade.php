{{-- Reusable listing card partial
     Props: $listing (required), $isFeatured (optional, default false)
--}}
<a href="{{ route('listings.show', $listing->id) }}"
   class="block card-listing group">

    {{-- Image area --}}
    <div class="relative overflow-hidden">
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
                    class="fav-icon-btn disabled:opacity-60">
                <svg class="w-5 h-5 transition-colors" viewBox="0 0 24 24"
                     :fill="fav ? '#ef4444' : 'none'" :stroke="fav ? '#ef4444' : '#71717a'" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>
        </div>

        @if($listing->hasMedia('images'))
            <img src="{{ $listing->getFirstMediaUrl('images', 'card') }}"
                 alt="{{ $listing->title }}"
                 class="w-full aspect-[4/3] object-cover"
                 loading="lazy">
        @else
            <div class="card-image flex flex-col items-center justify-center">
                <svg class="w-8 h-8 text-white/40 relative z-[1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        {{-- Featured badge --}}
        @if($isFeatured ?? false)
            <div class="absolute top-2 end-2 z-[1]">
                <span class="badge-featured">
                    {{ __('ui.sections.featured_plain') }}
                </span>
            </div>
        @endif

        {{-- Condition badge --}}
        @if($listing->condition === 'new')
            <div class="absolute bottom-2 start-2 z-[1]">
                <span class="badge-new">
                    {{ __('ui.sections.condition_new') }}
                </span>
            </div>
        @elseif($listing->condition === 'used')
            <div class="absolute bottom-2 start-2 z-[1]">
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white"
                      style="background:rgba(0,0,0,0.55);">
                    {{ __('ui.sections.condition_used') }}
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
        <h3 class="text-nilex-ink font-bold text-sm mb-1.5 line-clamp-1 group-hover:text-nilex transition-colors duration-200">
            {{ $listing->title }}
        </h3>

        @if($listing->relationLoaded('user') ? $listing->user : $listing->user()->first())
            @include('frontend.partials.business-badge', ['seller' => $listing->user])
        @endif

        {{-- Location --}}
        @if($listing->location)
            <div class="flex items-center gap-1 text-muted-foreground text-xs mb-2">
                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="line-clamp-1">{{ $listing->location->name }}</span>
            </div>
        @endif

        {{-- Footer --}}
        <div class="flex items-center justify-between pt-3 border-t border-zinc-50">
            <span class="price-tag text-sm">
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
