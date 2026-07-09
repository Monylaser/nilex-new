{{-- Feature comparison matrix (additive UI — config/pricing.php) --}}
@php
    $columnKeys = $planColumnKeys ?? config('pricing.plan_column_keys', []);
    $matrixRows = $featureMatrix ?? config('pricing.feature_matrix', []);

    $resolvePlanKey = static function ($plan): ?string {
        $en = strtolower(trim($plan->name_en ?? ''));
        $ar = trim($plan->name_ar ?? '');

        if (str_contains($en, 'starter') || str_contains($ar, 'مبتد')) {
            return 'starter';
        }
        if (str_contains($en, 'growth') || str_contains($ar, 'نمو')) {
            return 'growth';
        }
        if (str_contains($en, 'pro') || str_contains($ar, 'محترف')) {
            return 'pro_seller';
        }
        if (str_contains($en, 'business') || str_contains($ar, 'أعمال') || str_contains($ar, 'شرك')) {
            return 'business';
        }

        return null;
    };

    $orderedPlans = collect($plans)->sortBy('price')->values();
    $columns = collect($columnKeys)->map(function (string $key) use ($orderedPlans, $resolvePlanKey) {
        return $orderedPlans->first(fn ($plan) => $resolvePlanKey($plan) === $key);
    });
@endphp

<section id="feature-matrix" class="scroll-mt-24">
    <div class="text-center mb-5 sm:mb-6">
        <h2 class="text-lg sm:text-xl font-bold text-zinc-900 tracking-tight">
            {{ __('ui.pricing.matrix_title') }}
        </h2>
        <p class="text-zinc-500 text-xs sm:text-sm mt-2 max-w-2xl mx-auto leading-relaxed">
            {{ __('ui.pricing.matrix_subtitle') }}
        </p>
    </div>

    <div class="bg-white rounded-3xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="bg-zinc-50 border-b border-zinc-200">
                        <th scope="col" class="text-start px-4 sm:px-5 py-3.5 text-xs font-bold text-zinc-500 uppercase tracking-wide w-[38%]">
                            {{ __('ui.pricing.matrix_feature') }}
                        </th>
                        @foreach ($columns as $index => $plan)
                            @php
                                $colKey = $columnKeys[$index] ?? null;
                                $isGrowthCol = $colKey === 'growth';
                            @endphp
                            <th scope="col" class="text-center px-3 py-3.5 min-w-[88px] {{ $isGrowthCol ? 'bg-nilex-teal/5' : '' }}">
                                @if ($plan)
                                    <span class="block text-xs font-bold text-zinc-900 leading-snug">{{ $plan->name_ar }}</span>
                                    @if ($isGrowthCol)
                                        <span class="inline-block mt-1 text-[10px] font-semibold text-nilex-teal bg-white border border-nilex-teal/30 px-2 py-0.5 rounded-full leading-none">
                                            {{ __('ui.pricing.most_popular') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-zinc-300">—</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($matrixRows as $row)
                        @php
                            $rowStatus = $row['status'] ?? null;
                        @endphp
                        <tr class="hover:bg-zinc-50/60 transition-colors">
                            <td class="px-4 sm:px-5 py-3 text-xs sm:text-sm text-zinc-700 font-medium leading-snug">
                                {{ __('ui.pricing.features.' . $row['key']) }}
                            </td>
                            @if ($rowStatus === 'coming_soon')
                                <td colspan="{{ count($columnKeys) }}" class="text-center px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-1 rounded-full" title="{{ __('ui.pricing.status_coming_soon') }}" aria-label="{{ __('ui.pricing.status_coming_soon') }}">
                                        <span aria-hidden="true">🚧</span>
                                        {{ __('ui.pricing.status_coming_soon') }}
                                    </span>
                                </td>
                            @elseif ($rowStatus === 'admin_only')
                                <td colspan="{{ count($columnKeys) }}" class="text-center px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-zinc-600 bg-zinc-100 border border-zinc-200 px-2.5 py-1 rounded-full" title="{{ __('ui.pricing.status_admin_only') }}" aria-label="{{ __('ui.pricing.status_admin_only') }}">
                                        <span aria-hidden="true">🔒</span>
                                        {{ __('ui.pricing.status_admin_only') }}
                                    </span>
                                </td>
                            @else
                                @foreach ($columnKeys as $colKey)
                                    @php $included = (bool) ($row[$colKey] ?? false); @endphp
                                    <td class="text-center px-3 py-3 {{ $colKey === 'growth' ? 'bg-nilex-teal/[0.03]' : '' }}">
                                        @if ($included)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-nilex-teal" title="{{ __('ui.pricing.status_available') }}" aria-label="{{ __('ui.pricing.status_available') }}">
                                                <span aria-hidden="true">✅</span>
                                                <span class="sr-only sm:not-sr-only">{{ __('ui.pricing.status_available') }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-zinc-100 text-zinc-300" title="{{ __('ui.pricing.not_included') }}" aria-label="{{ __('ui.pricing.not_included') }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="px-4 sm:px-5 py-3.5 text-xs sm:text-sm text-zinc-500 bg-zinc-50 border-t border-zinc-200 leading-relaxed">
            {{ __('ui.pricing.matrix_compliance_note') }}
        </p>
    </div>
</section>
