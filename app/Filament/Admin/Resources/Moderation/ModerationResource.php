<?php

namespace App\Filament\Admin\Resources\Moderation;

use App\Models\AuditLog;
use App\Models\Listing;
use App\Filament\Admin\Resources\Moderation\Pages;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ModerationResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-shield-check';
    protected static string|UnitEnum|null   $navigationGroup = 'الإشراف';
    protected static ?string $navigationLabel = 'طابور المراجعة';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $slug            = 'moderation';

    public static function getNavigationBadge(): ?string
    {
        $count = Listing::pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Listing::query()->with(['user', 'category', 'moderator']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('title')
                    ->label('عنوان الإعلان')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('user.name')
                    ->label('المعلن')
                    ->searchable()
                    ->icon('heroicon-m-user'),

                TextColumn::make('category.name_ar')
                    ->label('القسم')
                    ->badge()
                    ->color('info'),

                TextColumn::make('price')
                    ->label('السعر')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'published' => 'success',
                        'rejected'  => 'danger',
                        'flagged'   => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'قيد المراجعة',
                        'published' => 'منشور',
                        'rejected'  => 'مرفوض',
                        'flagged'   => 'مُبلَّغ عنه',
                        default     => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Listing::statusOptions())
                    ->default('pending'),

                SelectFilter::make('category_id')
                    ->label('القسم')
                    ->relationship('category', 'name_ar'),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('اتخاذ قرار')
                    ->icon('heroicon-m-shield-check')
                    ->color('primary')
                    ->modalHeading(fn ($record) => 'مراجعة: ' . $record->title)
                    ->modalWidth('3xl')
                    ->modalContent(
                        fn ($record) => view('filament.moderation.review-modal', ['listing' => $record])
                    )
                    ->form([
                        Select::make('action')
                            ->label('القرار النهائي')
                            ->options([
                                'approve' => '✅ موافقة — نشر الإعلان فوراً',
                                'reject'  => '❌ رفض — مع تسجيل مخالفة إن وجد',
                                'flag'    => '🚩 تحديد — مراجعة أمنية مكثفة',
                            ])
                            ->required()
                            ->live(),

                        // قائمة أسباب الرفض المسحوبة من الموديل
                        Select::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->options(Listing::rejectionReasonOptions())
                            ->visible(fn ($get) => $get('action') === 'reject')
                            ->required(fn ($get) => $get('action') === 'reject')
                            ->searchable(),

                        Textarea::make('admin_notes')
                            ->label('ملاحظات إضافية تظهر للمستخدم')
                            ->placeholder('اكتب تفاصيل تساعد المعلن على فهم المشكلة...')
                            ->visible(fn ($get) => $get('action') === 'reject')
                            ->rows(3),

                        Select::make('flag_reason')
                            ->label('سبب التحديد الأمني')
                            ->options([
                                'ethical'  => 'مخالفة أخلاقية',
                                'security' => 'خطر أمني',
                                'fraud'    => 'احتيال ونصب',
                                'spam'     => 'إعلانات مزعجة',
                                'other'    => 'أسباب أخرى',
                            ])
                            ->visible(fn ($get) => $get('action') === 'flag')
                            ->required(fn ($get) => $get('action') === 'flag'),
                    ])
                    ->action(function (array $data, Listing $record): void {
                        $adminId = Auth::id();
                        
                        match ($data['action']) {
                            'approve' => static::handleApprove($record, $adminId),
                            'reject'  => static::handleReject($record, $adminId, $data['rejection_reason'], $data['admin_notes'] ?? null),
                            'flag'    => static::handleFlag($record, $adminId, $data['flag_reason'] ?? 'other'),
                        };
                    }),
            ]);
    }

    protected static function handleApprove(Listing $listing, int $adminId): void
    {
        $listing->approve($adminId);
        
        // تسجيل في سجل العمليات
        if (class_exists(AuditLog::class)) {
            AuditLog::record('approve_ad', $listing, ['title' => $listing->title]);
        }

        Notification::make()->title('تم نشر الإعلان بنجاح ✅')->success()->send();
    }

    protected static function handleReject(Listing $listing, int $adminId, string $reason, ?string $notes = null): void
    {
        // 1. تنفيذ الرفض في الموديل
        $listing->reject($adminId, $reason);

        // 2. التحقق من المخالفات (Strikes) تلقائياً
        if ($listing->rejectionCausesStrike($reason)) {
            $listing->user->addStrike();
        }

        // 3. التسجيل في السجل
        if (class_exists(AuditLog::class)) {
            AuditLog::record('reject_ad', $listing, [
                'title' => $listing->title, 
                'reason' => $reason, 
                'notes' => $notes
            ]);
        }

        Notification::make()->title('تم رفض الإعلان وإبلاغ المستخدم')->danger()->send();
    }

    protected static function handleFlag(Listing $listing, int $adminId, string $reason): void
    {
        $listing->flag($adminId, $reason);
        
        if (class_exists(AuditLog::class)) {
            AuditLog::record('flag_ad', $listing, ['title' => $listing->title, 'flag_reason' => $reason]);
        }

        Notification::make()->title('تم تحديد الإعلان للمراجعة الأمنية 🚩')->warning()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModeration::route('/'),
        ];
    }
}