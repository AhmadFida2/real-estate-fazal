<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Inspection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;


class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Choose Report Tabs')
                        ->schema([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('fill_old_data')->label('Get Old Data')
                                    ->form([
                                        Forms\Components\Select::make('inspection_old_id')->label('Select Old Inspection')
                                            ->options(fn() => Inspection::all()->pluck('name', 'id'))->columns(3)
                                    ])
                                    ->action(function ($livewire, $data) {
                                        $data = Inspection::find($data['inspection_old_id']);
                                        $livewire->form->fill($data->toArray());
                                    }),
                            ]),
                            Forms\Components\Radio::make('report_type')->label('Report Type')->dehydrated(false)
                                ->inline()
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
                                ])->columnSpanFull()->columns(4)
                        ]),
                    Forms\Components\Wizard\Step::make('Basic Info')
                        ->visible(fn($get) => in_array('1', $get('form_steps')))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('address')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('address_2')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('city')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('state_id')
                                ->label('State')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('zip')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('country')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('overall_rating')
                                ->label('Overall Rating')
                                ->required()
                                ->numeric(),
                            Forms\Components\Select::make('rating_scale')
                                ->label('Rating Scale')
                                ->required()
                                ->options(['MBA' => 'MBA', 'Fannie Mae' => 'Fannie Mae']),
                            Forms\Components\DateTimePicker::make('inspection_date')
                                ->label('Inspection Date')
                                ->required(),
                            Forms\Components\Select::make('primary_type')
                                ->live()
                                ->label('Primary Type')
                                ->required()
                                ->options(['Health Care' => 'Health Care', 'Industrial' => 'Industrial', 'Lodging' => 'Lodging', 'Multifamily' => 'Multifamily', 'Mobile Home Park' => 'Mobile Home Park', 'Mixed Use' => 'Mixed Use', 'Office' => 'Office', 'Other' => 'Other', 'Retail' => 'Retail', 'Self Storage' => 'Self Storage']),
                            Forms\Components\Select::make('secondary_type')
                                ->label('Secondary Type')
                                ->required()
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
                                    Forms\Components\TextInput::make('servicer_name')
                                        ->label('Servicer Name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('loan_number')
                                        ->label('Loan Number')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('property_id')
                                        ->label('InspectionCollection ID')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('servicer_inspection_id')
                                        ->label('Servicer Inspection ID')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('original_loan_amount')
                                        ->label('Original Loan Amount')
                                        ->required()
                                        ->numeric()
                                        ->inputMode('decimal'),
                                    Forms\Components\TextInput::make('loan_balance')
                                        ->label('Loan Balance')
                                        ->required()
                                        ->numeric()
                                        ->inputMode('decimal'),
                                    Forms\Components\DatePicker::make('loan_balance_date')
                                        ->label('Loan Balance Date')
                                        ->required(),
                                    Forms\Components\TextInput::make('loan_owner')
                                        ->label('Owner of Loan')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('investor_number')
                                        ->label('Investor Number')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('investor_loan_number')
                                        ->label('Investor Loan Number')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('asset_manager_name')
                                        ->label('Asset Manager Name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('asset_manager_phone')
                                        ->label('Asset Manager Phone')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('asset_manager_email')
                                        ->label('Asset Manager Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('report_reviewed_by')
                                        ->label('Report Reviewed By')
                                        ->required()
                                        ->maxLength(255),
                                ]),
                            Forms\Components\Fieldset::make('Contact Company and Inspector Info')
                                ->columns(3)
                                ->statePath('contact_inspector_info')
                                ->schema([
                                    Forms\Components\TextInput::make('contact_company')
                                        ->label('Contact Company')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('contact_name')
                                        ->label('Contact Name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('contact_phone')
                                        ->label('Contact Phone')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('contact_email')
                                        ->label('Contact Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspection_company')
                                        ->label('Inspection Company')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspector_name')
                                        ->label("Inspector's Name")
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspector_company_phone')
                                        ->label("Inspection Co. Phone")
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspector_id')
                                        ->label("Inspector's ID")
                                        ->required()
                                        ->maxLength(255),
                                ]),
                            Forms\Components\Fieldset::make('Management Company and On-site Contact Info')
                                ->columns(3)
                                ->statePath('management_onsite_info')
                                ->schema([
                                    Forms\Components\TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('onsite_contact')
                                        ->label('On-site Contact')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('role_title')
                                        ->label('Role or Title')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('mgmt_affiliation')
                                        ->label('Mgmt Affiliation')
                                        ->required()
                                        ->options([
                                            'Affiliated with the Borrower' => 'Affiliated with the Borrower',
                                            'Nonaffiliated, Third Party' => 'Nonaffiliated, Third Party'
                                        ]),
                                    Forms\Components\TextInput::make('phone_number')
                                        ->label('Phone Number')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('mgmt_interview')
                                        ->label('Mgmt Interview')
                                        ->required()
                                        ->options([
                                            'Yes, On-site' => 'Yes, On-Site',
                                            'Yes, Prior to visit' => 'Yes, Prior to visit',
                                            'Yes, Prior to visit and Onsite' => 'Yes, Prior to visit and Onsite',
                                            'No, Not Required' => 'No, Not Required',

                                        ]),
                                    Forms\Components\Select::make('time_at_property')
                                        ->label('Length of Time at InspectionCollection')
                                        ->required()
                                        ->options([
                                            '< 6 mo' => '< 6 mo',
                                            '6 mo to < 1 yr' => '6 mo to < 1 yr',
                                            '1 yr to < 3 yr' => '1 yr to < 3 yr',
                                            '3 yr to < 5 yr' => '3 yr to < 5 yr',
                                            '5 yr or more' => '5 yr or more',
                                        ]),
                                    Forms\Components\Select::make('management_changed')
                                        ->label('Mgmt company change since last inspection')
                                        ->required()
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No'
                                        ]),
                                ]),
                            Forms\Components\Fieldset::make('Service and Inspector Comments')
                                ->statePath('comments')
                                ->schema([
                                    Forms\Components\Textarea::make('servicer_comments')
                                        ->label("Lender's or Servicer's General Comments or Instructions to Inspector for Subject InspectionCollection")
                                        ->default('NA')
                                        ->required(),
                                    Forms\Components\Textarea::make('inspector_comments')
                                        ->label("InspectionCollection's Inspector's General Comments or Suggestions to Lender or Servicer on the Subject InspectionCollection")
                                        ->default('NOTE: This is not a fire or life safety inspection and it does not address the integrity or structural soundness of the property. ')
                                        ->required(),
                                ]),
                            Forms\Components\Fieldset::make('InspectionCollection Profile and Occupancy')
                                ->columns(3)
                                ->statePath('profile_occupancy_info')
                                ->schema([
                                    Forms\Components\TextInput::make('number_of_buildings')
                                        ->label('Number of Buildings')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('number_of_floors')
                                        ->label('Number of Floors')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('number_of_elevators')
                                        ->label('Number of Elevators')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('number_of_parking_spaces')
                                        ->label('Number of Parking Spaces')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('year_built')
                                        ->label('Year Built')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('year_renovated')
                                        ->label('Year Renovated')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('annual_occupancy')
                                        ->label('Annual Occupancy')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('annual_turnover')
                                        ->label('Annual Turnover')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('rent_roll_obtained')
                                        ->label('Rent Roll Obtained')
                                        ->required()->numeric(),
                                    Forms\Components\DatePicker::make('rent_roll_date')
                                        ->label('Rent Roll Date')
                                        ->required(),
                                    Forms\Components\Select::make('is_affordable_housing')
                                        ->label('Is InspectionCollection Affordable Housing?')
                                        ->required()
                                        ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable']),
                                    Forms\Components\Select::make('unit_of_measurement_used')
                                        ->label('Units of Measurement Used')
                                        ->required()
                                        ->options(['Units' => 'Units', 'Rooms' => 'Rooms', 'Beds' => 'Beds', 'Sq. Feet' => 'Sq. Feet']),
                                    Forms\Components\TextInput::make('num_of_rooms')->label('Number of Units/Rooms/Beds')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('occupied_space')
                                        ->label('Occupied Space')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('vacant_space')
                                        ->label('Vacant Space')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('occupied_units_inspected')
                                        ->label('Occupied Units Inspected')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('vacant_units_inspected')
                                        ->label('Vacant Units Inspected')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('total_sq_feet_gross')
                                        ->label('Total Sq. Feet (Gross)')
                                        ->required()->numeric(),
                                    Forms\Components\TextInput::make('total_sq_feet_net')
                                        ->label('Total Sq. Feet (Net)')
                                        ->required()->numeric(),
                                    Forms\Components\Select::make('dark_space')
                                        ->label('Is there any Dark Space?')
                                        ->required()
                                        ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable', 'Unknown' => 'Unknown']),
                                    Forms\Components\Select::make('down_space')
                                        ->label('Is there any Down Space?')
                                        ->required()
                                        ->options(['Yes' => 'Yes', 'No' => 'No'])->live(),
                                    Forms\Components\TextInput::make('num_of_down_units')
                                        ->label('Number of Down Units/Rooms/Beds')
                                        ->default(0)
                                        ->required()->numeric()->visible(fn($get) => $get('down_space') == 'Yes')->reactive(),
                                    Forms\Components\Textarea::make('dark_down_space_description')
                                        ->label('Describe Dark/Down Space If Any')
                                        ->required(),
                                    Forms\Components\Select::make('rental_concessions_offered')
                                        ->label('InspectionCollection Offers Rental Concessions?')
                                        ->required()
                                        ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable', 'Unknown' => 'Unknown']),
                                    Forms\Components\TextInput::make('describe_rental_concession')
                                        ->label('Describe Rental Concessions'),
                                    Forms\Components\TextInput::make('franchise_name')
                                        ->label('Franchise Name'),
                                    Forms\Components\Select::make('franchise_change_since_last_inspection')
                                        ->label('Franchise Change Since Last Inspection?')
                                        ->options(['Yes' => 'Yes', 'No' => 'No', 'Not Applicable' => 'Not Applicable', 'Unknown' => 'Unknown']),
                                ]),
                            Forms\Components\Section::make('Operation and Maintenance Plans (O & M)')
                                ->columns(1)
                                ->description('Plans such as, but not limited to, Operations and Maintenance, Moisture Management and Environmental Remediation.')
                                ->statePath('operation_maintenance_plans')
                                ->schema([
                                    Forms\Components\Repeater::make('Plan')
                                        ->columns(3)
                                        ->schema([
                                            Forms\Components\Select::make('plan_name')
                                                ->options(['Asbestos' => 'Asbestos', 'Lead Paint' => 'Lead Paint', 'Moisture/Mold' => 'Moisture/Mold', 'Radon' => 'Radon', 'Storage Tanks' => 'Storage Tanks', 'PCB(polychlorinated biphenyl)' => 'PCB(polychlorinated biphenyl)', 'Other, specified below' => 'Other, specified below', 'Unknown' => 'Unknown']),
                                            Forms\Components\Select::make('management_aware')
                                                ->label('Management Aware of Plan?')
                                                ->options(['Yes' => 'Yes', 'No' => 'No', 'Unknown' => 'Unknown']),
                                            Forms\Components\Select::make('plan_available')
                                                ->label('Plan Available?')
                                                ->options(['Yes, On-site' => 'Yes, On-site', 'Yes, Off-site' => 'Yes, Off-site', 'No' => 'No', 'Unknown' => 'Unknown']),
                                        ]),
                                    Forms\Components\Textarea::make('describe_om_plans')->label('Specify Additional O&M Plans or describe any observed non-compliance')
                                ]),
                            Forms\Components\Section::make('Capital Expenditures')
                                ->columns(1)
                                ->description('Plans such as, but not limited to, Operations and Maintenance, Moisture Management and Environmental Remediation.')
                                ->statePath('capital_expenditures')
                                ->schema([
                                    Forms\Components\Repeater::make('Expenditure')
                                        ->columns(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('repair_description')
                                                ->label('Repair Description'),
                                            Forms\Components\TextInput::make('identified_cost')
                                                ->label('Identified Cost')->numeric()->inputMode('decimal'),
                                            Forms\Components\Select::make('status')
                                                ->options(['Completed' => 'Completed', 'In-Progress' => 'In-Progress', 'Planned' => 'Planned']),
                                        ]),
                                ]),
                            Forms\Components\Fieldset::make('Neighborhood / Site Comparison Data')
                                ->columns(3)
                                ->statePath('neighborhood_site_data')
                                ->schema([
                                    Forms\Components\Section::make('Top 2 Major Competitors')
                                        ->schema([
                                            Forms\Components\TextInput::make('name_or_type_competitor_1')
                                                ->label('Name or Type')
                                                ->required(),
                                            Forms\Components\TextInput::make('distance_competitor_1')
                                                ->label('Distance')
                                                ->required()->numeric(),
                                            Forms\Components\TextInput::make('name_or_type_competitor_2')
                                                ->label('Name or Type')
                                                ->required(),
                                            Forms\Components\TextInput::make('distance_competitor_2')
                                                ->label('Distance')
                                                ->required()->numeric(),
                                        ]),
                                    Forms\Components\Select::make('single_family_percent_use')
                                        ->label('Single Family')
                                        ->required()
                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                    Forms\Components\Select::make('multi_family_percent_use')
                                        ->label('Multifamily')
                                        ->required()
                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                    Forms\Components\Select::make('commercial_percent_use')
                                        ->label('Commerical')
                                        ->required()
                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                    Forms\Components\Select::make('industrial_percent_use')
                                        ->label('Industrial')
                                        ->required()
                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                    Forms\Components\Select::make('is_declining_area')
                                        ->label('Is the area declining or distressed?')
                                        ->required()->options(['No' => 'No', 'Yes, described below' => 'Yes, described below']),
                                    Forms\Components\Select::make('is_new_construction_in_area')
                                        ->label('Is there any new construction in area?')
                                        ->required()->options(['No' => 'No', 'Yes, described below' => 'Yes, described below']),
                                    Forms\Components\Textarea::make('area_trends_description')
                                        ->label('Describe area, visibility, access, surrounding land use & overall trends (including location in relation to subject N,S,E,W)'),
                                    Forms\Components\Textarea::make('collateral_description')
                                        ->label('Additional Collateral Description Information'),
                                ])
                        ]),
                    Forms\Components\Wizard\Step::make('Physical Condition & DM')
                        ->visible(fn($get) => in_array('2', $get('form_steps')))
                        ->schema([

                            Forms\Components\Section::make('Physical Condition Assessment and Deffered Maintenance')
                                ->statePath('physical_condition')
                                ->schema([
                                    Forms\Components\Section::make('Curb Appeal')
                                        ->columns(2)
                                        ->description('Comparsion to Neighborhood; First Impression / Appearance')
                                        ->schema([
                                            Forms\Components\Select::make('curb_appeal_rating')->label('Curb Appeal Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('curb_appeal_trend')->label('Curb Appeal Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('curb_appeal_inspector_comments')->label('Curb Appeal Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Site')
                                        ->columns(2)
                                        ->description('Inspection Appearance; Signage; Ingress / Egress; Landscaping; Site Lightning; Parking Lot; Striping; Garage; Car Ports; Irrigation System; Drainage; Retaining Walls; Walkways; Fencing; Refuse Containment & Cleanliness; Hazardous Material Storage')
                                        ->schema([
                                            Forms\Components\Select::make('site_rating')->label('Site Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('site_trend')->label('Site Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('site_inspector_comments')->label('Site Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Building / Mechanical Systems')
                                        ->columns(2)
                                        ->description('HVAC; Electrical; Boilers; Water Heaters; Fire Protection; Sprinklers; Plumbing; Sewer; Solar Systems; Elevators / Escalators; Chiller Plant; Cooling Towers; Building Oxygen; Intercom Systeml; PA System; Security Systems')
                                        ->schema([
                                            Forms\Components\Select::make('mechanical_rating')->label('Mechanical Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('mechanical_trend')->label('Mechanical Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('mechanical_inspector_comments')->label('Mechanical Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Building Exteriors')
                                        ->columns(2)
                                        ->description('Siding; Trim; Paint; Windows; Entry Ways; Stairs; Railings; Balconies; Patios; Gutters; Downspouts; Foundations; Doors; Facade; Structure (Beam/Joint)')
                                        ->schema([
                                            Forms\Components\Select::make('exterior_rating')->label('Exteriors Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('exterior_trend')->label('Exteriors Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('exterior_inspector_comments')->label('Exteriors Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Building Roofs')
                                        ->columns(2)
                                        ->description('Roof Condition; Roof Access; Top Floor Ceilings; Shingles / Membrane; Skylights; Flashing; Parapet Walls; Mansard Roofs')
                                        ->schema([
                                            Forms\Components\Select::make('roofs_rating')->label('Roofs Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('roofs_trend')->label('Roofs Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('roofs_inspector_comments')->label('Roofs Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Occupied Units/Space')
                                        ->columns(2)
                                        ->description('HVAC; Ceiling; Floors; Walls; Painting; Wall Cover; Floor Cover; Tiles; Windows; Countertop; Cabinets; Appliances; Lightning; Electrical; Bathroom Accessories; Plumbing Fixtures; Storage; Basements / Attics')
                                        ->schema([
                                            Forms\Components\Select::make('occupied_rating')->label('Occupied Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('occupied_trend')->label('Occupied Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('occupied_inspector_comments')->label('Occupied Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Vacant Units / Space / Hotel Rooms')
                                        ->columns(2)
                                        ->description('Inspection Appearance; Signage; Ingress / Egress; Landscaping; Site Lightning; Parking Lot; Striping; Garage; Car Ports; Irrigation System; Drainage; Retaining Walls; Walkways; Fencing; Refuse Containment & Cleanliness; Hazardous Material Storage')
                                        ->schema([
                                            Forms\Components\Select::make('vacant_rating')->label('Vacant Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('vacant_trend')->label('Vacant Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('vacant_inspector_comments')->label('Vacant Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Down Units / Space / Hotel Rooms')
                                        ->columns(2)
                                        ->description('Inspection Appearance; Signage; Ingress / Egress; Landscaping; Site Lightning; Parking Lot; Striping; Garage; Car Ports; Irrigation System; Drainage; Retaining Walls; Walkways; Fencing; Refuse Containment & Cleanliness; Hazardous Material Storage')
                                        ->schema([
                                            Forms\Components\Select::make('down_rating')->label('Down Units')
                                                ->required()
                                                ->options(['Yes' => 'Yes', 'No' => 'No']),
                                            Forms\Components\Select::make('down_trend')->label('Down Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('down_inspector_comments')->label('Down Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Interior Common Areas')
                                        ->columns(2)
                                        ->description('Mailboxes; Reception Area; Lobby; Food Courts; Dining Area; Kitchen; Halls; Stairways; Meeting Rooms; Public Restrooms; Storage; Basement; Healthcare Assistance Rooms; Pharmacy / Medication Storage; Nurses Station')
                                        ->schema([
                                            Forms\Components\Select::make('interior_common_rating')->label('Interior Common Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('interior_common_trend')->label('Interior Common Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('interior_common_inspector_comments')->label('Interior Common Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Amenities')
                                        ->columns(2)
                                        ->description('Pool; Clubhouse; Gym; Laundry Area / Rooms; Playground; Wireless Access; Restaurant / Bar; Business Center; Sport Courts; Spa; Store; Media Center')
                                        ->schema([
                                            Forms\Components\Select::make('amenities_rating')->label('Amenities Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('amenities_trend')->label('Amenities Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('amenities_inspector_comments')->label('Amenities Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Section::make('Environmental')
                                        ->columns(2)
                                        ->description('Reported spills or leaks; Evidence of spills or leaks; Evidence of distressed vegetation; Evidence of mold; Evidence of O&M non-compliance')
                                        ->schema([
                                            Forms\Components\Select::make('interior_rating')->label('Interior Rating')
                                                ->required()
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible']),
                                            Forms\Components\Select::make('interior_trend')->label('Interior Trend')
                                                ->required()
                                                ->options(['Imporving' => 'Imporving', 'Stable' => 'Stable', 'Declining' => 'Declining', 'Unknown' => 'Unknown']),
                                            Forms\Components\Textarea::make('interior_inspector_comments')->label('Interior Inspector Comments')
                                                ->required()
                                                ->columnSpanFull()
                                        ]),
                                    Forms\Components\Textarea::make('Exterior - Additional description of the propery condition')
                                        ->required()->columnSpanFull(),
                                    Forms\Components\Textarea::make('Interior - Additional description of the propery condition')
                                        ->required()->columnSpanFull(),
                                    Forms\Components\Section::make('Deffered Maintenance Items')
                                        ->schema([
                                            Forms\Components\Repeater::make('deferred_items')
                                                ->schema([
                                                    Forms\Components\TextInput::make('Description')
                                                        ->helperText('Identify Item and Describe Condition (including location)'),
                                                    Forms\Components\TextInput::make('Rating'),
                                                    Forms\Components\TextInput::make('Life Safety'),
                                                    Forms\Components\TextInput::make('Estimated Cost'),
                                                ])
                                        ])

                                ])
                        ]),
                    Forms\Components\Wizard\Step::make('Photos')
                        ->visible(fn($get) => in_array('3', $get('form_steps')))
                        ->schema([
                            Forms\Components\Repeater::make('images')
                                ->statePath('images')
                                ->addActionLabel('Add Photo')
                                ->reorderable()
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([
                                    Forms\Components\Select::make('photo_type')->label('Photo Type')
                                        ->options(['Exterior' => 'Exterior', 'Interior' => 'Interior', 'Roof' => 'Roof', 'Neighborhood' => 'Neighborhood', 'Routine Maintenance' => 'Routine Maintenance', 'Deferred Maintenance' => 'Deferred Maintenance', 'Life Safety' => 'Life Safety']),
                                    Forms\Components\Textarea::make('photo_description')->label('Photo Description'),
                                    Forms\Components\FileUpload::make('photo_url')->label('Photo')
                                ])
                        ]),
                    Forms\Components\Wizard\Step::make('Rent Roll')
                        ->visible(fn($get) => in_array('4', $get('form_steps')))
                        ->schema([
                            Forms\Components\Section::make('Rent Roll')
                                ->statePath('rent_roll')
                                ->schema([
                                    Forms\Components\Select::make('rent_roll_attached')
                                        ->options(['Yes' => 'Yes', 'No' => 'No'])->live(),
                                    Forms\Components\Select::make('rent_roll_missing_reason')
                                        ->options(['Hard Copy to follow' => 'Hard Copy to follow', 'Requested but not provided' => 'Requested but not provided', 'Requested but declined' => 'Requested but declined', 'Not Applicable' => 'Not Applicable'])
                                        ->disabled(fn($get) => $get('rent_roll_attached') != 'No'),
                                    Forms\Components\Select::make('rent_roll_summary_attached')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\Select::make('single_tenant_property')
                                        ->options(['Yes' => 'Yes', 'No' => 'No'])->live(),
                                    Forms\Components\TextInput::make('lease_expires')
                                        ->disabled(fn($get) => $get('single_tenant_property') != 'Yes'),
                                    Forms\Components\Select::make('hospitality_property')->live()
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\TextInput::make('ytd_adr')->label('YTD ADR')
                                        ->disabled(fn($get) => $get('hospitality_property') != 'Yes'),
                                    Forms\Components\TextInput::make('revpar')->label('RevPAR')
                                        ->disabled(fn($get) => $get('hospitality_property') != 'Yes'),
                                    Forms\Components\TextInput::make('ado')->label('ADO')
                                        ->disabled(fn($get) => $get('hospitality_property') != 'Yes'),
                                    Forms\Components\Section::make('Largest Commerical Tenants')
                                        ->columns(1)
                                        ->schema([
                                            Forms\Components\Repeater::make('tenant_info')
                                                ->columns(6)
                                                ->schema([
                                                    Forms\Components\TextInput::make('Tenant Name'),
                                                    Forms\Components\TextInput::make('Expiration'),
                                                    Forms\Components\TextInput::make('sq_ft')->label('Sq. Ft.')->default(0)->numeric()->live(onBlur: true)->afterStateUpdated(fn($set, $get, $state) => $set('rent_per_sqft', intval($get('annual_rent') / $state))),
                                                    Forms\Components\TextInput::make('NRA Percentage')->label('% NRA'),
                                                    Forms\Components\TextInput::make('annual_rent')->label('Annual Rent')->default(0)->live(onBlur: true)->numeric()->inputMode('decimal')->afterStateUpdated(fn($set, $get, $state) => $set('rent_per_sqft', intval($state / $get('sq_ft')))),
                                                    Forms\Components\TextInput::make('rent_per_sqft')->label('Rent / Sq. Ft.')->readOnly()
                                                ])
                                        ])
                                ]),
                        ]),
                    Forms\Components\Wizard\Step::make('Management Interview')
                        ->statePath('mgmt_interview')
                        ->visible(fn($get) => in_array('5', $get('form_steps')))
                        ->schema([
                            Forms\Components\Section::make('Management Information & Interview')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('management_company_name')
                                        ->label('Management Company Name'),
                                    Forms\Components\TextInput::make('name_information_source')
                                        ->label('Name of Information Source'),
                                    Forms\Components\TextInput::make('role_title_information_source')
                                        ->label('Role or Title of Information Source'),
                                    Forms\Components\TextInput::make('management_affiliation')
                                        ->label('Management Affiliation'),
                                    Forms\Components\TextInput::make('phone_number')
                                        ->label('Phone Number'),
                                    Forms\Components\TextInput::make('email_address')
                                        ->label('Email Address'),
                                    Forms\Components\Select::make('length_at_property')
                                        ->label('Length of time at property')
                                        ->options([
                                            '< 6 mo' => '< 6 mo',
                                            '6 mo to < 1 yr' => '6 mo to < 1 yr',
                                            '1 yr to < 3 yr' => '1 yr to < 3 yr',
                                            '3 yr to < 5 yr' => '3 yr to < 5 yr',
                                            '5 yr or more' => '5 yr or more',
                                        ]),
                                    Forms\Components\Select::make('mgmt_change_last_inspection')
                                        ->label('Mgmt change from last inspection')
                                        ->required()
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No'
                                        ]),
                                ]),
                            Forms\Components\Section::make('Neighborhood and Rental Market')
                                ->schema([
                                    Forms\Components\Select::make('property_performance_question')
                                        ->label('In your opinion, how does the property perform compared to similar properties in the area?')
                                        ->options([
                                            'Superior' => 'Superior',
                                            'Average' => 'Average',
                                            'Below Average' => 'Below Average',
                                        ]),
                                    Forms\Components\TextInput::make('average_vacancy_percentage')
                                        ->label('In your opinion, what is the average percentage of vacancy in similar properties in the area?'),
                                    Forms\Components\TextInput::make('average_rent_current')
                                        ->label('What is the current average rent paid in the area ($ per square foot/units/beds)?'),
                                    Forms\Components\Select::make('vacancy_comparison_last_year')
                                        ->label('How does the current vacancy compare to last year at this time?')
                                        ->options([
                                            'Similar' => 'Similar',
                                            'Increased' => 'Increased',
                                            'Decreased' => 'Decreased',
                                            'Unknown' => 'Unknown',
                                        ]),
                                    Forms\Components\Textarea::make('vacany_variance_explanation')
                                        ->label('In your opinion, explain the reason for any variance on vacancy, and rents between the market and the subject property:'),
                                    Forms\Components\Toggle::make('major_change_area')->live()
                                        ->label('Any change to a major employer in the area, or major commercial/retail operation in the area?'),
                                    Forms\Components\Textarea::make('major_change_area_description')
                                        ->label('If yes, describe:')->visible(fn($get) => $get('major_change_area')),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('Amount of the last rental increase')
                                                ->label('Amount of the last rental increase'),
                                            Forms\Components\DatePicker::make('Date of last rental increase')
                                                ->label('Date of last rental increase'),
                                            Forms\Components\TextInput::make('Number of Administration Employees')
                                                ->label('Number of Administration Employees')->numeric(),
                                            Forms\Components\TextInput::make('Number of Maintenance Employees')
                                                ->label('Number of Maintenance Employees')->numeric(),
                                            Forms\Components\Select::make('Heat at the Property')
                                                ->label('Heat at the Property')
                                                ->options([
                                                    'Paid by Tenant' => 'Paid by Tenant',
                                                    'Paid by Owner' => 'Paid by Owner',
                                                ]),
                                            Forms\Components\Select::make('Water at the Property')
                                                ->label('Water at the Property')
                                                ->options([
                                                    'Paid by Tenant' => 'Paid by Tenant',
                                                    'Paid by Owner' => 'Paid by Owner',
                                                ]),
                                            Forms\Components\Select::make('Electric at the Property')
                                                ->label('Electric at the Property')
                                                ->options([
                                                    'Paid by Tenant' => 'Paid by Tenant',
                                                    'Paid by Owner' => 'Paid by Owner',
                                                ]),
                                            Forms\Components\Select::make('Gas at the Property')
                                                ->label('Gas at the Property')
                                                ->options([
                                                    'Paid by Tenant' => 'Paid by Tenant',
                                                    'Paid by Owner' => 'Paid by Owner',
                                                ]),
                                            Forms\Components\Select::make('Trash at the Property')
                                                ->label('Trash at the Property')
                                                ->options([
                                                    'Paid by Tenant' => 'Paid by Tenant',
                                                    'Paid by Owner' => 'Paid by Owner',
                                                ]),
                                            Forms\Components\Select::make('Cable at the Property')
                                                ->label('Cable at the Property')
                                                ->options([
                                                    'Paid by Tenant' => 'Paid by Tenant',
                                                    'Paid by Owner' => 'Paid by Owner',
                                                ]),
                                            Forms\Components\Section::make('Tenant Profile')
                                                ->columns(3)
                                                ->statePath('tenant_profile')
                                                ->schema([
                                                    Forms\Components\Select::make('Corporate')
                                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                                    Forms\Components\Select::make('Military')
                                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                                    Forms\Components\Select::make('Seasonal')
                                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                                    Forms\Components\Select::make('Seniors')
                                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                                    Forms\Components\Select::make('Students')
                                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                                    Forms\Components\Select::make('Other')
                                                        ->options(['5%' => '5%', '10%' => '10%', '15%' => '15%', '20%' => '20%', '25%' => '25%', '30%' => '30%', '35%' => '35%', '40%' => '40%', '45%' => '45%', '50%' => '50%', '55%' => '55%', '60%' => '60%', '65%' => '65%', '70%' => '70%', '75%' => '75%', '80%' => '80%', '85%' => '85%', '90%' => '90%', '95%' => '95%', '100%' => '100%']),
                                                ])
                                        ])
                                ]),
                            Forms\Components\Section::make('Property Events')
                                ->statePath('property_events')
                                ->schema([
                                    Forms\Components\Select::make('key_employee_replaced')
                                        ->label('In the past 12 months, has there been any key employee turnover or any key employee replaced?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Select::make('significant_propoerty_damage')
                                        ->label('In the past 12 months, have there been any fires, significant water intrusion or other property damage?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Textarea::make('significant_propoerty_damage_explanation')
                                        ->label('If yes, explain the location on the property, costs associated, any insurance claims submitted, resolution and leaseability:'),
                                    Forms\Components\Select::make('code_violation_received')
                                        ->label('In the past 12 months, to the best of your knowledge, have any code violations been received?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Textarea::make('code_violation_explanation')
                                        ->label('If yes, please describe the violation, the costs associated, and any resolution or outstanding issues:'),
                                    Forms\Components\Select::make('significant_rehab_construction')
                                        ->label('Is the property undergoing any significant rehab/construction?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Textarea::make('significant_rehab_construction_explanation')
                                        ->label('If yes, explain the location, size and estimated costs:'),
                                    Forms\Components\Select::make('franchise_agreement_change')
                                        ->label('Any change or violations of a Franchise Agreement or License(s)?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Textarea::make('franchise_agreement_change_explanation')
                                        ->label('If yes, please explain any change or violation, costs and any resolution or outstanding issues:'),
                                    Forms\Components\Select::make('lawsuits_pending')
                                        ->label('To the best of your knowledge, are there any lawsuits pending that may negatively impact the property?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Textarea::make('lawsuits_pending_explanation')
                                        ->label('If yes, please explain:'),
                                    Forms\Components\Select::make('special_assessments')
                                        ->label('If a Co-op, has the corporation had the need to use special assessments to cover expenses?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Textarea::make('special_assessments_explanation')
                                        ->label('If yes, please explain:'),
                                    Forms\Components\Select::make('short_term_leases')
                                        ->label('Are there units or corporate leases for the purposes of home sharing (home sharing can be defined as
short-term (<1 month) rentals generally marketed through an online platform such as Airbnb)?')
                                        ->options([
                                            'Yes' => 'Yes',
                                            'No' => 'No',
                                            'Unknown' => 'Unknown'
                                        ]),
                                    Forms\Components\Textarea::make('short_term_leases_explanatino')
                                        ->label('If yes, please explain:'),
                                    Forms\Components\Textarea::make('management_evaluation_comments')
                                        ->label('Other Information and Management Evaluation Comments:')


                                ])

                        ]),
                    Forms\Components\Wizard\Step::make('Multifamily')
                        ->statePath('multifamily')
                        ->visible(fn($get) => in_array('6', $get('form_steps')))
                        ->schema([
                            Forms\Components\Section::make('Multifamily, Mobile Homes, Cooperative Housing, Student Housing')
                                ->schema([
                                    Forms\Components\Select::make('any_commercial_units')
                                        ->label('Any Commercial Units?')
                                        ->options(['Yes' => 'Yes', 'No' => 'No', 'Unknown' => 'Unknown']),
                                    Forms\Components\TextInput::make('num_commercial_units')
                                        ->label('If yes, how many?')
                                        ->numeric(),
                                    Forms\Components\TextInput::make('commercial_units_inspected')
                                        ->label('Number Commercial units Inspected:')
                                        ->numeric(),
                                ]),
                            Forms\Components\Section::make('Multifamily Unit Breakdown')
                                ->statePath('multifamily_unit_breakdown')
                                ->schema([
                                    Forms\Components\Repeater::make('unit_info')
                                        ->columns(10)
                                        ->schema([
                                            Forms\Components\TextInput::make('bedrooms')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('baths')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('num_of_units')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('avg_sqft_unit')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('avg_rent')
                                                ->numeric()->inputMode('decimal'),
                                            Forms\Components\TextInput::make('occupied')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('non-revenue')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('vacant')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('down')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('inspected')
                                                ->numeric(),
                                        ])
                                ]),
                            Forms\Components\Section::make('Detailed Report of Units Inspected')
                                ->statePath('unit_detail_report')
                                ->schema([
                                    Forms\Components\Repeater::make('unit_detail')
                                        ->columns(7)
                                        ->schema([
                                            Forms\Components\TextInput::make('unit_no'),
                                            Forms\Components\TextInput::make('bedrooms')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('baths')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('square_feet')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('asking_rent')
                                                ->numeric()->inputMode('decimal'),
                                            Forms\Components\TextInput::make('current_use'),
                                            Forms\Components\TextInput::make('overall_condition')
                                        ]),
                                ]),
                            Forms\Components\Textarea::make('general_comments')
                                ->label('General Comments')->columnSpanFull(),
                        ]),
                    Forms\Components\Wizard\Step::make('Fannie Mae Assmt Addendum')
                        ->statePath('fannie_mae_assmt')
                        ->visible(fn($get) => in_array('7', $get('form_steps')))
                        ->schema([
                            Forms\Components\Section::make('Limitations of Field Assessment')
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
                                    Forms\Components\Textarea::make('limitation_comment')->label('Limitation Comment')->columnSpanFull()
                                ]),
                            Forms\Components\Section::make('Comprehensive Property Assessment Ratings')
                                ->statePath('property_assessment_ratings')
                                ->schema([
                                    Forms\Components\Select::make('life_safety')->label('Life Safety')
                                        ->options([
                                            '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                            '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                            '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                            '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                            '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                        ]),
                                    Forms\Components\Textarea::make('life_safety_comments')->columnSpanFull(),
                                    Forms\Components\Select::make('deffered_maintenance')->label('Deffered Maintenance')
                                        ->options([
                                            '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                            '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                            '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                            '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                            '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                        ]),
                                    Forms\Components\Textarea::make('deffered_maintenance_comments')->columnSpanFull(),
                                    Forms\Components\Select::make('routine_maintenance')->label('Routine Maintenance')
                                        ->options([
                                            '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                            '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                            '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                            '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                            '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                        ]),
                                    Forms\Components\Textarea::make('routine_maintenance_comments')->columnSpanFull(),
                                    Forms\Components\Select::make('capital_needs')->label('Capital Needs')
                                        ->options([
                                            '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                            '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                            '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                            '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                            '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                        ]),
                                    Forms\Components\Textarea::make('capital_needs_comments')->columnSpanFull(),
                                    Forms\Components\Select::make('volume_of_issues_noted')->label('Level/Volume of issues noted and appropriate follow-up recommendations')
                                        ->options([
                                            '1. No Life Safety issues observed' => '1. No Life Safety issues observed',
                                            '2. No/minor Life Safety issues observed' => '2. No/minor Life Safety issues observed',
                                            '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure' => '3. Some Life Safety issues observed requiring immediate attention; but no capital expenditure',
                                            '4. Life Safety issues observed that require immediate attention and possible capital expenditure' => '4. Life Safety issues observed that require immediate attention and possible capital expenditure',
                                            '5. Significant Life Safety issues requiring capital expenditure' => '5. Significant Life Safety issues requiring capital expenditure'
                                        ]),
                                    Forms\Components\Textarea::make('volume_of_issues_noted_comments')->columnSpanFull(),
                                    Forms\Components\Select::make('overall_property_rating')->label('Overall Property Ratings')
                                        ->options([
                                            '1' => '1',
                                            '2' => '2',
                                            '3' => '3',
                                            '4' => '4',
                                            '5' => '5'
                                        ]),
                                    Forms\Components\Textarea::make('overall_property_rating_comments')->columnSpanFull(),
                                    Forms\Components\Section::make('Seller/Servicer Certification')
                                        ->statePath('seller_servicer_certification')
                                        ->columns(3)
                                        ->schema([
                                            Forms\Components\DatePicker::make('date'),
                                            Forms\Components\TextInput::make('first_name')->label('First Name'),
                                            Forms\Components\TextInput::make('last_name')->label('Last Name'),
                                            Forms\Components\TextInput::make('title')->label('Title'),
                                            Forms\Components\TextInput::make('phone_number')->label('Phone Number'),
                                            Forms\Components\TextInput::make('email_address')->label('Email Address'),
                                        ])

                                ])
                        ]),
                    Forms\Components\Wizard\Step::make('FRE Assmt Addendum')
                        ->statePath('fre_assmt')
                        ->visible(fn($get) => in_array('8', $get('form_steps')))
                        ->schema([
                            Forms\Components\Section::make('Physical Inspection Additional Questions')
                                ->statePath('physical_assmt_add_questions')
                                ->schema([
                                    Forms\Components\Select::make('deferred_maintenance_outstanding')
                                        ->label('Are any deferred maintenance items outstanding from the last inspection?')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\Textarea::make('deferred_maintenance_detail')
                                        ->label('If Yes, please specify items that remain outstanding and include impact of outstanding items on overall property appeal and condition'),
                                    Forms\Components\Select::make('harmful_environment_condition')
                                        ->label('Was a harmful environmental condition observed which is not covered by an existing O&M plan (such as mold)?')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\Textarea::make('harmful_environment_detail')
                                        ->label('If Yes, please discuss below'),
                                    Forms\Components\Select::make('out_of_compliance_ada')
                                        ->label('Is the property out of compliance with any applicable ADA requirements?')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\Textarea::make('out_of_compliance_detail')
                                        ->label('If Yes, please discuss below'),

                                ]),
                        ]),
                    Forms\Components\Wizard\Step::make('Repairs Verification')
                        ->statePath('repairs_verification')
                        ->visible(fn($get) => in_array('9', $get('form_steps')))
                        ->schema([
                            Forms\Components\Section::make('Property Information')
                                ->statePath('property_info')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('name'),
                                    Forms\Components\TextInput::make('address'),
                                    Forms\Components\TextInput::make('address_2'),
                                    Forms\Components\TextInput::make('city'),
                                    Forms\Components\TextInput::make('state_id')
                                        ->label('State')
                                        ->numeric(),
                                    Forms\Components\TextInput::make('zip'),
                                    Forms\Components\TextInput::make('country'),
                                ]),
                            Forms\Components\Section::make('Inspection Scheduling Contact Info')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('contact_company')
                                        ->label('Contact Company')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('contact_name')
                                        ->label('Contact Name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('contact_phone')
                                        ->label('Contact Phone')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('contact_email')
                                        ->label('Contact Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspection_company')
                                        ->label('Inspection Company')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspector_name')
                                        ->label("Inspector's Name")
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspector_company_phone')
                                        ->label("Inspection Co. Phone")
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('inspector_id')
                                        ->label("Inspector's ID")
                                        ->required()
                                        ->maxLength(255),
                                ]),
                            Forms\Components\Section::make('Servicer Info')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('servicer_name')->label('Servicer Name'),
                                    Forms\Components\TextInput::make('loan_number')->label('Loan Number'),
                                    Forms\Components\Select::make('primary_type')
                                        ->label('Primary Property Type')
                                        ->required()
                                        ->options(['Health Care' => 'Health Care', 'Industrial' => 'Industrial', 'Lodging' => 'Lodging', 'Multifamily' => 'Multifamily', 'Mobile Home Park' => 'Mobile Home Park', 'Mixed Use' => 'Mixed Use', 'Office' => 'Office', 'Other' => 'Other', 'Retail' => 'Retail', 'Self Storage' => 'Self Storage']),
                                ]),
                            Forms\Components\Section::make('Completion Details')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('expected_percentage_complete')->label('Expected percentage completed')
                                        ->numeric()->suffix('%')->maxValue(100),
                                    Forms\Components\TextInput::make('overall_observed_percentage_complete')->label('Overall observed percentage completed')
                                        ->numeric()->suffix('%')->maxValue(100),
                                ]),
                            Forms\Components\Section::make('Repairs Verification')
                                ->schema([
                                    Forms\Components\Textarea::make('general_summary_comments')
                                        ->label('General description of improvements and summary comments'),
                                    Forms\Components\Repeater::make('verification_list')
                                        ->columns(4)
                                        ->schema([
                                            Forms\Components\Textarea::make('item_description'),
                                            Forms\Components\Textarea::make('inspector_comments'),
                                            Forms\Components\FileUpload::make('photo'),
                                            Forms\Components\Select::make('repair_status')
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
                        ]),
                    Forms\Components\Wizard\Step::make('Senior Supplement')
                        ->statePath('senior_supplement')
                        ->visible(fn($get) => in_array('10', $get('form_steps')))
                        ->schema([
                        ]),
                    Forms\Components\Wizard\Step::make('Hospitals')
                        ->statePath('hospitals')
                        ->visible(fn($get) => in_array('11', $get('form_steps')))
                        ->schema([
                            Forms\Components\Section::make('General Property Info')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('new_patients_accepted')
                                        ->label('New Patients Currently being Accepted')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\Select::make('admission_waiting_period')
                                        ->label('Admission Waiting Period')
                                        ->options([
                                            'Yes, 1-15 Days' => 'Yes, 1-15 Days',
                                            'Yes, 16-30 Days' => 'Yes, 16-30 Days',
                                            'Yes, 31-60 Days' => 'Yes, 31-60 Days',
                                            'Yes, 61-120 Days' => 'Yes, 61-120 Days',
                                            'Yes, 121+ Days' => 'Yes, 121+ Days',
                                            'No Waiting Period' => 'No Waiting Period'
                                        ]),
                                    Forms\Components\Select::make('proximity_to_hospital')
                                        ->label('Proximity to a Hospital')
                                        ->options([
                                            'On site' => 'On site',
                                            'Less than 1 mile' => 'Less than 1 mile',
                                            '1 to < 5 miles' => '1 to < 5 miles',
                                            '5 to <10 miles' => '5 to <10 miles',
                                            '10 or more miles' => '10 or more miles',
                                        ])
                                ]),
                            Forms\Components\Section::make('Level of Care Breakdown')
                                ->statePath('level_of_care_breakdown')
                                ->schema([
                                    Forms\Components\Repeater::make('unit_info')
                                        ->columns(7)
                                        ->schema([
                                            Forms\Components\Select::make('unit_type')
                                                ->label('Unit Type')->options([
                                                    'Assisted Living/Congregate Care' => 'Assisted Living/Congregate Care',
                                                    'Hospital' => 'Hospital',
                                                    'Nursing Home, Unskilled' => 'Nursing Home, Unskilled',
                                                    'Nursing Home, Skilled' => 'Nursing Home, Skilled',
                                                    'Specialty Health Care' => 'Specialty Health Care'
                                                ]),
                                            Forms\Components\TextInput::make('total_beds')->label('Total Beds')->numeric(),
                                            Forms\Components\TextInput::make('occupied_beds')->label('Total Beds Occupied')->numeric(),
                                            Forms\Components\TextInput::make('total_units')->label('Total Units')->numeric(),
                                            Forms\Components\TextInput::make('occupied_units')->label('Total Units Occupied')->numeric(),
                                            Forms\Components\TextInput::make('average_sq_feet_unit')->label('Sq. Feet / Unit')->numeric()->inputMode('decimal'),
                                            Forms\Components\TextInput::make('monthly_rent')->label('Monthly Rent')->numeric()->inputMode('decimal'),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('administrator_name')->label("Administrator's Name"),
                                            Forms\Components\Select::make('administrator_length_at_property')->label('Length of Time at Property')
                                                ->options([
                                                    '< 6 mos' => '< 6 mos',
                                                    '6 m to < 1 yr' => '6 m to < 1 yr',
                                                    '1 to < 3 yrs' => '1 to < 3 yrs',
                                                    '3 to < 5 yrs' => '3 to < 5 yrs',
                                                    '5 yrs or longer' => '5 yrs or longer'
                                                ]),
                                            Forms\Components\TextInput::make('director_nursing_name')->label("Director of Nursing's Name"),
                                            Forms\Components\Select::make('director_nursing_length_at_property')->label('Length of Time at Property')
                                                ->options([
                                                    '< 6 mos' => '< 6 mos',
                                                    '6 m to < 1 yr' => '6 m to < 1 yr',
                                                    '1 to < 3 yrs' => '1 to < 3 yrs',
                                                    '3 to < 5 yrs' => '3 to < 5 yrs',
                                                    '5 yrs or longer' => '5 yrs or longer'
                                                ]),
                                        ]),
                                    Forms\Components\Section::make('Direct Care Staff Numbers')
                                        ->statePath('direct_care_staff_numbers')
                                        ->columns(7)
                                        ->schema([
                                            Forms\Components\Placeholder::make('nurses_rns')->label('Nurses RNs'),
                                            Forms\Components\TextInput::make('nurses_rns_1')->label(''),
                                            Forms\Components\TextInput::make('nurses_rns_2')->label(''),
                                            Forms\Components\TextInput::make('nurses_rns_3')->label(''),
                                            Forms\Components\TextInput::make('nurses_rns_comments')->label('')->helperText('Comments')->columnSpan(3),
                                            Forms\Components\Placeholder::make('nurses_lpns')->label('Nurses LPNs'),
                                            Forms\Components\TextInput::make('nurses_lpns_1')->label(''),
                                            Forms\Components\TextInput::make('nurses_lpns_2')->label(''),
                                            Forms\Components\TextInput::make('nurses_lpns_3')->label(''),
                                            Forms\Components\TextInput::make('nurses_lpns_comments')->label('')->helperText('Comments')->columnSpan(3),
                                            Forms\Components\Placeholder::make('other_direct_care')->label('Other Direct Care'),
                                            Forms\Components\TextInput::make('other_direct_care_1')->label(''),
                                            Forms\Components\TextInput::make('other_direct_care_2')->label(''),
                                            Forms\Components\TextInput::make('other_direct_care_3')->label(''),
                                            Forms\Components\TextInput::make('other_direct_care_comments')->label('')->helperText('Comments')->columnSpan(3),
                                            Forms\Components\Placeholder::make('non_direct_care')->label('Non Direct Care Personnel'),
                                            Forms\Components\TextInput::make('non_direct_care_1')->label(''),
                                            Forms\Components\TextInput::make('non_direct_care_2')->label(''),
                                            Forms\Components\TextInput::make('non_direct_care_3')->label(''),
                                            Forms\Components\TextInput::make('non_direct_care_comments')->label('')->helperText('Comments')->columnSpan(3),
                                        ]),
                                ]),
                            Forms\Components\Section::make('Regulatory / Licensing Agency Information')
                                ->statePath('regulatory_agency_information')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('name_of_agency')->label('Name of Agency'),
                                    Forms\Components\TextInput::make('contact_person')->label('Contact Person'),
                                    Forms\Components\DatePicker::make('expiration_date_license')->label('Expiration Date of Operating License'),
                                    Forms\Components\Select::make('all_licenses_current')->label('All Licenses Current')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\DatePicker::make('date_medicare_inspection')->label('Date of last Medicare inspection'),
                                    Forms\Components\Select::make('medicare_certified')->label('Property Medicare Certified')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\DatePicker::make('date_medicaid_inspection')->label('Date of last Medicaid inspection'),
                                    Forms\Components\Select::make('medicaid_certified')->label('Property Medicaid Certified')
                                        ->options(['Yes' => 'Yes', 'No' => 'No']),
                                    Forms\Components\Textarea::make('violations_description')->label('Please describe any violations, costs associated, resolution or outstanding issues')->columnSpanFull(),
                                ]),
                            Forms\Components\Section::make('Property Condition')
                                ->statePath('property_condition')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('handrails_in_halls')->label('Handrails in the halls')
                                        ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                                    Forms\Components\Select::make('grab_bars_present')->label('Grab bars present in rest rooms')
                                        ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                                    Forms\Components\Select::make('exits_marked')->label('Exits clearly marked')
                                        ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                                    Forms\Components\Select::make('staff_interacts_well')->label('Staff interacts well with residents')
                                        ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                                    Forms\Components\Select::make('intercom_system')->label('Intercom System')
                                        ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                                    Forms\Components\Select::make('looks_smells_clean')->label('Facility looks and smells clean')
                                        ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                                    Forms\Components\Select::make('generator_function')->label('Generator Function')
                                        ->options(['Yes' => 'Yes', 'No, Describe Below' => 'No, Describe Below']),
                                    Forms\Components\Textarea::make('additional_condition_description')->label('Additional description of any safety or deficiency issues observed')->columnSpanFull(),
                                    Forms\Components\TextInput::make('down_units_numbers')->label('Down Units (List the unit #)')->inlineLabel()->columnSpanFull()
                                ]),
                            Forms\Components\Section::make('Detailed Report of Units Inspected')
                                ->statePath('detailed_report_of_units_inspected')
                                ->schema([
                                    Forms\Components\Repeater::make('unit_inspection_detail')
                                        ->columns(7)
                                        ->schema([
                                            Forms\Components\TextInput::make('unit_number')->label('Unit Number'),
                                            Forms\Components\TextInput::make('bedrooms')->label('Bedrooms'),
                                            Forms\Components\TextInput::make('baths')->label('Baths'),
                                            Forms\Components\TextInput::make('sq_feet')->label('Square Feet'),
                                            Forms\Components\TextInput::make('asking_rent')->label('Asking Rent'),
                                            Forms\Components\Select::make('current_use')->label('Current Use')
                                                ->options(['Occupied Unfurnished' => 'Occupied Unfurnished', 'Occupied Furnished' => 'Occupied Furnished', 'Down Unit' => 'Down Unit', 'Vacant Unfurnished, Ready' => 'Vacant Unfurnished, Ready', 'Vacant Unfurnished' => 'Vacant Unfurnished', 'Vacant Furnished, Ready' => 'Vacant Furnished, Ready', 'Vacant Furnished' => 'Vacant Furnished', 'Non-Revenue' => 'Non-Revenue', 'Commercial Unit' => 'Commercial Unit']),
                                            Forms\Components\Select::make('overall_condition')->label('Overall Condition')
                                                ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'Not Applicable' => 'Not Applicable', 'Not Accessible' => 'Not Accessible', 'Not Inspected' => 'Not Inspected']),
                                        ])
                                ])


                        ]),
                ])
                    ->columns(3)
        ->skippable()
        ->submitAction(new HtmlString(Blade::render(<<<BLADE
    <x-filament::button type="submit" size="sm">Submit</x-filament::button>
BLADE
        ))),]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_2')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('state_id')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zip')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('overall_rating')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_scale')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('inspection_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('primary_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('secondary_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.servicer_name')
                    ->label('Servicer Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('servicer_loan_info.loan_number')
                    ->label('Loan Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('servicer_loan_info.property_id')
                    ->label('InspectionCollection ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.servicer_inspection_id')
                    ->label('Servicer Inspection ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.original_loan_amount')
                    ->label('Original Loan Amount')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.loan_balance')
                    ->label('Loan Balance')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.loan_balance_date')
                    ->label('Loan Balance Date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.loan_owner')
                    ->label('Loan Owner')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.investor_number')
                    ->label('Investor Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.investor_loan_number')
                    ->label('Investor Loan Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.asset_manager_name')
                    ->label('Asset Manager Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.asset_manager_phone')
                    ->label('Asset Manager Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.asset_manager_email')
                    ->label('Asset Manager Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servicer_loan_info.report_reviewed_by')
                    ->label('Report Reviewed by')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.contact_company')
                    ->label('Contact Company')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.contact_name')
                    ->label('Contact Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.contact_phone')
                    ->label('Contact Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.contact_email')
                    ->label('Contact Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.inspection_company')
                    ->label('Inspection Company')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.inspector_name')
                    ->label('Inspector Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.inspector_company_phone')
                    ->label('Inspector Company Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contact_inspector_info.inspector_id')
                    ->label('Inspector ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
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
}
