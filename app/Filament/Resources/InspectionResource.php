<?php /** @noinspection SpellCheckingInspection */

namespace App\Filament\Resources;

use App\Filament\Resources\InspectionResource\Pages;
use App\Jobs\CreateExcel;
use App\Models\Inspection;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Markdown;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Tabs::make()
                    ->tabs([
                        static::reportSelectStep(),
                        static::reportBasicStep(),
                        static::reportPhysicalStep(),
                        static::reportPhotoStep(),
                        static::reportRentStep(),
                        static::reportMgmtInterviewStep(),
                        static::reportMultifamilyStep(),
                        static::reportFannieMaeStep(),
                        static::reportFREStep(),
                        static::reportRepairStep(),
                        static::reportSeniorStep(),
                        static::reportHospitalStep(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Propety Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('overall_rating')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_scale')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Inspector Name')
                    ->visible(auth()->user()->is_admin)
                    ->searchable(),
                Tables\Columns\TextColumn::make('inspection_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inspection_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? "Complete" : "Pending")
                    ->color(fn($state) => $state ? "success" : "warning"),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconButton()
                    ->action(function ($record, Component $livewire) {
                        Notification::make()
                            ->title('Generating File')
                            ->body('You will be notified once its done.')
                            ->info()
                            ->send();
                        $livewire->dispatch('gen-excel', id: $record->id);
                    })

                ,
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListInspections::route('/'),
            'create' => Pages\CreateInspection::route('/create'),
            'edit' => Pages\EditInspection::route('/{record}/edit'),
        ];
    }

    public static function reportSelectStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Choose Report Tabs')
            ->columns(3)
            ->schema([
                Forms\Components\Placeholder::make('Usage Instructions')->columnSpan(2)->hidden(fn($operation) => $operation == 'view')
                    ->content(Markdown::inline(text: "Select Report type from below. Tabs will appear in above header accordingly. You can also import old data by clicking **Get Old Data** Button")),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('fill_old_data')->label('Get Old Data')
                        ->form([
                            Select::make('inspection_old_id')->label('Select Old Inspection')
                                ->options(fn() => Inspection::all()->pluck('name', 'id'))->columns(3)
                        ])
                        ->action(function ($livewire, $data) {
                            $data = Inspection::find($data['inspection_old_id']);
                            $livewire->form->fill($data->toArray());
                        })->hidden(fn($operation) => $operation == 'view'),
                ])->verticallyAlignCenter()->alignCenter(),
                Forms\Components\Radio::make('inspection_type')->label('Inspection Type')->inline()
                    ->inlineLabel(false)
                    ->columnSpanFull()
                    ->options(['Basic Inspection', 'Fannie Mae Inspection', 'Repairs Verification', 'Freddie Mac Inspection'])->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state == 0) {
                            $set('form_steps', [1, 2, 3, 4]);
                        } elseif ($state == 1) {
                            $set('form_steps', [1, 2, 3, 4, 5, 6, 7, 9]);
                        } elseif ($state == 2) {
                            $set('form_steps', [3, 9]);
                        } elseif ($state == 3) {
                            $set('form_steps', [1, 2, 3, 4, 5, 6, 8, 9]);
                        }
                    }),
                Forms\Components\CheckboxList::make('form_steps')->default([1])->gridDirection('row')->label('Select Report Tabs')
                    ->options([
                        1 => 'General Info',
                        2 => 'Physical Condition & DM',
                        3 => 'Photos',
                        4 => 'Rent Roll',
                        5 => 'Mgmt Interview',
                        6 => 'Multifamily',
                        7 => 'Fannie Mae Assmt Addendum',
                        8 => 'FRE Assmt Addendum',
                        9 => 'Repairs Verification',
                        10 => 'Senior Housing Supplement',
                        11 => 'Hospitals',
                    ])->columnSpanFull()->columns(4)->live(),
                Select::make('inspection_status')->required()
                    ->options(['Pending', 'Complete'])->default(0)

            ]);
    }

    public static function reportBasicStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Basic Info')
            ->columns(3)
            ->visible(fn($get) => in_array('1', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('1', $get('form_steps')))
            ->schema([
                Forms\Components\Hidden::make('temp_key')->default(0),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address_2')
                    ->maxLength(255),
                TextInput::make('city')
                    ->required()
                    ->maxLength(255),
                TextInput::make('state')
                    ->required()
                    ->label('State'),
                TextInput::make('zip')
                    ->required()
                    ->maxLength(255),
//                TextInput::make('country')
//                    ->required()
//                    ->maxLength(255),
                TextInput::make('overall_rating')
                    ->required()
                    ->label('Overall Rating')
                    ->numeric()
                    ->minValue(0),
                Select::make('rating_scale')
                    ->required()
                    ->label('Rating Scale')
                    ->options(['MBA' => 'MBA', 'Fannie Mae' => 'Fannie Mae'])->default('MBA'),
                Forms\Components\DateTimePicker::make('inspection_date')
                    ->required()
                    ->label('Inspection Date')->default(today()),
                Select::make('primary_type')
                    ->required()
                    ->live()
                    ->label('Primary Type')
                    ->options(['Health Care' => 'Health Care', 'Industrial' => 'Industrial', 'Lodging' => 'Lodging', 'Multifamily' => 'Multifamily', 'Mobile Home Park' => 'Mobile Home Park', 'Mixed Use' => 'Mixed Use', 'Office' => 'Office', 'Other' => 'Other', 'Retail' => 'Retail', 'Self Storage' => 'Self Storage']),
                Select::make('secondary_type')
                    ->required()
                    ->label('Secondary Type')
                    ->options(function ($get) {
                        $types = ['Health Care' => ['Assisted Living/Congregate Care' => 'Assisted Living/Congregate Care', 'Hospital' => 'Hospital', 'Nursing Home, Unskilled' => 'Nursing Home, Unskilled', 'Nursing Home, Skilled' => 'Nursing Home, Skilled', 'Speciality Health Care' => 'Speciality Health Care'],
                            'Industrial' => ['Flex Industrial/Office' => 'Flex Industrial/Office', 'R&D' => 'R&D', 'Industrial Showroom' => 'Industrial Showroom', 'Light Industrial/Manufacturing' => 'Light Industrial/Manufacturing', 'Heavy Manufacturing Facility' => 'Heavy Manufacturing Facility', 'Warehouse/Distribution' => 'Warehouse/Distribution'],
                            'Lodging' => ['Extended Stay Hotel' => 'Extended Stay Hotel', 'Full Service Hotel' => 'Full Service Hotel', 'Limited Service Hotel' => 'Limited Service Hotel', 'Resort Hotel' => 'Resort Hotel', 'Motel' => 'Motel'],
                            'Multifamily' => ['Cooperative Housing' => 'Cooperative Housing', 'Condominiums/Townhouses' => 'Condominiums/Townhouses', 'Garden Apartment' => 'Garden Apartment', 'Retirement/Independent Living' => 'Retirement/Independent Living', 'Affordable Housing' => 'Affordable Housing', 'Student Housing' => 'Student Housing', 'High Rise Multifamily (&#8805 7 Stories)' => 'High Rise Multifamily (&#8805 7 Stories)', 'Mid-Rise Multifamily (4-6 stories)' => 'Mid-Rise Multifamily (4-6 stories)', 'Small (&#8804 8 Units) Multifamily' => 'Small (&#8804 8 Units) Multifamily', 'Rent Subsidy' => 'Rent Subsidy'],
                            'Mobile Home Park' => ['Mobile Home Park' => 'Mobile Home Park'],
                            'Mixed Use' => ['Mixed Predominately Office' => 'Mixed Predominately Office', 'Mixed Predominately Multifamily' => 'Mixed Predominately Multifamily', 'Mixed Predominately Industrial' => 'Mixed Predominately Industrial', 'Mixed Predominately Retail' => 'Mixed Predominately Retail'],
                            'Office' => ['Office Condo' => 'Office Condo', 'High Rise Office (&#8806 7 stories)' => 'High Rise Office (&#8806 7 stories)', 'Mid-Rise Office (4-6 stories)' => 'Mid-Rise Office (4-6 stories)', 'Low-Rise Office (1-3 stories)' => 'Low-Rise Office (1-3 stories)', 'Office Campus' => 'Office Campus', 'Medical Office' => 'Medical Office', 'Single Tenant Office' => 'Single Tenant Office'],
                            'Other' => [],
                            'Retail' => ['Anchored Retail Center' => 'Anchored Retail Center', 'Neighborhood Center' => 'Neighborhood Center', 'Power Center' => 'Power Center', 'Regional Mall' => 'Regional Mall', 'Restaurant' => 'Restaurant', 'Shadow Anchored Retail' => 'Shadow Anchored Retail', 'Single Tenant Retail' => 'Single Tenant Retail', 'Specialty Shopping Center' => 'Specialty Shopping Center', 'Outlet Mall' => 'Outlet Mall', 'Warehouse Retail' => 'Warehouse Retail', 'Unanchored/Strip Retail' => 'Unanchored/Strip Retail'],
                            'Self Storage' => ['Self Storage' => 'Self Storage']];
                        if ($get('primary_type') == '') {
                            return [];
                        } else {
                            return $types[$get('primary_type')];
                        }
                    }),
                Forms\Components\Fieldset::make('Servicer and Loan Info')
                    ->columns(3)
                    ->statePath('servicer_loan_info')
                    ->schema([
                        TextInput::make('servicer_name')
                            ->label('Servicer Name')
                            ->maxLength(255),
                        TextInput::make('loan_number')
                            ->label('Loan Number')
                            ->maxLength(255),
                        TextInput::make('property_id')
                            ->label('Property ID')
                            ->maxLength(255),
                        TextInput::make('servicer_inspection_id')
                            ->label('Servicer Inspection ID')
                            ->maxLength(255),
                        TextInput::make('original_loan_amount')
                            ->label('Original Loan Amount')
                            ->numeric()
                            ->minValue(0)
                            ->inputMode('decimal'),
                        TextInput::make('loan_balance')
                            ->label('Loan Balance')
                            ->numeric()
                            ->minValue(0)
                            ->inputMode('decimal'),
                        Forms\Components\DatePicker::make('loan_balance_date')
                            ->label('Loan Balance Date')
                        ,
                        TextInput::make('loan_owner')
                            ->label('Owner of Loan')
                            ->maxLength(255),
                        TextInput::make('investor_number')
                            ->label('Investor Number')
                            ->maxLength(255),
                        TextInput::make('investor_loan_number')
                            ->label('Investor Loan Number')
                            ->maxLength(255),
                        TextInput::make('asset_manager_name')
                            ->label('Asset Manager Name')
                            ->maxLength(255),
                        TextInput::make('asset_manager_phone')
                            ->label('Asset Manager Phone')
                            ->maxLength(255),
                        TextInput::make('asset_manager_email')
                            ->label('Asset Manager Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('report_reviewed_by')
                            ->label('Report Reviewed By')
                            ->maxLength(255),
                    ]),
                Forms\Components\Fieldset::make('Contact Company and Inspector Info')
                    ->columns(3)
                    ->statePath('contact_inspector_info')
                    ->schema([
                        TextInput::make('contact_company')
                            ->label('Contact Company')
                            ->maxLength(255),
                        TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->maxLength(255),
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('inspection_company')
                            ->label('Inspection Company')
                            ->maxLength(255),
                        TextInput::make('inspector_name')
                            ->label("Inspector's Name")
                            ->maxLength(255),
                        TextInput::make('inspector_company_phone')
                            ->label("Inspection Co. Phone")
                            ->maxLength(255),
                        TextInput::make('inspector_id')
                            ->label("Inspector's ID")
                            ->maxLength(255),
                    ]),
                Forms\Components\Fieldset::make('Management Company and On-site Contact Info')
                    ->columns(3)
                    ->statePath('management_onsite_info')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),
                        TextInput::make('onsite_contact')
                            ->label('On-site Contact')
                            ->maxLength(255),
                        TextInput::make('role_title')
                            ->label('Role or Title')
                            ->maxLength(255),
                        Select::make('mgmt_affiliation')
                            ->label('Mgmt Affiliation')
                            ->options([
                                'Affiliated with the Borrower' => 'Affiliated with the Borrower',
                                'Nonaffiliated, Third Party' => 'Nonaffiliated, Third Party'
                            ]),
                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->maxLength(255),
                        Select::make('mgmt_interview')
                            ->label('Mgmt Interview')
                            ->options([
                                'Yes, On-site' => 'Yes, On-Site',
                                'Yes, Prior to visit' => 'Yes, Prior to visit',
                                'Yes, Prior to visit and Onsite' => 'Yes, Prior to visit and Onsite',
                                'No, Not Required' => 'No, Not Required',

                            ]),
                        Select::make('time_at_property')
                            ->label('Length of Time at Property')
                            ->options([
                                '< 6 mo' => '< 6 mo',
                                '6 mo to < 1 yr' => '6 mo to < 1 yr',
                                '1 yr to < 3 yr' => '1 yr to < 3 yr',
                                '3 yr to < 5 yr' => '3 yr to < 5 yr',
                                '5 yr or more' => '5 yr or more',
                            ]),
                        Select::make('management_changed')
                            ->label('Mgmt company change since last inspection')
                            ->options([
                                'Yes' => 'Yes',
                                'No' => 'No'
                            ]),
                    ]),
                Forms\Components\Fieldset::make('Service and Inspector Comments')
                    ->statePath('comments')
                    ->schema([
                        Textarea::make('servicer_comments')
                            ->label("Lender's or Servicer's General Comments or Instructions to Inspector for Subject Property")
                            ->default('NA')
                        ,
                        Textarea::make('inspector_comments')
                            ->label("Property Inspector's General Comments or Suggestions to Lender or Servicer on the Subject Property")
                            ->default('NOTE: This is not a fire or life safety inspection and it does not address the integrity or structural soundness of the property. ')
                        ,
                    ]),
                Forms\Components\Fieldset::make('Profile and Occupancy')
                    ->columns(3)
                    ->statePath('profile_occupancy_info')
                    ->schema([
                        TextInput::make('number_of_buildings')
                            ->label('Number of Buildings')->numeric()
                            ->minValue(0),
                        TextInput::make('number_of_floors')
                            ->label('Number of Floors')->numeric()
                            ->minValue(0),
                        TextInput::make('number_of_elevators')
                            ->label('Number of Elevators')->numeric()
                            ->minValue(0),
                        TextInput::make('number_of_parking_spaces')
                            ->label('Number of Parking Spaces')->numeric()
                            ->minValue(0),
                        TextInput::make('year_built')
                            ->label('Year Built')->numeric()
                            ->minValue(0),
                        TextInput::make('year_renovated')
                            ->label('Year Renovated')->numeric()
                            ->minValue(0),
                        TextInput::make('annual_occupancy')
                            ->label('Annual Occupancy')->numeric()
                            ->minValue(0),
                        TextInput::make('annual_turnover')
                            ->label('Annual Turnover')->numeric()
                            ->minValue(0),
                        Select::make('rent_roll_obtained')
                            ->label('Rent Roll Obtained')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Forms\Components\DatePicker::make('rent_roll_date')
                            ->label('Rent Roll Date'),
                        Select::make('is_affordable_housing')
                            ->label('Is Affordable Housing?')
                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                        Select::make('unit_of_measurement_used')
                            ->label('Units of Measurement Used')
                            ->options(['Units' => 'Units', 'Rooms' => 'Rooms', 'Beds' => 'Beds', 'Sq. Feet' => 'Sq. Feet']),
                        TextInput::make('num_of_rooms')->label('Number of Units/Rooms/Beds')->numeric()
                            ->minValue(0),
                        TextInput::make('occupied_space')
                            ->label('Occupied Space')->numeric()
                            ->minValue(0),
                        TextInput::make('vacant_space')
                            ->label('Vacant Space')->numeric()
                            ->minValue(0),
                        TextInput::make('occupied_units_inspected')
                            ->label('Occupied Units Inspected')->numeric()
                            ->minValue(0),
                        TextInput::make('vacant_units_inspected')
                            ->label('Vacant Units Inspected')->numeric()
                            ->minValue(0),
                        TextInput::make('total_sq_feet_gross')
                            ->label('Total Sq. Feet (Gross)')->numeric()
                            ->minValue(0),
                        TextInput::make('total_sq_feet_net')
                            ->label('Total Sq. Feet (Net)')->numeric()
                            ->minValue(0),
                        Select::make('dark_space')
                            ->label('Is there any Dark Space?')
                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable', 'Unknown' => 'Unknown']),
                        Select::make('down_space')
                            ->label('Is there any Down Space?')
                            ->options(['Yes' => 'Yes', 'No' => 'No'])->live(),
                        TextInput::make('num_of_down_units')
                            ->label('Number of Down Units/Rooms/Beds')
                            ->default(0)->numeric()
                            ->minValue(0)->visible(fn($get) => $get('down_space') == 'Yes')->reactive(),
                        Textarea::make('dark_down_space_description')
                            ->label('Describe Dark/Down Space If Any')
                        ,
                        Select::make('rental_concessions_offered')
                            ->label('Offers Rental Concessions?')
                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable', 'Unknown' => 'Unknown']),
                        TextInput::make('describe_rental_concession')
                            ->label('Describe Rental Concessions'),
                        TextInput::make('franchise_name')
                            ->label('Franchise Name'),
                        Select::make('franchise_change_since_last_inspection')
                            ->label('Franchise Change Since Last Inspection?')
                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable', 'Unknown' => 'Unknown']),
                    ]),
                Section::make('Operation and Maintenance Plans (O & M)')
                    ->columns(1)
                    ->description('Plans such as, but not limited to, Operations and Maintenance, Moisture Management and Environmental Remediation.')
                    ->statePath('operation_maintenance_plans')
                    ->schema([
                        Repeater::make('Plan')
                            ->columns(3)
                            ->schema([
                                Select::make('plan_name')
                                    ->options(['Asbestos' => 'Asbestos', 'Lead Paint' => 'Lead Paint', 'Moisture/Mold' => 'Moisture/Mold', 'Radon' => 'Radon', 'Storage Tanks' => 'Storage Tanks', 'PCB(polychlorinated biphenyl)' => 'PCB(polychlorinated biphenyl)', 'Other, specified below' => 'Other, specified below', 'Unknown' => 'Unknown']),
                                Select::make('management_aware')
                                    ->label('Management Aware of Plan?')
                                    ->options(['Yes' => 'Yes', 'No' => 'No', 'Unknown' => 'Unknown']),
                                Select::make('plan_available')
                                    ->label('Plan Available?')
                                    ->options(['Yes, On-site' => 'Yes, On-site', 'Yes, Off-site' => 'Yes, Off-site', 'No' => 'No', 'Unknown' => 'Unknown']),
                            ]),
                        Textarea::make('describe_om_plans')->label('Specify Additional O&M Plans or describe any observed non-compliance')
                    ]),
                Section::make('Capital Expenditures')
                    ->columns(1)
                    ->description('Plans such as, but not limited to, Operations and Maintenance, Moisture Management and Environmental Remediation.')
                    ->statePath('capital_expenditures')
                    ->schema([
                        Repeater::make('Expenditure')
                            ->columns(3)
                            ->schema([
                                TextInput::make('repair_description')
                                    ->label('Repair Description'),
                                TextInput::make('identified_cost')
                                    ->label('Identified Cost')->numeric()
                                    ->minValue(0)->inputMode('decimal'),
                                Select::make('status')
                                    ->options(['Completed' => 'Completed', 'In-Progress' => 'In-Progress', 'Planned' => 'Planned']),
                            ]),
                    ]),
                Forms\Components\Fieldset::make('Neighborhood / Site Comparison Data')
                    ->columns(3)
                    ->statePath('neighborhood_site_data')
                    ->schema([
                        Section::make('Top 2 Major Competitors')
                            ->schema([
                                TextInput::make('name_or_type_competitor_1')
                                    ->label('Name or Type')
                                ,
                                TextInput::make('distance_competitor_1')
                                    ->label('Distance'),
                                TextInput::make('name_or_type_competitor_2')
                                    ->label('Name or Type')
                                ,
                                TextInput::make('distance_competitor_2')
                                    ->label('Distance'),
                            ]),
                        Select::make('single_family_percent_use')
                            ->label('Single Family')
                            ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                        Select::make('multi_family_percent_use')
                            ->label('Multifamily')
                            ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                        Select::make('commercial_percent_use')
                            ->label('Commerical')
                            ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                        Select::make('industrial_percent_use')
                            ->label('Industrial')
                            ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                        Select::make('is_declining_area')
                            ->label('Is the area declining or distressed?')->options(['No' => 'No', 'Yes, described below' => 'Yes, described below']),
                        Select::make('is_new_construction_in_area')
                            ->label('Is there any new construction in area?')->options(['No' => 'No', 'Yes, described below' => 'Yes, described below']),
                        Textarea::make('area_trends_description')
                            ->label('Describe area, visibility, access, surrounding land use & overall trends (including location in relation to subject N,S,E,W)'),
                        Textarea::make('collateral_description')
                            ->label('Additional Collateral Description Information'),
                    ])
            ]);
    }

    public static function reportPhysicalStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Physical Condition & DM')
            ->columns(3)
            ->visible(fn($get) => in_array('2', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('2', $get('form_steps')))
            ->schema([

                Section::make('Physical Condition Assessment and Deffered Maintenance')
                    ->statePath('physical_condition')
                    ->schema([
                        Section::make('Curb Appeal')
                            ->columns()
                            ->description('Comparsion to Neighborhood; First Impression / Appearance')
                            ->schema([
                                Select::make('curb_appeal_rating')->label('Curb Appeal Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('curb_appeal_trend')->label('Curb Appeal Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('curb_appeal_inspector_comments')->label('Curb Appeal Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Site')
                            ->columns()
                            ->description('Inspection Appearance; Signage; Ingress / Egress; Landscaping; Site Lightning; Parking Lot; Striping; Garage; Car Ports; Irrigation System; Drainage; Retaining Walls; Walkways; Fencing; Refuse Containment & Cleanliness; Hazardous Material Storage')
                            ->schema([
                                Select::make('site_rating')->label('Site Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('site_trend')->label('Site Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('site_inspector_comments')->label('Site Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Building / Mechanical Systems')
                            ->columns()
                            ->description('HVAC; Electrical; Boilers; Water Heaters; Fire Protection; Sprinklers; Plumbing; Sewer; Solar Systems; Elevators / Escalators; Chiller Plant; Cooling Towers; Building Oxygen; Intercom Systeml; PA System; Security Systems')
                            ->schema([
                                Select::make('mechanical_rating')->label('Mechanical Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('mechanical_trend')->label('Mechanical Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('mechanical_inspector_comments')->label('Mechanical Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Building Exteriors')
                            ->columns()
                            ->description('Siding; Trim; Paint; Windows; Entry Ways; Stairs; Railings; Balconies; Patios; Gutters; Downspouts; Foundations; Doors; Facade; Structure (Beam/Joint)')
                            ->schema([
                                Select::make('exterior_rating')->label('Exteriors Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('exterior_trend')->label('Exteriors Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('exterior_inspector_comments')->label('Exteriors Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Building Roofs')
                            ->columns()
                            ->description('Roof Condition; Roof Access; Top Floor Ceilings; Shingles / Membrane; Skylights; Flashing; Parapet Walls; Mansard Roofs')
                            ->schema([
                                Select::make('roofs_rating')->label('Roofs Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('roofs_trend')->label('Roofs Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('roofs_inspector_comments')->label('Roofs Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Occupied Units/Space')
                            ->columns()
                            ->description('HVAC; Ceiling; Floors; Walls; Painting; Wall Cover; Floor Cover; Tiles; Windows; Countertop; Cabinets; Appliances; Lightning; Electrical; Bathroom Accessories; Plumbing Fixtures; Storage; Basements / Attics')
                            ->schema([
                                Select::make('occupied_rating')->label('Occupied Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('occupied_trend')->label('Occupied Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('occupied_inspector_comments')->label('Occupied Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Vacant Units / Space / Hotel Rooms')
                            ->columns()
                            ->description('Inspection Appearance; Signage; Ingress / Egress; Landscaping; Site Lightning; Parking Lot; Striping; Garage; Car Ports; Irrigation System; Drainage; Retaining Walls; Walkways; Fencing; Refuse Containment & Cleanliness; Hazardous Material Storage')
                            ->schema([
                                Select::make('vacant_rating')->label('Vacant Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('vacant_trend')->label('Vacant Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('vacant_inspector_comments')->label('Vacant Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Down Units / Space / Hotel Rooms')
                            ->columns()
                            ->description('Inspection Appearance; Signage; Ingress / Egress; Landscaping; Site Lightning; Parking Lot; Striping; Garage; Car Ports; Irrigation System; Drainage; Retaining Walls; Walkways; Fencing; Refuse Containment & Cleanliness; Hazardous Material Storage')
                            ->schema([
                                Select::make('down_rating')->label('Down Units')
                                    ->options(['Yes' => 'Yes', 'No' => 'No']),
                                Select::make('down_trend')->label('Down Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('down_inspector_comments')->label('Down Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Interior Common Areas')
                            ->columns()
                            ->description('Mailboxes; Reception Area; Lobby; Food Courts; Dining Area; Kitchen; Halls; Stairways; Meeting Rooms; Public Restrooms; Storage; Basement; Healthcare Assistance Rooms; Pharmacy / Medication Storage; Nurses Station')
                            ->schema([
                                Select::make('interior_common_rating')->label('Interior Common Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('interior_common_trend')->label('Interior Common Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('interior_common_inspector_comments')->label('Interior Common Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Amenities')
                            ->columns()
                            ->description('Pool; Clubhouse; Gym; Laundry Area / Rooms; Playground; Wireless Access; Restaurant / Bar; Business Center; Sport Courts; Spa; Store; Media Center')
                            ->schema([
                                Select::make('amenities_rating')->label('Amenities Rating')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('amenities_trend')->label('Amenities Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('amenities_inspector_comments')->label('Amenities Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Section::make('Environmental')
                            ->columns()
                            ->description('Reported spills or leaks; Evidence of spills or leaks; Evidence of distressed vegetation; Evidence of mold; Evidence of O&M non-compliance')
                            ->schema([
                                Select::make('interior_rating')->label('Interior Rating')
                                    ->options(['No' => 'No', 'Minor' => 'Minor', 'Major' => 'Major']),
                                Select::make('interior_trend')->label('Interior Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                Textarea::make('interior_inspector_comments')->label('Interior Inspector Comments')
                                    ->columnSpanFull()
                            ]),
                        Textarea::make('exterior_additional_desc')->label('Exterior - Additional description of the propery condition')->columnSpanFull(),
                        Textarea::make('interior_additional_desc')->label('Interior - Additional description of the propery condition')->columnSpanFull(),
                        Section::make('Deffered Maintenance Items')
                            ->schema([
                                Repeater::make('deferred_items')
                                    ->columns(5)
                                    ->schema([
                                        TextInput::make('description')->label('Description')
                                            ->helperText('Identify Item and Describe Condition (including location)'),
                                        Select::make('rating')->label('Rating')
                                            ->options(['Minor' => 'Minor', 'Major' => 'Major']),
                                        Select::make('life_safety')->label('Life Safety')
                                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                                        TextInput::make('photo_number')->label('Photo Number'),
                                        TextInput::make('estimated_cost')->label('Estimated Cost'),
                                    ])
                            ])

                    ])
            ]);
    }

    public static function reportPhotoStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Photos')
            ->visible(fn($get) => in_array('3', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('3', $get('form_steps')))
            ->schema([
                Forms\Components\FileUpload::make('temp_images')->multiple()->image()->reorderable()->appendFiles()->dehydrated(false)
                    ->label('Bulk Image Upload')->imageResizeUpscale(false)->panelLayout('grid')->columnSpanFull(),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('process_images')->action(function ($get, $set) {
                        $rep_data = $get('images');
                        $urls = [];
                        $images = $get('temp_images');
                        foreach ($images as $image) {
                            if ($image instanceof TemporaryUploadedFile) {
                                $manager = new ImageManager(new Driver());
                                $ext = $image->getClientOriginalExtension();
                                $f_name = Str::random(40) . '.' . $ext;
                                $img = $manager->read($image->getRealPath());
                                // Resize the image while maintaining the aspect ratio
                                $img->scaleDown(800);
                                $img = $img->toJpeg();
                                Storage::disk('s3')->put($f_name, $img, 'public');
                                $image->delete();
                            }
                            $rep_data[] = [
                                'photo_type' => '',
                                'photo_description' => '',
                                'photo_url' => [$f_name]
                            ];
                            $urls[] = $f_name;
                        }
                        $key = now()->timestamp;
                        Cache::forever($key, $urls);
                        $keys = Cache::get('temp_keys', []);
                        $keys[] = $key;
                        Cache::forever('temp_keys', $keys);
                        $set('temp_images', []);
                        $set('temp_key', $key);
                        $set('images', $rep_data);
                    })
                ])->alignCenter()->verticallyAlignCenter(),
                Repeater::make('images')
                    ->grid(4)
                    ->addable(false)
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Forms\Components\FileUpload::make('photo_url')->label('Photo')->deletable(false)->disk('s3'),
                        Select::make('photo_type')->label('Photo Type')
                            ->options(['Exterior' => 'Exterior', 'Interior' => 'Interior', 'Roof' => 'Roof', 'Neighborhood' => 'Neighborhood', 'Routine Maintenance' => 'Routine Maintenance', 'Deferred Maintenance' => 'Deferred Maintenance', 'Life Safety' => 'Life Safety']),
                        Textarea::make('photo_description')->label('Photo Description'),
                    ])
                    ->itemLabel(function () {
                        static $position = 1;
                        return 'Photo # ' . $position++;
                    })
                    ->default([])
            ]);

    }

    public static function reportRentStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Rent Roll')
            ->columns(3)
            ->visible(fn($get) => in_array('4', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('4', $get('form_steps')))
            ->schema([
                Section::make('Rent Roll')
                    ->statePath('rent_roll')
                    ->schema([
                        Select::make('rent_roll_attached')
                            ->options(['Yes' => 'Yes', 'No' => 'No'])->live(),
                        Select::make('rent_roll_missing_reason')
                            ->options(['Hard Copy to follow' => 'Hard Copy to follow', 'Requested but not provided' => 'Requested but not provided', 'Requested but declined' => 'Requested but declined', 'Not Applicable' => 'Not Applicable'])
                            ->disabled(fn($get) => $get('rent_roll_attached') != 'No'),
                        Select::make('rent_roll_summary_attached')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Select::make('single_tenant_property')
                            ->options(['Yes' => 'Yes', 'No' => 'No'])->live(),
                        TextInput::make('lease_expires')
                            ->disabled(fn($get) => $get('single_tenant_property') != 'Yes'),
                        Select::make('hospitality_property')->live()
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        TextInput::make('ytd_adr')->label('YTD ADR')
                            ->disabled(fn($get) => $get('hospitality_property') != 'Yes'),
                        TextInput::make('revpar')->label('RevPAR')
                            ->disabled(fn($get) => $get('hospitality_property') != 'Yes'),
                        TextInput::make('ado')->label('ADO')
                            ->disabled(fn($get) => $get('hospitality_property') != 'Yes'),
                        Section::make('Largest Commerical Tenants')
                            ->columns(1)
                            ->schema([
                                Repeater::make('tenant_info')
                                    ->columns(6)
                                    ->schema([
                                        TextInput::make('tenant_name')->label('Tenant Name'),
                                        TextInput::make('expiration')->label('Expiration'),
                                        TextInput::make('sq_ft')->prefix('$')->label('Sq. Ft.')->default(0)->numeric()
                                            ->minValue(0)->live(onBlur: true)->afterStateUpdated(fn($set, $get, $state) => $set('rent_per_sqft', number_format($get('annual_rent') / $state))),
                                        TextInput::make('nra_percentage')->label('% NRA'),
                                        TextInput::make('annual_rent')->prefix('$')->label('Annual Rent')->default(0)->live(onBlur: true)->numeric()
                                            ->minValue(0)->inputMode('decimal')->afterStateUpdated(fn($set, $get, $state) => $set('rent_per_sqft', number_format($state / $get('sq_ft'), 2))),
                                        TextInput::make('rent_per_sqft')->prefix('$')->label('Rent / Sq. Ft.')->readOnly()
                                    ])
                            ])
                    ]),
            ]);
    }

    public static function reportMgmtInterviewStep(): Forms\Components\Component
    {
        return
            Forms\Components\Tabs\Tab::make('Management Interview')
                ->columns(3)
                ->statePath('mgmt_interview')
                ->visible(fn($get) => in_array('5', $get('form_steps')))
                ->dehydrated(fn($get) => in_array('5', $get('form_steps')))
                ->schema([
                    Section::make('Management Information & Interview')
                        ->columns()
                        ->schema([
                            TextInput::make('management_company_name')
                                ->label('Management Company Name'),
                            TextInput::make('name_information_source')
                                ->label('Name of Information Source'),
                            TextInput::make('role_title_information_source')
                                ->label('Role or Title of Information Source'),
                            Select::make('management_affiliation')
                                ->label('Mgmt Affiliation')
                                ->options([
                                    'Affiliated with the Borrower' => 'Affiliated with the Borrower',
                                    'Nonaffiliated, Third Party' => 'Nonaffiliated, Third Party'
                                ]),
                            TextInput::make('phone_number')
                                ->label('Phone Number'),
                            TextInput::make('email_address')
                                ->label('Email Address'),
                            Select::make('length_at_property')
                                ->label('Length of time at property')
                                ->options([
                                    '< 6 mo' => '< 6 mo',
                                    '6 mo to < 1 yr' => '6 mo to < 1 yr',
                                    '1 yr to < 3 yr' => '1 yr to < 3 yr',
                                    '3 yr to < 5 yr' => '3 yr to < 5 yr',
                                    '5 yr or more' => '5 yr or more',
                                ]),
                            Select::make('mgmt_change_last_inspection')
                                ->label('Mgmt change from last inspection')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No'
                                ]),
                        ]),
                    Section::make('Neighborhood and Rental Market')
                        ->schema([
                            Select::make('property_performance_question')
                                ->label('In your opinion, how does the property perform compared to similar properties in the area?')
                                ->options([
                                    'Superior' => 'Superior',
                                    'Average' => 'Average',
                                    'Below Average' => 'Below Average',
                                ]),
                            TextInput::make('average_vacancy_percentage')
                                ->label('In your opinion, what is the average percentage of vacancy in similar properties in the area?'),
                            TextInput::make('average_rent_current')
                                ->label('What is the current average rent paid in the area ($ per square foot/units/beds)?'),
                            Select::make('vacancy_comparison_last_year')
                                ->label('How does the current vacancy compare to last year at this time?')
                                ->options([
                                    'Similar' => 'Similar',
                                    'Increased' => 'Increased',
                                    'Decreased' => 'Decreased',
                                    'Unknown' => 'Unknown',
                                ]),
                            Textarea::make('vacany_variance_explanation')
                                ->label('In your opinion, explain the reason for any variance on vacancy, and rents between the market and the subject property:'),
                            Forms\Components\Toggle::make('major_change_area')->live()
                                ->label('Any change to a major employer in the area, or major commercial/retail operation in the area?'),
                            Textarea::make('major_change_area_description')
                                ->label('If yes, describe:')->visible(fn($get) => $get('major_change_area')),
                            Forms\Components\Grid::make()
                                ->schema([
                                    TextInput::make('Amount of the last rental increase')
                                        ->label('Amount of the last rental increase'),
                                    Forms\Components\DatePicker::make('Date of last rental increase')
                                        ->label('Date of last rental increase'),
                                    TextInput::make('Number of Administration Employees')
                                        ->label('Number of Administration Employees')->numeric()
                                        ->minValue(0)->extraAttributes(['min' => '0']),
                                    TextInput::make('Number of Maintenance Employees')
                                        ->label('Number of Maintenance Employees')->numeric()
                                        ->minValue(0),
                                    Select::make('Heat at the Property')
                                        ->label('Heat at the Property')
                                        ->options([
                                            'Not Applicable' => 'Not Applicable',
                                            'Paid by Tenant' => 'Paid by Tenant',
                                            'Paid by Owner' => 'Paid by Owner',
                                        ]),
                                    Select::make('Water at the Property')
                                        ->label('Water at the Property')
                                        ->options([
                                            'Not Applicable' => 'Not Applicable',
                                            'Paid by Tenant' => 'Paid by Tenant',
                                            'Paid by Owner' => 'Paid by Owner',
                                        ]),
                                    Select::make('Electric at the Property')
                                        ->label('Electric at the Property')
                                        ->options([
                                            'Not Applicable' => 'Not Applicable',
                                            'Paid by Tenant' => 'Paid by Tenant',
                                            'Paid by Owner' => 'Paid by Owner',
                                        ]),
                                    Select::make('Gas at the Property')
                                        ->label('Gas at the Property')
                                        ->options([
                                            'Not Applicable' => 'Not Applicable',
                                            'Paid by Tenant' => 'Paid by Tenant',
                                            'Paid by Owner' => 'Paid by Owner',
                                        ]),
                                    Select::make('Trash at the Property')
                                        ->label('Trash at the Property')
                                        ->options([
                                            'Not Applicable' => 'Not Applicable',
                                            'Paid by Tenant' => 'Paid by Tenant',
                                            'Paid by Owner' => 'Paid by Owner',
                                        ]),
                                    Select::make('Cable at the Property')
                                        ->label('Cable at the Property')
                                        ->options([
                                            'Not Applicable' => 'Not Applicable',
                                            'Paid by Tenant' => 'Paid by Tenant',
                                            'Paid by Owner' => 'Paid by Owner',
                                        ]),
                                    Section::make('Tenant Profile')
                                        ->columns(3)
                                        ->statePath('tenant_profile')
                                        ->schema([
                                            Select::make('Corporate')
                                                ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                            Select::make('Military')
                                                ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                            Select::make('Seasonal')
                                                ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                            Select::make('Seniors')
                                                ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                            Select::make('Students')
                                                ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                            Select::make('Other')
                                                ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                        ])
                                ])
                        ]),
                    Section::make('Property Events')
                        ->statePath('property_events')
                        ->schema([
                            Select::make('key_employee_replaced')
                                ->label('In the past 12 months, has there been any key employee turnover or any key employee replaced?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Select::make('significant_propoerty_damage')
                                ->label('In the past 12 months, have there been any fires, significant water intrusion or other property damage?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Textarea::make('significant_propoerty_damage_explanation')
                                ->label('If yes, explain the location on the property, costs associated, any insurance claims submitted, resolution and leaseability:'),
                            Select::make('code_violation_received')
                                ->label('In the past 12 months, to the best of your knowledge, have any code violations been received?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Textarea::make('code_violation_explanation')
                                ->label('If yes, please describe the violation, the costs associated, and any resolution or outstanding issues:'),
                            Select::make('significant_rehab_construction')
                                ->label('Is the property undergoing any significant rehab/construction?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Textarea::make('significant_rehab_construction_explanation')
                                ->label('If yes, explain the location, size and estimated costs:'),
                            Select::make('franchise_agreement_change')
                                ->label('Any change or violations of a Franchise Agreement or License(s)?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Textarea::make('franchise_agreement_change_explanation')
                                ->label('If yes, please explain any change or violation, costs and any resolution or outstanding issues:'),
                            Select::make('lawsuits_pending')
                                ->label('To the best of your knowledge, are there any lawsuits pending that may negatively impact the property?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Textarea::make('lawsuits_pending_explanation')
                                ->label('If yes, please explain:'),
                            Select::make('special_assessments')
                                ->label('If a Co-op, has the corporation had the need to use special assessments to cover expenses?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Textarea::make('special_assessments_explanation')
                                ->label('If yes, please explain:'),
                            Select::make('short_term_leases')
                                ->label('Are there units or corporate leases for the purposes of home sharing (home sharing can be defined as
short-term (<1 month) rentals generally marketed through an online platform such as Airbnb)?')
                                ->options([
                                    'Yes' => 'Yes',
                                    'No' => 'No',
                                    'Unknown' => 'Unknown'
                                ]),
                            Textarea::make('short_term_leases_explanatino')
                                ->label('If yes, please explain:'),
                            Textarea::make('management_evaluation_comments')
                                ->label('Other Information and Management Evaluation Comments:')


                        ])

                ]);
    }

    public static function reportMultifamilyStep(): Forms\Components\Component
    {
        return
            Forms\Components\Tabs\Tab::make('Multifamily')
                ->columns(3)
                ->statePath('multifamily')
                ->visible(fn($get) => in_array('6', $get('form_steps')))
                ->dehydrated(fn($get) => in_array('6', $get('form_steps')))
                ->schema([
                    Section::make('Multifamily, Mobile Homes, Cooperative Housing, Student Housing')
                        ->schema([
                            Select::make('any_commercial_units')
                                ->label('Any Commercial Units?')
                                ->options(['Yes' => 'Yes', 'No' => 'No', 'Unknown' => 'Unknown']),
                            TextInput::make('num_commercial_units')
                                ->label('If yes, how many?')
                                ->numeric()
                                ->minValue(0),
                            TextInput::make('commercial_units_inspected')
                                ->label('Number Commercial units Inspected:')
                                ->numeric()
                                ->minValue(0),
                        ]),
                    Section::make('Multifamily Unit Breakdown')
                        ->statePath('multifamily_unit_breakdown')
                        ->schema([
                            Repeater::make('unit_info')
                                ->columns(10)
                                ->schema([
                                    TextInput::make('bedrooms')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('baths')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('num_of_units')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('avg_sqft_unit')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('avg_rent')
                                        ->numeric()
                                        ->minValue(0)->inputMode('decimal'),
                                    TextInput::make('occupied')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('non-revenue')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('vacant')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('down')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('inspected')
                                        ->numeric()
                                        ->minValue(0),
                                ])
                        ]),
                    Section::make('Detailed Report of Units Inspected')
                        ->statePath('unit_detail_report')
                        ->schema([
                            Repeater::make('unit_detail')
                                ->columns(7)
                                ->schema([
                                    TextInput::make('unit_no'),
                                    TextInput::make('bedrooms')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('baths')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('square_feet')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('asking_rent')
                                        ->numeric()
                                        ->minValue(0)->inputMode('decimal'),
                                    TextInput::make('current_use'),
                                    TextInput::make('overall_condition')
                                ]),
                        ]),
                    Textarea::make('general_comments')
                        ->label('General Comments')->columnSpanFull(),
                ]);
    }

    public static function reportFannieMaeStep(): Forms\Components\Component
    {
        return
            Forms\Components\Tabs\Tab::make('Fannie Mae Assmt Addendum')
                ->columns(3)
                ->statePath('fannie_mae_assmt')
                ->visible(fn($get) => in_array('7', $get('form_steps')))
                ->dehydrated(fn($get) => in_array('7', $get('form_steps')))
                ->schema([
                    Section::make('Limitations of Field Assessment')
                        ->statePath('limitations_of_field_assessment')
                        ->schema([
                            Forms\Components\CheckboxList::make('limitations_experienced')
                                ->label('Did you experience any of the following limitations to performing this field assessment: (Choose Yes/No)')
                                ->options([
                                    "Management unavailable for interview?" => "Management unavailable for interview?",
                                    "Management experience on the property is less than six months?" => "Management experience on the property is less than six months?",
                                    "Occupied units were unavailable for assessment, or the total number of units available (occupied or unoccupied) was insufficient?" => "Occupied units were unavailable for assessment, or the total number of units available (occupied or unoccupied) was insufficient?",
                                    "Significant portions of the common areas, amenities or basements, etc. were unavailable for assessment?" => "Significant portions of the common areas, amenities or basements, etc. were unavailable for assessment?",
                                    "Snow was covering most exterior areas (parking lots, roofs, landscape areas)?" => "Snow was covering most exterior areas (parking lots, roofs, landscape areas)?",
                                    "Other Limitation" => "Other Limitation",
                                ]),
                            Textarea::make('limitation_comment')->label('Limitation Comment')->columnSpanFull()
                        ]),
                    Section::make('Comprehensive Property Assessment Ratings')
                        ->statePath('property_assessment_ratings')
                        ->schema([
                            Select::make('life_safety')->label('Life Safety')
                                ->options([
                                    '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                    '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                    '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                    '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                    '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                ]),
                            Textarea::make('life_safety_comments')->columnSpanFull(),
                            Select::make('deffered_maintenance')->label('Deffered Maintenance')
                                ->options([
                                    '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                    '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                    '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                    '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                    '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                ]),
                            Textarea::make('deffered_maintenance_comments')->columnSpanFull(),
                            Select::make('routine_maintenance')->label('Routine Maintenance')
                                ->options([
                                    '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                    '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                    '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                    '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                    '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                ]),
                            Textarea::make('routine_maintenance_comments')->columnSpanFull(),
                            Select::make('capital_needs')->label('Capital Needs')
                                ->options([
                                    '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                    '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                    '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                    '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                    '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                ]),
                            Textarea::make('capital_needs_comments')->columnSpanFull(),
                            Select::make('volume_of_issues_noted')->label('Level/Volume of issues noted and appropriate follow-up recommendations')
                                ->options([
                                    '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                    '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                    '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                    '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                    '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                ]),
                            Textarea::make('volume_of_issues_noted_comments')->columnSpanFull(),
                            Select::make('overall_property_rating')->label('Overall Property Ratings')
                                ->options([
                                    '1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',
                                    '5' => '5'
                                ]),
                            Textarea::make('overall_property_rating_comments')->columnSpanFull(),
                            Section::make('Seller/Servicer Certification')
                                ->statePath('seller_servicer_certification')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\DatePicker::make('date'),
                                    TextInput::make('first_name')->label('First Name'),
                                    TextInput::make('last_name')->label('Last Name'),
                                    TextInput::make('title')->label('Title'),
                                    TextInput::make('phone_number')->label('Phone Number'),
                                    TextInput::make('email_address')->label('Email Address'),
                                ])

                        ])
                ]);
    }

    public static function reportFREStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('FRE Assmt Addendum')
            ->columns(3)
            ->statePath('fre_assmt')
            ->visible(fn($get) => in_array('8', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('8', $get('form_steps')))
            ->schema([
                Section::make('Physical Inspection Additional Questions')
                    ->statePath('physical_assmt_add_questions')
                    ->schema([
                        Select::make('deferred_maintenance_outstanding')
                            ->label('Are any deferred maintenance items outstanding from the last inspection?')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Textarea::make('deferred_maintenance_detail')
                            ->label('If Yes, please specify items that remain outstanding and include impact of outstanding items on overall property appeal and condition'),
                        Select::make('harmful_environment_condition')
                            ->label('Was a harmful environmental condition observed which is not covered by an existing O&M plan (such as mold)?')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Textarea::make('harmful_environment_detail')
                            ->label('If Yes, please discuss below'),
                        Select::make('out_of_compliance_ada')
                            ->label('Is the property out of compliance with any applicable ADA requirements?')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Textarea::make('out_of_compliance_detail')
                            ->label('If Yes, please discuss below'),

                    ]),
            ]);
    }

    public static function reportRepairStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Repairs Verification')
            ->columns(3)
            ->statePath('repairs_verification')
            ->visible(fn($get) => in_array('9', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('9', $get('form_steps')))
            ->schema([
                Section::make('Property Information')
                    ->statePath('property_info')
                    ->columns(4)
                    ->schema([
                        TextInput::make('name'),
                        TextInput::make('address'),
                        TextInput::make('address_2'),
                        TextInput::make('city'),
                        TextInput::make('state')
                            ->label('State'),
                        TextInput::make('zip'),
//                        TextInput::make('country'),
                    ]),
                Section::make('Inspection Scheduling Contact Info')
                    ->columns(4)
                    ->schema([
                        TextInput::make('contact_company')
                            ->label('Contact Company')
                            ->maxLength(255),
                        TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->maxLength(255),
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('inspection_company')
                            ->label('Inspection Company')
                            ->maxLength(255),
                        TextInput::make('inspector_name')
                            ->label("Inspector's Name")
                            ->maxLength(255),
                        TextInput::make('inspector_company_phone')
                            ->label("Inspection Co. Phone")
                            ->maxLength(255),
                        TextInput::make('inspector_id')
                            ->label("Inspector's ID")
                            ->maxLength(255),
                    ]),
                Section::make('Servicer Info')
                    ->columns(4)
                    ->schema([
                        TextInput::make('servicer_name')->label('Servicer Name'),
                        TextInput::make('loan_number')->label('Loan Number'),
                        Select::make('primary_type')
                            ->label('Primary Property Type')
                            ->options(['Health Care' => 'Health Care', 'Industrial' => 'Industrial', 'Lodging' => 'Lodging', 'Multifamily' => 'Multifamily', 'Mobile Home Park' => 'Mobile Home Park', 'Mixed Use' => 'Mixed Use', 'Office' => 'Office', 'Other' => 'Other', 'Retail' => 'Retail', 'Self Storage' => 'Self Storage']),
                    ]),
                Section::make('Completion Details')
                    ->columns()
                    ->schema([
                        TextInput::make('expected_percentage_complete')->label('Expected percentage completed')
                            ->numeric()
                            ->minValue(0)->maxValue(100)->suffix('%')->maxValue(100),
                        TextInput::make('overall_observed_percentage_complete')->label('Overall observed percentage completed')
                            ->numeric()
                            ->minValue(0)->maxValue(100)->suffix('%')->maxValue(100),
                    ]),
                Section::make('Repairs Verification')
                    ->schema([
                        Textarea::make('general_summary_comments')
                            ->label('General description of improvements and summary comments'),
                        Repeater::make('verification_list')
                            ->columns(4)
                            ->schema([
                                Textarea::make('item_description'),
                                Textarea::make('inspector_comments'),
                                TextInput::make('photo_number')->label('Photo Number'),
                                Select::make('repair_status')
                                    ->options([
                                        'Repairs Complete' => 'Repairs Complete',
                                        'Partially - Inprogress' => 'Partially - Inprogress',
                                        'Partially - Complete' => 'Partially - Complete',
                                        'Repairs Scheduled' => 'Repairs Scheduled',
                                        'Repairs Planned' => 'Repairs Planned',
                                        'Unknown' => 'Unknown',
                                    ])
                            ])

                    ]),
            ]);
    }

    public static function reportSeniorStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Senior Supplement')
            ->columns(4)
            ->statePath('senior_supplement')
            ->visible(fn($get) => in_array('10', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('10', $get('form_steps')))
            ->schema([
                Section::make('Part I: Physical Inspection')
                    ->statePath('physical_inspection')
                    ->description('Indicate condition of seniors housing components below. Any identified repair costs are strictly for seniors housing components and should not have already been identified on the Physical Condition/DM tab.')
                    ->schema([
                        Section::make('Site (Seniors)')
                            ->columns(3)
                            ->statePath('site_seniors')
                            ->description('Bus-Van-Handicapped Parking; Building Accessibility; Outdoor Activity Area; Generator')
                            ->schema([
                                Select::make('current_condition')
                                    ->label('Current Condition')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('trend')
                                    ->label('Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                TextInput::make('repair_cost')->label('Repair Cost')
                                    ->numeric()
                                    ->minValue(0)->inputMode('decimal'),
                                Select::make('life_safety')->label('Life Safety')
                                    ->options(['Yes' => 'Yes', 'No' => 'No']),
                                Textarea::make('inspector_comments')
                                    ->label('Inspector Comments')->columnSpanFull(),


                            ]),
                        Section::make('Interior Common Areas (Seniors)')
                            ->columns(3)
                            ->statePath('interior_common_seniors')
                            ->description('Healthcare Assistance Rooms; Pharmacy/Medication Storage; Nurses Station; Bathing Assistance Areas; Employee Restroom; Facility Furniture; Kitchen; Pantry-Supplies Storage; Common/Private Dining Areas')
                            ->schema([
                                Select::make('current_condition')
                                    ->label('Current Condition')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('trend')
                                    ->label('Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                TextInput::make('repair_cost')->label('Repair Cost')
                                    ->numeric()
                                    ->minValue(0)->inputMode('decimal'),
                                Select::make('life_safety')->label('Life Safety')
                                    ->options(['Yes' => 'Yes', 'No' => 'No']),
                                Textarea::make('inspector_comments')
                                    ->label('Inspector Comments')->columnSpanFull(),


                            ]),
                        Section::make('Amenities (Seniors)')
                            ->columns(3)
                            ->statePath('amenities_seniors')
                            ->description('Television-Sitting Areas; Exercise-Wellness Room; Game-Entertainment Room; Library-Reading Room; Craft-Activity Room; Beauty/Barber Shop; Sundry Shop; Family-Meeting Area; Garden; Wheelchairs-Walkers')
                            ->schema([
                                Select::make('current_condition')
                                    ->label('Current Condition')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                Select::make('trend')
                                    ->label('Trend')
                                    ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                TextInput::make('repair_cost')->label('Repair Cost')
                                    ->numeric()
                                    ->minValue(0)->inputMode('decimal'),
                                Select::make('life_safety')->label('Life Safety')
                                    ->options(['Yes' => 'Yes', 'No' => 'No']),
                                Textarea::make('inspector_comments')
                                    ->label('Inspector Comments')->columnSpanFull(),


                            ]),
                    ]),
                Section::make('Part II: Resident Room/Occupancy')
                    ->statePath('resident_room_occupany')
                    ->schema([
                        Section::make('Types of Services Provided')
                            ->columnSpanFull()
                            ->statePath('types_of_services')
                            ->schema([
                                Section::make('Independent Living')
                                    ->statePath('indepedent_living')
                                    ->columns(6)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        TextInput::make('total_units')->label('Total # of Units'),
                                        TextInput::make('occupied_units')->label('Occupied Units'),
                                        TextInput::make('total_units')->label('Total # of Beds'),
                                        TextInput::make('total_units')->label('Occupied Beds'),
                                        TextInput::make('resident_payor_type')->label('Resident Payor Type'),

                                    ]),
                                Section::make('Congregate Care Retirement Community (CCRC)')
                                    ->statePath('comgregate_care_community')
                                    ->columns(6)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        TextInput::make('total_units')->label('Total # of Units'),
                                        TextInput::make('occupied_units')->label('Occupied Units'),
                                        TextInput::make('total_units')->label('Total # of Beds'),
                                        TextInput::make('total_units')->label('Occupied Beds'),
                                        TextInput::make('resident_payor_type')->label('Resident Payor Type'),

                                    ]),
                                Section::make('Assisted Living')
                                    ->statePath('assisted_living')
                                    ->columns(6)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        TextInput::make('total_units')->label('Total # of Units'),
                                        TextInput::make('occupied_units')->label('Occupied Units'),
                                        TextInput::make('total_units')->label('Total # of Beds'),
                                        TextInput::make('total_units')->label('Occupied Beds'),
                                        TextInput::make('resident_payor_type')->label('Resident Payor Type'),

                                    ]),
                                Section::make('Alzheimer\'s / Memory Care')
                                    ->statePath('alzhemiers_memory_care')
                                    ->columns(6)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        TextInput::make('total_units')->label('Total # of Units'),
                                        TextInput::make('occupied_units')->label('Occupied Units'),
                                        TextInput::make('total_units')->label('Total # of Beds'),
                                        TextInput::make('total_units')->label('Occupied Beds'),
                                        TextInput::make('resident_payor_type')->label('Resident Payor Type'),

                                    ]),
                                Section::make('Skilled Nursing')
                                    ->statePath('skilled_nursing')
                                    ->columns(6)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        TextInput::make('total_units')->label('Total # of Units'),
                                        TextInput::make('occupied_units')->label('Occupied Units'),
                                        TextInput::make('total_units')->label('Total # of Beds'),
                                        TextInput::make('total_units')->label('Occupied Beds'),
                                        TextInput::make('resident_payor_type')->label('Resident Payor Type'),

                                    ]),
                                Section::make('Other (specify)')
                                    ->statePath('other_specifiy')
                                    ->columns(7)
                                    ->schema([
                                        TextInput::make('other_service_name')->label('Service Name'),
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        TextInput::make('total_units')->label('Total # of Units'),
                                        TextInput::make('occupied_units')->label('Occupied Units'),
                                        TextInput::make('total_units')->label('Total # of Beds'),
                                        TextInput::make('total_units')->label('Occupied Beds'),
                                        TextInput::make('resident_payor_type')->label('Resident Payor Type'),

                                    ]),


                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Select::make('unit_mix_comply')
                                    ->label('Does the current unit mix comply with the unit mix specified in the Mortgage?')
                                    ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable', 'Unknown' => 'Unknown']),
                                Textarea::make('unit_mix_comment')->label('Unit Mix Comment')->columnSpan(2),
                                Textarea::make('days_turn_resident_unit')->label('How many days does it take to turn a resident unit? Explain if more than 2 days.')->columnSpanFull(),
                                Textarea::make('units_for_retenating')->label('How many units are currently being prepared for re-tenanting?')->columnSpanFull(),

                            ])


                    ]),
                Section::make('Part III: Resident Services')
                    ->statePath('resident_services')
                    ->description('Indicate which services are included in resident\'s basic fee and frequency of service, where applicable. ')
                    ->schema([
                        Section::make('Resident Services')
                            ->statePath('resident_services')
                            ->schema([
                                Section::make('24-hour Nursing Care')
                                    ->statePath('24h_nursing_care')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Physician service')
                                    ->statePath('physician_service')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Medication assistance')
                                    ->statePath('medication_assistance')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Specialized dietary services')
                                    ->statePath('specialized_dietary_services')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Meals')
                                    ->statePath('meals')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Regular health assessments')
                                    ->statePath('regular_health_assessments')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Scheduled transportation')
                                    ->statePath('scheduled_transportation')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Unscheduled transportation')
                                    ->statePath('unscheduled_transportation')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Social and activity programs')
                                    ->statePath('social_and_activity_programs')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Housekeeping')
                                    ->statePath('housekeeping')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Laundry service')
                                    ->statePath('laundry_service')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Select::make('frequency')->label('Frequency')
                                            ->options(['Hourly' => 'Hourly', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Bi-Weekly' => 'Bi-Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),


                            ]),
                        Section::make('Safety & Security')
                            ->statePath('safety_security')
                            ->schema([
                                Section::make('Exit doors alarmed')
                                    ->statePath('exit_doors_alarmed')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Wandergard/Elopement system')
                                    ->statePath('wandergard_elopment_system')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Dementia unit secured')
                                    ->statePath('dementia_unit_secured')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Nurses stations')
                                    ->statePath('nurses_stations')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('quantity')->numeric()
                                            ->minValue(0),
                                        Textarea::make('locations')->label('Locations')->columnSpan(3)
                                    ]),
                            ]),
                        Section::make('Meal Service')
                            ->statePath('meal_service')
                            ->schema([
                                Section::make('Licensed dietician on staff')
                                    ->statePath('licensed_dietician_on_staff')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Menu choices available')
                                    ->statePath('menu_choices_available')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Snacks available')
                                    ->statePath('snacks_available')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Meals delivered to units')
                                    ->statePath('meals_delivered_to_units')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),


                            ]),
                        Section::make('Medication Administration')
                            ->statePath('medication_administration')
                            ->schema([
                                Section::make('Staff utilizes medication aides')
                                    ->statePath('staff_utilizes_medication_aides')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Staff utilizes medication cart')
                                    ->statePath('staff_utilizes_medication_cart')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Medication room secured')
                                    ->statePath('medication_room_secured')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),


                            ]),
                        Textarea::make('staff_permitted_to_medication')
                            ->label('List staff that is permitted to administer resident medication')
                            ->columnSpanFull(),
                        Textarea::make('resident_medication_documented')
                            ->label('Indicate how resident medication is documented')
                            ->columnSpanFull(),
                        Section::make('Direct Care Personnel (Staff on Duty)')
                            ->statePath('direct_care_persons')
                            ->schema([
                                Section::make('RN\'s')
                                    ->statePath('rns')
                                    ->columns(5)
                                    ->schema([
                                        TextInput::make('day')->label('Day')->numeric()
                                            ->minValue(0),
                                        TextInput::make('evening')->label('Evening')->numeric()
                                            ->minValue(0),
                                        TextInput::make('night')->label('Night')->numeric()
                                            ->minValue(0),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('LPN\'s')
                                    ->statePath('lpns')
                                    ->columns(5)
                                    ->schema([
                                        TextInput::make('day')->label('Day')->numeric()
                                            ->minValue(0),
                                        TextInput::make('evening')->label('Evening')->numeric()
                                            ->minValue(0),
                                        TextInput::make('night')->label('Night')->numeric()
                                            ->minValue(0),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Others')
                                    ->statePath('others')
                                    ->columns(5)
                                    ->schema([
                                        TextInput::make('day')->label('Day')->numeric()
                                            ->minValue(0),
                                        TextInput::make('evening')->label('Evening')->numeric()
                                            ->minValue(0),
                                        TextInput::make('night')->label('Night')->numeric()
                                            ->minValue(0),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Administrative Personnel')
                                    ->statePath('administrative_personnel')
                                    ->columns(5)
                                    ->schema([
                                        TextInput::make('day')->label('Day')->numeric()
                                            ->minValue(0),
                                        TextInput::make('evening')->label('Evening')->numeric()
                                            ->minValue(0),
                                        TextInput::make('night')->label('Night')->numeric()
                                            ->minValue(0),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),


                            ]),


                    ]),
                Section::make('Part IV: Management')
                    ->statePath('management')
                    ->description('Are there written Policies & Procedures in place for the following')
                    ->schema([
                        Section::make('Inspector\'s Discussion with Management Staff')
                            ->statePath('inspectors_discussion_staff')
                            ->description()
                            ->schema([
                                Section::make('ADA & Fair Housing')
                                    ->statePath('ada_fair_housing')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Contracting & purchasing')
                                    ->statePath('contracting_purchasing')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Emergency evacuation')
                                    ->statePath('emergency_evacuation')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Employee performance')
                                    ->statePath('employee_performance')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Incident reporting')
                                    ->statePath('ada_fair_housing')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Resident care')
                                    ->statePath('resident_care')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Transferring resident to/from assisted living')
                                    ->statePath('transferring_from_assisted_living')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Transferring resident to/from health care facility')
                                    ->statePath('transferring_from_healthcare')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                            ]),
                        Section::make('Property Budget')
                            ->statePath('property_budget')
                            ->schema([
                                Section::make('Property annual budget (attach copy)')
                                    ->statePath('annual_budget')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Planned capital improvements in next 12 months')
                                    ->statePath('planned_capital_improvements')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                            ]),
                        Section::make('Property Staffing')
                            ->statePath('property_staffing')
                            ->schema([
                                Section::make('Scheduled meetings with staff')
                                    ->statePath('scheduled_meetings_with_staff')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Scheduled meetings with residents')
                                    ->statePath('scheduled_meetings_with_residents')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Social & Activities program for residents')
                                    ->statePath('social_activities_program')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Employee training opportunities')
                                    ->statePath('employee_training_opportunities')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                            ]),
                        Section::make('Estimated Annual Employee Turnover')
                            ->statePath('estimated_annual_employee_turnover')
                            ->schema([
                                TextInput::make('direct_care_givers')->label('Direct care givers')->numeric()
                                    ->minValue(0)->inputMode('decimal')->inlineLabel(),
                                TextInput::make('administrative_personnel')->label('Administrative personnel')->numeric()
                                    ->minValue(0)->inputMode('decimal')->inlineLabel(),
                            ]),
                        Section::make('Staffing experience of key personnel')
                            ->statePath('staffing_experience_of_key_personnel')
                            ->schema([
                                TextInput::make('administrative_executive_director')->label('Administrative/Executive Director')->numeric()
                                    ->minValue(0)->inputMode('decimal')->inlineLabel(),
                                TextInput::make('head_care_giver_resident_assistant')->label('Head Care Giver/Resident Assistant')->numeric()
                                    ->minValue(0)->inputMode('decimal')->inlineLabel(),

                            ]),
                        Section::make('Inspector\'s Comments on Management Performance')
                            ->statePath('inspectors_comments_management')
                            ->schema([
                                Textarea::make('staff_interaction_with_residents')->label('Staff interaction with residents')->columnSpanFull(),
                                Textarea::make('appearance_of_residents')->label('Appearance of residents/suitability for time of day')->columnSpanFull(),
                                Textarea::make('attire_and_demeanor_of_staff')->label('Attire and demeanor of staff')->columnSpanFull(),
                                Textarea::make('overall_cleanliness_of_facility')->label('Overall cleanliness of facility; any odors present')->columnSpanFull(),

                            ])


                    ]),
                Section::make('Part V: Marketing')
                    ->statePath('marketing')
                    ->schema([
                        Section::make('Inspector\'s Discussion with Marketing Staff')
                            ->statePath('inspector_discussion_marketing_staff')
                            ->schema([
                                Section::make('Is there a written marketing plan?')
                                    ->statePath('marketing_plan')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Potential resident list/waiting list?')
                                    ->statePath('resident_waiting_list')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Networking with religious organizations, hospitals, etc.?')
                                    ->statePath('networking_religious_orgs')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Marketing material distribution/outreach?')
                                    ->statePath('marketing_material_distribution')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Are telemarketing or other marketing tools used?')
                                    ->statePath('telemarketing_tools')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Property brochure and application')
                                    ->statePath('property_brochure_and_application')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Resident handbook (attach copy)')
                                    ->statePath('resident_handbook')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Model unit available?')
                                    ->statePath('model_unit_available')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Rent concessions?')
                                    ->statePath('rent_concessions')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Number of marketing personnel')
                                    ->statePath('num_marketing_personnel')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('num')->label('Number of Personnel'),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Combined years experience of marketing personnel')
                                    ->statePath('years_experience_marketing_personnel')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('years')->label('Years of Experience'),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Textarea::make('design_comparison')->label('How do the unit design, square footage, and amenities compare with comparable seniors housing properties in this market?')
                                    ->columnSpanFull(),


                            ]),
                        Section::make('Competitor Analysis')
                            ->statePath('competitor_analysis')
                            ->schema([
                                Repeater::make('competitor')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('name')->label('Name of Facility'),
                                        TextInput::make('num_units')->label('# of units'),
                                        TextInput::make('type_of_property')->label('Type of Property'),
                                        TextInput::make('name_of_operator')->label('Name of Operator'),
                                    ])
                            ])

                    ]),
                Section::make('Part VI: Regulatory Compliance')
                    ->statePath('regulatory_compliance')
                    ->schema([
                        Section::make('Regulatory / Licensing Agency')
                            ->schema([
                                TextInput::make('regulator_name')->label('Name of Regulatory or Licensing Agency'),
                                TextInput::make('regulator_contact_person')->label('Regulatory Agency Contact Person'),
                                Forms\Components\DatePicker::make('license_expiration_date')->label('Expiration Date of Operating License')
                            ]),
                        Section::make('Regulatory / Licensing Agency Inspection')
                            ->schema([
                                Forms\Components\DatePicker::make('last_visit_date')->label('Date of Last Agency Visit')
                            ]),
                        Section::make('Purpose of Visit')
                            ->statePath('purpose_of_visit')
                            ->schema([
                                Section::make('Certification/Licensure')
                                    ->statePath('certification_licensure')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Life/Safety')
                                    ->statePath('life_safety')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Follow-up')
                                    ->statePath('follow_up')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Other (describe)')
                                    ->statePath('other_purpose')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('purpose')->label('Purpose')->columnSpan(2),
                                        Textarea::make('comments')->label('Comments')->columnSpan(2)
                                    ]),
                                Section::make('Were deficiencies cited?')
                                    ->statePath('deficiencies_cited')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Were non-monetary penalties assessed?')
                                    ->statePath('non_monetary_fines_assessed')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Were monetary penalties/fines assessed?')
                                    ->statePath('monetary_fines_assessed')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Agency considers property in compliance?')
                                    ->statePath('property_in_compliance')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Copy of regulatory agency\'s report received?')
                                    ->statePath('regulatory_agency_report_received')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Copy of operator\'s plan of correction received?')
                                    ->statePath('operator_correction_received')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Were any of the corrective actions related to the resident care and/or criminal background checks?')
                                    ->statePath('resident_care_criminal_background')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('plan_of_action')->label('If yes, what is the plan(s) of correction and status of such corrective actions?')->columnSpan(3)
                                    ]),


                            ]),
                        Section::make('Changes in Regulatory Oversight?')
                            ->statePath('changes_in_oversight')
                            ->schema([
                                Section::make('Staffing Requirements')
                                    ->statePath('staffing_requirements')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Physical Design')
                                    ->statePath('physical_design')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Health & Safety Codes')
                                    ->statePath('health_safety_codes')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Government Subsidies')
                                    ->statePath('government_subsidies')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Reimbursement Programs')
                                    ->statePath('reimbursement_programs')
                                    ->columns(4)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),

                            ]),
                        Section::make('Other Required Property Licenses')
                            ->statePath('other_required_licenses')
                            ->schema([
                                Section::make('Commercial Kitchen/Food & Beverage Permit')
                                    ->statePath('commercial_kitchen_beverage_permit')
                                    ->columns(5)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Forms\Components\DatePicker::make('expire_date')->label('Expire Date'),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Commercial Vehicle')
                                    ->statePath('commercial_vehicle')
                                    ->columns(5)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Forms\Components\DatePicker::make('expire_date')->label('Expire Date'),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Elevator')
                                    ->statePath('elevator')
                                    ->columns(5)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Forms\Components\DatePicker::make('expire_date')->label('Expire Date'),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Third Party Healthcare')
                                    ->statePath('third_party_healthcare')
                                    ->columns(5)
                                    ->schema([
                                        Select::make('status')->label('Yes / No / N/A')
                                            ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                        Forms\Components\DatePicker::make('expire_date')->label('Expire Date'),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),
                                Section::make('Other (describe)')
                                    ->statePath('other')
                                    ->columns(5)
                                    ->schema([
                                        TextInput::make('name')->label('License Name'),
                                        Forms\Components\DatePicker::make('expire_date')->label('Expire Date'),
                                        Textarea::make('comments')->label('Comments')->columnSpan(3)
                                    ]),

                            ]),
                        Section::make('Miscellaneous')
                            ->statePath('miscellaneous')
                            ->schema([
                                Section::make('Are there any material violations, lawsuits or judgments against any licensed professional employed by the operator?')
                                    ->statePath('material_violations_lawsuits_professional')
                                    ->columns(5)
                                    ->schema([
                                        Select::make('status')->label('Yes / No')
                                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                                        Textarea::make('detail')->label('Detail')->columnSpan(4)
                                    ]),
                                Section::make('Are there any material violations, lawsuits or judgments against any other personnel at the property?')
                                    ->statePath('material_violations_lawsuits_personnel')
                                    ->columns(5)
                                    ->schema([
                                        Select::make('status')->label('Yes / No')
                                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                                        Textarea::make('detail')->label('Detail')->columnSpan(4)
                                    ]),

                            ])


                    ]),

            ]);
    }

    public static function reportHospitalStep(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('Hospitals')
            ->columns(3)
            ->statePath('hospitals')
            ->visible(fn($get) => in_array('11', $get('form_steps')))
            ->dehydrated(fn($get) => in_array('11', $get('form_steps')))
            ->schema([
                Section::make('General Property Info')
                    ->columns(3)
                    ->schema([
                        Select::make('new_patients_accepted')
                            ->label('New Patients Currently being Accepted')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Select::make('admission_waiting_period')
                            ->label('Admission Waiting Period')
                            ->options([
                                'Yes, 1-15 Days' => 'Yes, 1-15 Days',
                                'Yes, 16-30 Days' => 'Yes, 16-30 Days',
                                'Yes, 31-60 Days' => 'Yes, 31-60 Days',
                                'Yes, 61-120 Days' => 'Yes, 61-120 Days',
                                'Yes, 121+ Days' => 'Yes, 121+ Days',
                                'No Waiting Period' => 'No Waiting Period'
                            ]),
                        Select::make('proximity_to_hospital')
                            ->label('Proximity to a Hospital')
                            ->options([
                                'On site' => 'On site',
                                'Less than 1 mile' => 'Less than 1 mile',
                                '1 to < 5 miles' => '1 to < 5 miles',
                                '5 to <10 miles' => '5 to <10 miles',
                                '10 or more miles' => '10 or more miles',
                            ])
                    ]),
                Section::make('Level of Care Breakdown')
                    ->statePath('level_of_care_breakdown')
                    ->schema([
                        Repeater::make('unit_info')
                            ->columns(7)
                            ->schema([
                                Select::make('unit_type')
                                    ->label('Unit Type')->options([
                                        'Assisted Living/Congregate Care' => 'Assisted Living/Congregate Care',
                                        'Hospital' => 'Hospital',
                                        'Nursing Home, Unskilled' => 'Nursing Home, Unskilled',
                                        'Nursing Home, Skilled' => 'Nursing Home, Skilled',
                                        'Specialty Health Care' => 'Specialty Health Care'
                                    ]),
                                TextInput::make('total_beds')->label('Total Beds')->numeric()
                                    ->minValue(0),
                                TextInput::make('occupied_beds')->label('Total Beds Occupied')->numeric()
                                    ->minValue(0),
                                TextInput::make('total_units')->label('Total Units')->numeric()
                                    ->minValue(0),
                                TextInput::make('occupied_units')->label('Total Units Occupied')->numeric()
                                    ->minValue(0),
                                TextInput::make('average_sq_feet_unit')->label('Sq. Feet / Unit')->numeric()
                                    ->minValue(0)->inputMode('decimal'),
                                TextInput::make('monthly_rent')->label('Monthly Rent')->numeric()
                                    ->minValue(0)->inputMode('decimal'),
                            ]),
                        Forms\Components\Grid::make()
                            ->schema([
                                TextInput::make('administrator_name')->label("Administrator's Name"),
                                Select::make('administrator_length_at_property')->label('Length of Time at Property')
                                    ->options([
                                        '< 6 mos' => '< 6 mos',
                                        '6 m to < 1 yr' => '6 m to < 1 yr',
                                        '1 to < 3 yrs' => '1 to < 3 yrs',
                                        '3 to < 5 yrs' => '3 to < 5 yrs',
                                        '5 yrs or longer' => '5 yrs or longer'
                                    ]),
                                TextInput::make('director_nursing_name')->label("Director of Nursing's Name"),
                                Select::make('director_nursing_length_at_property')->label('Length of Time at Property')
                                    ->options([
                                        '< 6 mos' => '< 6 mos',
                                        '6 m to < 1 yr' => '6 m to < 1 yr',
                                        '1 to < 3 yrs' => '1 to < 3 yrs',
                                        '3 to < 5 yrs' => '3 to < 5 yrs',
                                        '5 yrs or longer' => '5 yrs or longer'
                                    ]),
                            ]),
                        Section::make('Direct Care Staff Numbers')
                            ->statePath('direct_care_staff_numbers')
                            ->columns(7)
                            ->schema([
                                Forms\Components\Placeholder::make('nurses_rns')->label('Nurses RNs'),
                                TextInput::make('nurses_rns_1')->label(''),
                                TextInput::make('nurses_rns_2')->label(''),
                                TextInput::make('nurses_rns_3')->label(''),
                                TextInput::make('nurses_rns_comments')->label('')->helperText('Comments')->columnSpan(3),
                                Forms\Components\Placeholder::make('nurses_lpns')->label('Nurses LPNs'),
                                TextInput::make('nurses_lpns_1')->label(''),
                                TextInput::make('nurses_lpns_2')->label(''),
                                TextInput::make('nurses_lpns_3')->label(''),
                                TextInput::make('nurses_lpns_comments')->label('')->helperText('Comments')->columnSpan(3),
                                Forms\Components\Placeholder::make('other_direct_care')->label('Other Direct Care'),
                                TextInput::make('other_direct_care_1')->label(''),
                                TextInput::make('other_direct_care_2')->label(''),
                                TextInput::make('other_direct_care_3')->label(''),
                                TextInput::make('other_direct_care_comments')->label('')->helperText('Comments')->columnSpan(3),
                                Forms\Components\Placeholder::make('non_direct_care')->label('Non Direct Care Personnel'),
                                TextInput::make('non_direct_care_1')->label(''),
                                TextInput::make('non_direct_care_2')->label(''),
                                TextInput::make('non_direct_care_3')->label(''),
                                TextInput::make('non_direct_care_comments')->label('')->helperText('Comments')->columnSpan(3),
                            ]),
                    ]),
                Section::make('Regulatory / Licensing Agency Information')
                    ->statePath('regulatory_agency_information')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name_of_agency')->label('Name of Agency'),
                        TextInput::make('contact_person')->label('Contact Person'),
                        Forms\Components\DatePicker::make('expiration_date_license')->label('Expiration Date of Operating License'),
                        Select::make('all_licenses_current')->label('All Licenses Current')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Forms\Components\DatePicker::make('date_medicare_inspection')->label('Date of last Medicare inspection'),
                        Select::make('medicare_certified')->label('Property Medicare Certified')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Forms\Components\DatePicker::make('date_medicaid_inspection')->label('Date of last Medicaid inspection'),
                        Select::make('medicaid_certified')->label('Property Medicaid Certified')
                            ->options(['Yes' => 'Yes', 'No' => 'No']),
                        Textarea::make('violations_description')->label('Please describe any violations, costs associated, resolution or outstanding issues')->columnSpanFull(),
                    ]),
                Section::make('Property Condition')
                    ->statePath('property_condition')
                    ->columns(3)
                    ->schema([
                        Select::make('handrails_in_halls')->label('Handrails in the halls')
                            ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                        Select::make('grab_bars_present')->label('Grab bars present in rest rooms')
                            ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                        Select::make('exits_marked')->label('Exits clearly marked')
                            ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                        Select::make('staff_interacts_well')->label('Staff interacts well with residents')
                            ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                        Select::make('intercom_system')->label('Intercom System')
                            ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                        Select::make('looks_smells_clean')->label('Facility looks and smells clean')
                            ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                        Select::make('generator_function')->label('Generator Function')
                            ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                        Textarea::make('additional_condition_description')->label('Additional description of any safety or deficiency issues observed')->columnSpanFull(),
                        TextInput::make('down_units_numbers')->label('Down Units (List the unit #)')->inlineLabel()->columnSpanFull()
                    ]),
                Section::make('Detailed Report of Units Inspected')
                    ->statePath('detailed_report_of_units_inspected')
                    ->schema([
                        Repeater::make('unit_inspection_detail')
                            ->columns(7)
                            ->schema([
                                TextInput::make('unit_number')->label('Unit Number'),
                                TextInput::make('bedrooms')->label('Bedrooms'),
                                TextInput::make('baths')->label('Baths'),
                                TextInput::make('sq_feet')->label('Square Feet'),
                                TextInput::make('asking_rent')->label('Asking Rent'),
                                Select::make('current_use')->label('Current Use')
                                    ->options(['Occupied Unfurnished' => 'Occupied Unfurnished', 'Occupied Furnished' => 'Occupied Furnished', 'Down Unit' => 'Down Unit', 'Vacant Unfurnished, Ready' => 'Vacant Unfurnished, Ready', 'Vacant Unfurnished' => 'Vacant Unfurnished', 'Vacant Furnished, Ready' => 'Vacant Furnished, Ready', 'Vacant Furnished' => 'Vacant Furnished', 'Non-Revenue' => 'Non-Revenue', 'Commercial Unit' => 'Commercial Unit']),
                                Select::make('overall_condition')->label('Overall Condition')
                                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible', 'Not Inspected' => 'Not Inspected']),
                            ])
                    ])


            ]);
    }


}
