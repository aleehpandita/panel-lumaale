<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        $isEdit = fn (? \Illuminate\Database\Eloquent\Model $record) => filled($record);

        return $form->schema([
            \Filament\Forms\Components\Section::make('Reserva')
                ->schema([
                    // ✅ Elegir Tour
                    \Filament\Forms\Components\Select::make('tour_id')
                        ->label('Tour')
                        ->relationship('tour', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled($isEdit),

                    // ✅ Elegir horario (opcional)
                    \Filament\Forms\Components\Select::make('tour_departure_id')
                        ->label('Horario')
                        ->relationship('departure', 'departure_time')
                        ->searchable()
                        ->preload()
                        ->disabled($isEdit)
                        ->helperText('Si el tour no maneja horarios, déjalo vacío.'),

                    \Filament\Forms\Components\DatePicker::make('tour_date')
                        ->required()
                        ->disabled($isEdit),

                    \Filament\Forms\Components\TextInput::make('pax_adults')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->disabled($isEdit),

                    \Filament\Forms\Components\TextInput::make('pax_children')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->disabled($isEdit),

                    \Filament\Forms\Components\TextInput::make('pax_infants')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->disabled($isEdit),

                    \Filament\Forms\Components\TextInput::make('total_amount')
                        ->numeric()
                        ->required()
                        ->disabled($isEdit),

                    \Filament\Forms\Components\Select::make('currency')
                        ->options(['USD' => 'USD', 'MXN' => 'MXN'])
                        ->default('USD')
                        ->required()
                        ->disabled($isEdit),
                ])
                ->columns(2),

            \Filament\Forms\Components\Section::make('Cliente')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('customer_name')
                        ->required()
                        ->disabled($isEdit),

                    \Filament\Forms\Components\TextInput::make('customer_email')
                        ->email()
                        ->required()
                        ->disabled($isEdit),

                    \Filament\Forms\Components\TextInput::make('customer_phone')
                        ->required()
                        ->disabled($isEdit),
                ])
                ->columns(3),

            \Filament\Forms\Components\Section::make('Estatus')
                ->schema([
                    \Filament\Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'pending',
                            'confirmed' => 'confirmed',
                            'cancelled' => 'cancelled',
                        ])
                        ->default('pending')
                        ->required(),

                    \Filament\Forms\Components\Select::make('payment_status')
                        ->options([
                            'pending' => 'pending',
                            'paid' => 'paid',
                            'failed' => 'failed',
                            'refunded' => 'refunded',
                        ])
                        ->default('pending')
                        ->required(),

                    \Filament\Forms\Components\Select::make('payment_method')
                        ->label('Método de pago')
                        ->options([
                            'cash' => 'Efectivo',
                            'stripe' => 'Stripe',
                            'paypal' => 'PayPal',
                            'transfer' => 'Transferencia',
                            'terminal' => 'Terminal',
                            'other' => 'Otro',
                        ])
                        ->disabled($isEdit),


                    \Filament\Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }



   public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('tour.title')
                    ->label('Tour')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('tour_date')
                    ->date()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('departure.departure_time')
                    ->label('Hora')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('customer_email')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency ?: 'USD')
                    ->weight('bold')
                    ->sortable(),


                \Filament\Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'MXN' => 'info',     // 🔵 azul
                        'USD' => 'success',  // 🟢 verde
                        default => 'gray',
                    })
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',    // 🟠 naranja
                        'confirmed' => 'success',  // 🟢 verde
                        'cancelled' => 'danger',   // 🔴 rojo
                        default => 'gray',
                    })
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pago')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',       // 🟢 verde
                        'pending' => 'warning',    // 🟠 naranja
                        'failed' => 'danger',      // 🔴 rojo
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->color(fn ($state) => match ($state ?? 'none') {
                        'cash' => 'gray',
                        'stripe' => 'info',
                        'paypal' => 'warning',
                        'transfer' => 'primary',
                        'terminal' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash' => 'Efectivo',
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'transfer' => 'Transferencia',
                        'terminal' => 'Terminal',
                        'other' => 'Otro',
                        default => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'pending',
                        'confirmed' => 'confirmed',
                        'cancelled' => 'cancelled',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'pending',
                        'paid' => 'paid',
                        'failed' => 'failed',
                        'refunded' => 'refunded',
                    ]),
            ])
             ->headerActions([
                \Filament\Tables\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $filename = 'bookings_' . now()->format('Ymd_His') . '.csv';

                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename={$filename}",
                        ];

                        $callback = function () {
                            $out = fopen('php://output', 'w');

                            // Encabezados
                            fputcsv($out, [
                                'ID','Tour','Fecha','Hora','Cliente','Email','Tel',
                                'Adultos','Niños','Infantes','Total','Moneda',
                                'Status','Pago','Creado'
                            ]);

                            // OJO: esto exporta TODO (puedes luego aplicarle filtros si quieres)
                            \App\Models\Booking::with(['tour','departure'])
                                ->orderByDesc('id')
                                ->chunk(500, function ($rows) use ($out) {
                                    foreach ($rows as $b) {
                                        fputcsv($out, [
                                            $b->id,
                                            $b->tour?->title,
                                            optional($b->tour_date)->toDateString(),
                                            $b->departure?->departure_time,
                                            $b->customer_name,
                                            $b->customer_email,
                                            $b->customer_phone,
                                            $b->pax_adults,
                                            $b->pax_children,
                                            $b->pax_infants,
                                            $b->total_amount,
                                            $b->currency,
                                            $b->status,
                                            $b->payment_status,
                                            optional($b->created_at)->toDateTimeString(),
                                        ]);
                                    }
                                });

                            fclose($out);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\EditAction::make(),

                // ✅ Confirmar
                \Filament\Tables\Actions\Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'confirmed',
                        ]);
                    }),

              \Filament\Tables\Actions\Action::make('markPaid')
                    ->label('Marcar pagado')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')

                    // 👇 AQUÍ VA EL visible 👇
                    ->visible(fn ($record) =>
                        $record->payment_status !== 'paid' &&
                        $record->status !== 'cancelled'
                    )

                    ->form([
                        \Filament\Forms\Components\Select::make('payment_method')
                            ->label('Método de pago')
                            ->options([
                                'cash' => 'Efectivo',
                                'stripe' => 'Stripe',
                                'paypal' => 'PayPal',
                                'transfer' => 'Transferencia',
                                'terminal' => 'Terminal',
                                'other' => 'Otro',
                            ])
                            ->required(),

                        \Filament\Forms\Components\Textarea::make('payment_note')
                            ->label('Nota interna')
                            ->rows(3),
                    ])

                    ->action(function ($record, array $data) {
                        $updates = [
                            'payment_status' => 'paid',
                            'payment_method' => $data['payment_method'],
                        ];

                        if ($record->status === 'pending') {
                            $updates['status'] = 'confirmed';
                        }

                        if (!empty($data['payment_note'])) {
                            $updates['notes'] = trim(($record->notes ?? '') . "\nPago: " . $data['payment_note']);
                        }

                        $record->update($updates);
                    }),

            ])
            ->bulkActions([  
               \Filament\Tables\Actions\BulkAction::make('bulkMarkPaid')
                ->label('Marcar pagados')
                ->icon('heroicon-o-banknotes')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Select::make('payment_method')
                        ->label('Método de pago')
                        ->options([
                            'cash' => 'Efectivo',
                            'stripe' => 'Stripe',
                            'paypal' => 'PayPal',
                            'transfer' => 'Transferencia',
                            'terminal' => 'Terminal',
                            'other' => 'Otro',
                        ])
                        ->required(),
                ])
                ->action(function (\Illuminate\Support\Collection $records, array $data) {
                    foreach ($records as $record) {
                        $updates = [
                            'payment_status' => 'paid',
                            'payment_method' => $data['payment_method'],
                        ];

                        if ($record->status === 'pending') {
                            $updates['status'] = 'confirmed';
                        }

                        $record->update($updates);
                    }
                }),
                
            ])
            ->defaultSort('id', 'desc');
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
