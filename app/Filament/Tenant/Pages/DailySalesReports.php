<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Order;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\DatePicker;

class DailySalesReports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.tenant.pages.daily-sales-reports';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Daily Sales Report';

    public static function canAccess(): bool
    {
        // Check if the authenticated user has the permission
        return auth()->user()->can('generate_tenant_reports');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->select([
                        DB::raw('DATE(completed_at) as report_date'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('AVG(total) as average_order_value')
                    ])
                    ->where('status', 'completed') // Only include completed orders
                    ->whereNotNull('completed_at')   // Ensure we have a date to group by
                    ->groupBy('report_date')
                    ->orderBy('report_date', 'desc') // Show most recent first
            )
            ->columns([
                TextColumn::make('report_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_orders')
                    ->label('Total Orders')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->money(tenant()?->currency ?? 'EUR') // Use tenant currency or default
                    ->sortable(),
                TextColumn::make('average_order_value')
                    ->label('Avg. Order Value')
                    ->money(tenant()?->currency ?? 'EUR') // Use tenant currency or default
                    ->sortable(),
            ])
            ->filters([
                Filter::make('completed_at')
                    ->form([
                        DatePicker::make('completed_from')
                            ->label('From Date'),
                        DatePicker::make('completed_until')
                            ->label('Until Date')
                            ->default(now()), // Default to today
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['completed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '>=', $date),
                            )
                            ->when(
                                $data['completed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['completed_from'] && ! $data['completed_until']) {
                            return null;
                        }

                        $parts = [];
                        if ($data['completed_from']) {
                            $parts[] = 'From: ' . Carbon::parse($data['completed_from'])->format('M j, Y');
                        }
                        if ($data['completed_until']) {
                            $parts[] = 'Until: ' . Carbon::parse($data['completed_until'])->format('M j, Y');
                        }
                        return implode(' ', $parts);
                    }),
            ])
            ->defaultSort('report_date', 'desc');
    }
} 