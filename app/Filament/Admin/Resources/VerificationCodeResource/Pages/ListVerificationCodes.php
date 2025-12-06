<?php

namespace App\Filament\Admin\Resources\VerificationCodeResource\Pages;

use App\Filament\Admin\Resources\VerificationCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVerificationCodes extends ListRecords
{
    protected static string $resource = VerificationCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cleanup')
                ->label('清理过期验证码')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    \App\Models\VerificationCode::where('expires_at', '<', now())
                        ->orWhere('is_used', true)
                        ->delete();
                }),
        ];
    }
}

