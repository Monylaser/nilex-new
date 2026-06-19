<?php

namespace App\Filament\Admin\Resources\AdCampaigns;

use App\Filament\Admin\Resources\AdCampaigns\Pages;
use App\Models\AdCampaign;
use App\Services\AdCampaignService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AdCampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'الحملات الإعلانية';

    protected static ?string $navigationLabel = 'الحملات';

    protected static ?string $modelLabel = 'حملة إعلانية';

    protected static ?string $pluralModelLabel = 'الحملات الإعلانية';

    protected static ?int $navigationSort = 1;

    public static function placementOptions(): array
    {
        $options = [
            'hero_top'        => 'بانر رئيسي أعلى الصفحة',
            'home_feed'       => 'داخل قائمة الإعلانات',
            'category_page'   => 'صفحة القسم',
            'listing_detail'  => 'صفحة تفاصيل الإعلان',
            'search_results'  => 'نتائج البحث',
        ];

        if (AdCampaignService::selfServiceEnabled()) {
            $options['login_page'] = 'صفحة تسجيل الدخول';
            $options['popup']      = 'نافذة منبثقة';
        }

        return $options;
    }

    public static function selfServiceCampaign(AdCampaign $record): bool
    {
        return $record->seller_id !== null || $record->payment_status !== null;
    }

    public static function statusOptions(): array
    {
        return [
            'draft'     => 'مسودة',
            'scheduled' => 'مجدولة',
            'active'    => 'نشطة',
            'paused'    => 'موقوفة مؤقتاً',
            'expired'   => 'منتهية',
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            'pending'   => 'قيد الدفع',
            'paid'      => 'مدفوع',
            'failed'    => 'فشل',
            'refunded'  => 'مسترد',
        ];
    }

    public static function approvalStatusOptions(): array
    {
        return [
            'pending'  => 'قيد الموافقة',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('seller');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('معلومات الحملة')
                ->schema([
                    TextInput::make('title')
                        ->label('عنوان الحملة')
                        ->required()
                        ->maxLength(255),

                    Select::make('placement')
                        ->label('موضع الإعلان')
                        ->options(static::placementOptions())
                        ->required()
                        ->live(),

                    Select::make('category_id')
                        ->label('القسم')
                        ->relationship('category', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => $get('placement') === 'category_page')
                        ->required(fn (Get $get): bool => $get('placement') === 'category_page'),

                    TextInput::make('target_url')
                        ->label('رابط الوجهة')
                        ->url()
                        ->nullable()
                        ->helperText('اتركه فارغاً للإعلانات التوعوية بدون رابط'),
                ]),

            Section::make('الجدولة والحالة')
                ->schema([
                    Select::make('status')
                        ->label('الحالة')
                        ->options(static::statusOptions())
                        ->default('draft'),

                    DateTimePicker::make('starts_at')
                        ->label('تاريخ البداية')
                        ->nullable()
                        ->seconds(false),

                    DateTimePicker::make('ends_at')
                        ->label('تاريخ الانتهاء')
                        ->nullable()
                        ->seconds(false)
                        ->minDate(fn (Get $get) => $get('starts_at')),
                ]),

            Section::make('الصورة والأولوية')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('ad_image')
                        ->label('صورة الإعلان')
                        ->collection('ad_image')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->maxSize(2048)
                        ->required(),

                    TextInput::make('priority')
                        ->label('الأولوية')
                        ->numeric()
                        ->default(0)
                        ->helperText('رقم أعلى = ظهور أولاً. نفس الأولوية = تناوب عشوائي عادل'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('ad_image')
                    ->label('الصورة')
                    ->collection('ad_image'),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('placement')
                    ->label('الموضع')
                    ->formatStateUsing(fn (string $state): string => static::placementOptions()[$state] ?? $state)
                    ->colors([
                        'hero_top'       => 'success',
                        'home_feed'      => 'info',
                        'category_page'  => 'warning',
                        'login_page'     => 'gray',
                        'popup'          => 'danger',
                        'listing_detail' => 'primary',
                        'search_results' => 'secondary',
                    ]),

                TextColumn::make('seller.name')
                    ->label('البائع')
                    ->searchable()
                    ->placeholder('—')
                    ->icon('heroicon-m-user')
                    ->visible(fn (): bool => AdCampaignService::selfServiceEnabled()),

                BadgeColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? (static::paymentStatusOptions()[$state] ?? $state)
                        : '—')
                    ->colors([
                        'paid'     => 'success',
                        'pending'  => 'warning',
                        'failed'   => 'danger',
                        'refunded' => 'gray',
                    ])
                    ->visible(fn (): bool => AdCampaignService::selfServiceEnabled()),

                BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->formatStateUsing(fn (string $state): string => static::approvalStatusOptions()[$state] ?? $state)
                    ->colors([
                        'approved' => 'success',
                        'pending'  => 'warning',
                        'rejected' => 'danger',
                    ])
                    ->visible(fn (): bool => AdCampaignService::selfServiceEnabled()),

                TextColumn::make('amount_paid')
                    ->label('المبلغ المدفوع')
                    ->money('EGP')
                    ->placeholder('—')
                    ->sortable()
                    ->visible(fn (): bool => AdCampaignService::selfServiceEnabled()),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => static::statusOptions()[$state] ?? $state)
                    ->colors([
                        'draft'     => 'gray',
                        'scheduled' => 'blue',
                        'active'    => 'green',
                        'paused'    => 'yellow',
                        'expired'   => 'red',
                    ]),

                TextColumn::make('priority')
                    ->label('الأولوية')
                    ->sortable(),

                TextColumn::make('views_count')
                    ->label('المشاهدات')
                    ->sortable(),

                TextColumn::make('clicks_count')
                    ->label('النقرات')
                    ->sortable(),

                TextColumn::make('ctr')
                    ->label('CTR%')
                    ->formatStateUsing(fn (AdCampaign $record): string => $record->ctr . '%'),

                TextColumn::make('starts_at')
                    ->label('البداية')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('الانتهاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(static::statusOptions()),

                SelectFilter::make('placement')
                    ->label('الموضع')
                    ->options(static::placementOptions()),

                Filter::make('starts_at_range')
                    ->label('تاريخ البداية')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('من'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('starts_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('starts_at', '<=', $date)
                            );
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('approve')
                    ->visible(fn (AdCampaign $record): bool => AdCampaignService::selfServiceEnabled()
                        && static::selfServiceCampaign($record)
                        && $record->payment_status === 'paid'
                        && $record->approval_status !== 'approved')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الموافقة على الحملة')
                    ->modalDescription(fn (AdCampaign $record): string => "هل تريد الموافقة على حملة \"{$record->title}\"؟")
                    ->action(function (AdCampaign $record): void {
                        app(AdCampaignService::class)->approveCampaign($record, (int) Auth::id());

                        \Filament\Notifications\Notification::make()
                            ->title('تمت الموافقة على الحملة')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('reject')
                    ->visible(fn (AdCampaign $record): bool => AdCampaignService::selfServiceEnabled()
                        && static::selfServiceCampaign($record)
                        && $record->approval_status !== 'rejected')
                    ->modalHeading(fn (AdCampaign $record): string => 'رفض حملة: ' . $record->title)
                    ->form([
                        Textarea::make('rejected_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, AdCampaign $record): void {
                        app(AdCampaignService::class)->rejectCampaign(
                            $record,
                            (int) Auth::id(),
                            $data['rejected_reason'],
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('تم رفض الحملة')
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdCampaigns::route('/'),
            'create' => Pages\CreateAdCampaign::route('/create'),
            'edit'   => Pages\EditAdCampaign::route('/{record}/edit'),
        ];
    }
}
