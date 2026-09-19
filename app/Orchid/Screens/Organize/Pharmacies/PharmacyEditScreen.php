<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Pharmacies;

use App\Models\Organization;
use App\Models\Pharmacy;
use App\Models\PharmacyStaff;
use App\Models\User;
use App\Orchid\Layouts\Organize\Pharmacies\PharmacyStaffTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PharmacyEditScreen extends Screen
{
    public ?Pharmacy $pharmacy = null;

    public function query(Pharmacy $pharmacy): iterable
    {
        return [
            'pharmacy' => $pharmacy,
            'staff' => $pharmacy->exists
                ? PharmacyStaff::with(['user'])->where('pharmacy_id', $pharmacy->id)->orderByDesc('id')->paginate(10)
                : PharmacyStaff::whereRaw('1 = 0')->paginate(10),
        ];
    }

    public function name(): ?string
    {
        return $this->pharmacy && $this->pharmacy->exists
            ? __('pharmacies.edit_title')
            : __('pharmacies.create_title');
    }

    public function description(): ?string
    {
        return __('pharmacies.form_description');
    }

    public function permission(): ?iterable
    {
        return ['view_pharmacy'];
    }

    public function commandBar(): iterable
    {
        $exists = (bool) ($this->pharmacy && $this->pharmacy->exists);

        return [
            Link::make(__('pharmacies.back'))
                ->icon('bs.arrow-left')
                ->route('platform.pharmacies'),

            Button::make(__('pharmacies.remove'))
                ->icon('bs.trash3')
                ->confirm(__('pharmacies.removed'))
                ->method('remove')
                ->canSee($exists),

            ModalToggle::make(__('pharmacies.staff_attach'))
                ->modal('staffModal')
                ->method('attachStaff')
                ->icon('bs.plus-circle')
                ->canSee($exists),

            Button::make(__('pharmacies.save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        $exists = (bool) ($this->pharmacy && $this->pharmacy->exists);
        $orgOptions = Organization::orderBy('name')->limit(200)->pluck('name', 'id')->toArray();
        $userOptions = User::orderBy('name')->limit(200)->pluck('name', 'id')->toArray();
        $verifierOptions = User::orderBy('name')->limit(200)->pluck('name', 'id')->toArray();

        // Only super-admins may toggle verification (the flag gates dispensing flows).
        $canVerify = (bool) request()->user()?->isSuperAdmin();

        $infoTab = Layout::rows([
            Select::make('pharmacy.organization_id')
                ->title(__('pharmacies.organization'))
                ->options($orgOptions)
                ->empty()
                ->required(),

            Input::make('pharmacy.name')
                ->title(__('pharmacies.name'))
                ->required()
                ->maxlength(255),

            Input::make('pharmacy.slug')
                ->title(__('pharmacies.slug'))
                ->maxlength(255),

            Input::make('pharmacy.registration_number')
                ->title(__('pharmacies.registration_number'))
                ->maxlength(100),

            Input::make('pharmacy.license_number')
                ->title(__('pharmacies.license_number'))
                ->maxlength(100),

            Input::make('pharmacy.phone')
                ->title(__('pharmacies.phone'))
                ->maxlength(50),

            Input::make('pharmacy.email')
                ->type('email')
                ->title(__('pharmacies.email'))
                ->maxlength(255),

            Input::make('pharmacy.address_line1')
                ->title(__('pharmacies.address_line1'))
                ->maxlength(255),

            Input::make('pharmacy.city')
                ->title(__('pharmacies.city'))
                ->maxlength(100),

            Input::make('pharmacy.county')
                ->title(__('pharmacies.county'))
                ->maxlength(100),

            Input::make('pharmacy.postal_code')
                ->title(__('pharmacies.postal_code'))
                ->maxlength(20),

            Switcher::make('pharmacy.is_verified')
                ->title(__('pharmacies.is_verified'))
                ->sendTrueOrFalse()
                ->canSee($canVerify),

            DateTimer::make('pharmacy.verification_date')
                ->title(__('pharmacies.verification_date'))
                ->format('Y-m-d')
                ->allowInput()
                ->canSee($canVerify),

            Select::make('pharmacy.verified_by')
                ->title(__('pharmacies.verified_by'))
                ->options($verifierOptions)
                ->empty()
                ->canSee($canVerify),
        ]);

        if (! $exists) {
            return [$infoTab];
        }

        return [
            Layout::tabs([
                __('pharmacies.tab_info') => [$infoTab],
                __('pharmacies.tab_staff') => [PharmacyStaffTable::class],
            ]),

            Layout::modal('staffModal', Layout::rows([
                Select::make('staff.user_id')
                    ->title(__('pharmacies.staff_user'))
                    ->options($userOptions)
                    ->empty()
                    ->required(),
                Input::make('staff.full_name')
                    ->title(__('pharmacies.staff_full_name'))
                    ->maxlength(255),
                Select::make('staff.role')
                    ->title(__('pharmacies.staff_role'))
                    ->options([
                        'pharmacist' => __('pharmacies.role_pharmacist'),
                        'assistant' => __('pharmacies.role_assistant'),
                        'manager' => __('pharmacies.role_manager'),
                        'cashier' => __('pharmacies.role_cashier'),
                    ])
                    ->empty(),
                Input::make('staff.license_number')
                    ->title(__('pharmacies.staff_license'))
                    ->maxlength(100),
                DateTimer::make('staff.license_expiry')
                    ->title(__('pharmacies.staff_license_expiry'))
                    ->format('Y-m-d')
                    ->allowInput(),
                Select::make('staff.status')
                    ->title(__('pharmacies.staff_status'))
                    ->options([
                        'active' => __('pharmacies.status_active'),
                        'inactive' => __('pharmacies.status_inactive'),
                        'suspended' => __('pharmacies.status_suspended'),
                    ])
                    ->empty(),
            ]))->title(__('pharmacies.staff_attach'))->applyButton(__('pharmacies.apply')),
        ];
    }

    public function save(Request $request, Pharmacy $pharmacy): RedirectResponse
    {
        $canVerify = (bool) $request->user()?->isSuperAdmin();

        $rules = [
            'pharmacy.organization_id' => 'required|integer|exists:organizations,id',
            'pharmacy.name' => 'required|string|max:255',
            'pharmacy.slug' => ['nullable', 'string', 'max:255', Rule::unique('pharmacies', 'slug')->ignore($pharmacy->getKey())],
            'pharmacy.registration_number' => 'nullable|string|max:100',
            'pharmacy.license_number' => 'nullable|string|max:100',
            'pharmacy.phone' => 'nullable|string|max:50',
            'pharmacy.email' => 'nullable|email|max:255',
            'pharmacy.address_line1' => 'nullable|string|max:255',
            'pharmacy.city' => 'nullable|string|max:100',
            'pharmacy.county' => 'nullable|string|max:100',
            'pharmacy.postal_code' => 'nullable|string|max:20',
        ];

        if ($canVerify) {
            $rules['pharmacy.is_verified'] = 'nullable|boolean';
            $rules['pharmacy.verification_date'] = 'nullable|date';
            $rules['pharmacy.verified_by'] = 'nullable|integer|exists:users,id';
        }

        $data = $request->validate($rules)['pharmacy'];

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['name'] ?? 'pharmacy')) ?: null;
        }

        // Pharmacy is $guarded=['id'] only, so plain fill() covers every column.
        $pharmacy->fill($data)->save();

        Toast::info(__('pharmacies.saved'));

        return redirect()->route('platform.pharmacies');
    }

    public function remove(Pharmacy $pharmacy): RedirectResponse
    {
        $pharmacy->delete();

        Toast::info(__('pharmacies.removed'));

        return redirect()->route('platform.pharmacies');
    }

    public function attachStaff(Request $request, Pharmacy $pharmacy): void
    {
        $data = $request->validate([
            'staff.user_id' => 'required|integer|exists:users,id',
            'staff.full_name' => 'nullable|string|max:255',
            'staff.role' => 'nullable|string|max:50',
            'staff.license_number' => 'nullable|string|max:100',
            'staff.license_expiry' => 'nullable|date',
            'staff.status' => 'nullable|string|max:50',
        ])['staff'];

        PharmacyStaff::create([
            'pharmacy_id' => $pharmacy->id,
            'user_id' => $data['user_id'],
            'full_name' => $data['full_name'] ?? User::whereKey($data['user_id'])->value('name'),
            'role' => $data['role'] ?? 'pharmacist',
            'license_number' => $data['license_number'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        Toast::info(__('pharmacies.staff_attached'));
    }

    public function detachStaff(Request $request, Pharmacy $pharmacy): void
    {
        PharmacyStaff::where('pharmacy_id', $pharmacy->id)->findOrFail($request->integer('id'))->delete();

        Toast::info(__('pharmacies.staff_detached'));
    }
}
