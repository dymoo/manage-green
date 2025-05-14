<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\ClubSetting;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;

class ClubSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Club Settings';
    
    protected static ?string $title = 'Club Settings';
    
    protected static ?string $slug = 'club-configuration';
    
    protected static ?int $navigationSort = 15;
    
    protected ?string $heading = 'Club Settings';
    
    protected static string $view = 'filament.pages.tenancy.club-settings';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $tenant = Filament::getTenant();
        
        // Load existing settings for the current tenant
        $this->loadSettingsForm();
    }
    
    protected function loadSettingsForm(): void
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            return;
        }
        
        // Load existing settings for each group
        $brandingSettings = $tenant->getSettingsByGroup('branding')->toArray();
        $pricingSettings = $tenant->getSettingsByGroup('pricing')->toArray();
        $inventorySettings = $tenant->getSettingsByGroup('inventory')->toArray();
        
        // Prepare default data structure
        $this->form->fill([
            'branding' => $brandingSettings,
            'pricing' => $pricingSettings,
            'inventory' => $inventorySettings,
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('Branding')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Section::make('Branding Settings')
                                    ->description('Customize the look and feel of your club')
                                    ->schema([
                                        KeyValue::make('branding.custom_colors')
                                            ->label('Custom Color Scheme')
                                            ->keyLabel('Element')
                                            ->valueLabel('Color')
                                            ->addable()
                                            ->reorderable()
                                            ->default([
                                                'button' => '#059669',
                                                'header' => '#059669',
                                                'footer' => '#059669',
                                            ]),
                                            
                                        Toggle::make('branding.use_custom_css')
                                            ->label('Use Custom CSS')
                                            ->default(false),
                                            
                                        Textarea::make('branding.custom_css')
                                            ->label('Custom CSS')
                                            ->hidden(fn (\Filament\Forms\Get $get) => !$get('branding.use_custom_css'))
                                            ->columnSpanFull(),
                                            
                                        Toggle::make('branding.show_logo_on_receipts')
                                            ->label('Show Logo on Receipts')
                                            ->default(true),
                                            
                                        Textarea::make('branding.footer_text')
                                            ->label('Receipt Footer Text')
                                            ->placeholder('Thank you for your purchase!'),
                                    ]),
                            ]),
                            
                        Tab::make('Pricing Rules')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Section::make('Pricing Rules')
                                    ->description('Configure pricing rules for your club')
                                    ->schema([
                                        Toggle::make('pricing.enable_member_discounts')
                                            ->label('Enable Member Discounts')
                                            ->default(false),
                                            
                                        KeyValue::make('pricing.discount_tiers')
                                            ->label('Discount Tiers')
                                            ->keyLabel('Membership Level')
                                            ->valueLabel('Discount %')
                                            ->addable()
                                            ->reorderable()
                                            ->hidden(fn (\Filament\Forms\Get $get) => !$get('pricing.enable_member_discounts'))
                                            ->default([
                                                'standard' => '0',
                                                'silver' => '5',
                                                'gold' => '10',
                                            ]),
                                            
                                        Toggle::make('pricing.enable_bulk_discounts')
                                            ->label('Enable Bulk Purchase Discounts')
                                            ->default(false),
                                            
                                        KeyValue::make('pricing.bulk_discount_tiers')
                                            ->label('Bulk Discount Tiers (g)')
                                            ->keyLabel('Minimum Quantity')
                                            ->valueLabel('Discount %')
                                            ->addable()
                                            ->reorderable()
                                            ->hidden(fn (\Filament\Forms\Get $get) => !$get('pricing.enable_bulk_discounts'))
                                            ->default([
                                                '5' => '5',
                                                '10' => '10',
                                                '25' => '15',
                                            ]),
                                            
                                        Toggle::make('pricing.round_to_nearest')
                                            ->label('Round Prices to Nearest')
                                            ->default(false),
                                            
                                        Select::make('pricing.rounding_rule')
                                            ->label('Rounding Rule')
                                            ->options([
                                                '0.5' => '50 Cents',
                                                '1' => 'Whole Euro',
                                                '0.1' => '10 Cents',
                                            ])
                                            ->default('0.5')
                                            ->hidden(fn (\Filament\Forms\Get $get) => !$get('pricing.round_to_nearest')),
                                    ]),
                            ]),
                            
                        Tab::make('Inventory Rules')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Section::make('Inventory Rules')
                                    ->description('Configure inventory management rules')
                                    ->schema([
                                        Toggle::make('inventory.enable_low_stock_alerts')
                                            ->label('Enable Low Stock Alerts')
                                            ->default(true),
                                            
                                        TextInput::make('inventory.global_low_stock_threshold')
                                            ->label('Global Low Stock Threshold (g)')
                                            ->numeric()
                                            ->default(10)
                                            ->suffix('g')
                                            ->hidden(fn (\Filament\Forms\Get $get) => !$get('inventory.enable_low_stock_alerts')),
                                            
                                        Toggle::make('inventory.restrict_sales_on_low_stock')
                                            ->label('Restrict Sales on Low Stock')
                                            ->default(false),
                                            
                                        Toggle::make('inventory.enable_stock_forecasting')
                                            ->label('Enable Stock Forecasting')
                                            ->default(false),
                                            
                                        TextInput::make('inventory.forecast_days')
                                            ->label('Forecast Days')
                                            ->numeric()
                                            ->default(30)
                                            ->hidden(fn (\Filament\Forms\Get $get) => !$get('inventory.enable_stock_forecasting')),
                                            
                                        KeyValue::make('inventory.product_minimum_order')
                                            ->label('Product Minimum Orders')
                                            ->keyLabel('Product SKU')
                                            ->valueLabel('Minimum Order (g)')
                                            ->addable()
                                            ->reorderable(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
    
    public function save(): void
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            Notification::make()
                ->title('Error')
                ->body('No tenant found')
                ->danger()
                ->send();
                
            return;
        }
        
        try {
            $data = $this->form->getState();
            
            // Loop through each setting group and save them
            foreach ($data as $group => $settings) {
                if (empty($settings)) {
                    continue;
                }
                
                // Save each setting in the group
                foreach ($settings as $key => $value) {
                    ClubSetting::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'setting_key' => $key,
                            'setting_group' => $group,
                        ],
                        [
                            'setting_value' => $value,
                            'is_active' => true,
                        ]
                    );
                }
            }
            
            Notification::make()
                ->title('Settings Saved')
                ->body('Your club settings have been updated successfully.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Error saving club settings: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error')
                ->body('There was an error saving your settings. Please try again.')
                ->danger()
                ->send();
                
            throw new Halt();
        }
    }
} 