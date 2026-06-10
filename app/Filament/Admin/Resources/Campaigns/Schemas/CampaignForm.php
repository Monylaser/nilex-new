<?php

namespace App\Filament\Admin\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('title')
                ->label('عنوان الحملة')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('message')
                ->label('نص الرسالة')
                ->required()
                ->rows(4)
                ->columnSpanFull(),

            Select::make('target_group')
                ->label('الفئة المستهدفة')
                ->options(Campaign::targetGroupOptions())
                ->default(Campaign::TARGET_ALL)
                ->required(),

            Select::make('status')
                ->label('الحالة')
                ->options(Campaign::statusOptions())
                ->default(Campaign::STATUS_DRAFT)
                ->required(),

            DateTimePicker::make('scheduled_at')
                ->label('موعد الإرسال')
                ->nullable()
                ->helperText('اتركه فارغاً لإرسال فوري، أو حدد وقتاً مستقبلياً.')
                ->seconds(false),
        ]);
    }
}
