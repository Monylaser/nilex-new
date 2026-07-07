{{-- Shared listing sort dropdown — used on category + search pages --}}
@php $currentSort = $sort ?? \App\Support\ListingSort::DEFAULT; @endphp
<label for="listing-sort" class="sr-only">{{ __('ui.sort.label') }}</label>
<select id="listing-sort"
        name="sort"
        class="bg-white border border-zinc-200 rounded-xl py-2.5 px-3.5 text-sm font-semibold text-zinc-700 focus:outline-none focus:border-zinc-400 cursor-pointer"
        onchange="this.form.submit()">
    <option value="latest" @selected($currentSort === 'latest')>{{ __('ui.sort.latest') }}</option>
    <option value="oldest" @selected($currentSort === 'oldest')>{{ __('ui.sort.oldest') }}</option>
    <option value="price_asc" @selected($currentSort === 'price_asc')>{{ __('ui.sort.price_asc') }}</option>
    <option value="price_desc" @selected($currentSort === 'price_desc')>{{ __('ui.sort.price_desc') }}</option>
</select>
