<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Organizations;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class OrganizationEditScreen extends Screen
{
    public ?Organization $organization = null;

    public function query(?Organization $organization = null): iterable
    {
        $organization ??= new Organization;

        if ($organization->exists) {
            $organization->loadCount(['clinics', 'pharmacies']);
        }

        return [
            'organization' => $organization,
        ];
    }

    public function name(): ?string
    {
        return $this->organization && $this->organization->exists
            ? __('organizations.edit_title')
            : __('organizations.create_title');
    }

    public function description(): ?string
    {
        return __('organizations.form_description');
    }

    public function permission(): ?iterable
    {
        return ['view_organization', 'create_organization', 'update_organization'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('organizations.back'))
                ->icon('bs.arrow-left')
                ->route('platform.organizations'),

            Button::make(__('organizations.remove'))
                ->icon('bs.trash3')
                ->confirm(__('organizations.delete_blocked', ['clinics' => '?', 'pharmacies' => '?']))
                ->method('remove')
                ->canSee((bool) ($this->organization && $this->organization->exists)),

            Button::make(__('organizations.save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        $side = [];
        if ($this->organization && $this->organization->exists) {
            $side[] = Layout::legend('organization', [
                Sight::make('clinics_count', __('organizations.clinics')),
                Sight::make('pharmacies_count', __('organizations.pharmacies')),
            ])->title(__('organizations.overview'));
        }

        return array_merge([
            Layout::rows([
                Input::make('organization.name')
                    ->title(__('organizations.name'))
                    ->required()
                    ->maxlength(255),

                Input::make('organization.slug')
                    ->title(__('organizations.slug'))
                    ->help('Slug')
                    ->maxlength(255),

                Select::make('organization.type')
                    ->title(__('organizations.type'))
                    ->options([
                        'clinic' => __('organizations.type_clinic'),
                        'pharmacy' => __('organizations.type_pharmacy'),
                        'hospital' => __('organizations.type_hospital'),
                        'laboratory' => __('organizations.type_laboratory'),
                        'other' => __('organizations.type_other'),
                    ])
                    ->empty(),

                Input::make('organization.subdomain_code')
                    ->title(__('organizations.subdomain_code'))
                    ->maxlength(100),

                Input::make('organization.cui')
                    ->title(__('organizations.cui'))
                    ->maxlength(50),

                Input::make('organization.reg_com')
                    ->title(__('organizations.reg_com'))
                    ->maxlength(50),

                Input::make('organization.address')
                    ->title(__('organizations.address'))
                    ->maxlength(255),

                Input::make('organization.city')
                    ->title(__('organizations.city'))
                    ->maxlength(100),

                Input::make('organization.county')
                    ->title(__('organizations.county'))
                    ->maxlength(100),

                Input::make('organization.country')
                    ->title(__('organizations.country'))
                    ->maxlength(10),

                Input::make('organization.postal_code')
                    ->title(__('organizations.postal_code'))
                    ->maxlength(20),

                Input::make('organization.contact_email')
                    ->type('email')
                    ->title(__('organizations.contact_email'))
                    ->maxlength(255),

                Input::make('organization.contact_phone')
                    ->title(__('organizations.contact_phone'))
                    ->maxlength(50),

                Input::make('organization.email')
                    ->type('email')
                    ->title(__('organizations.email'))
                    ->maxlength(255),

                Input::make('organization.phone')
                    ->title(__('organizations.phone'))
                    ->maxlength(50),

                Input::make('organization.website')
                    ->type('url')
                    ->title(__('organizations.website'))
                    ->maxlength(255),

                Input::make('organization.logo_url')
                    ->type('url')
                    ->title(__('organizations.logo_url'))
                    ->maxlength(500),

                Switcher::make('organization.is_active')
                    ->title(__('organizations.is_active'))
                    ->sendTrueOrFalse(),
            ]),
        ], $side);
    }

    public function save(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate([
            'organization.name' => 'required|string|max:255',
            'organization.slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organization->getKey())],
            'organization.type' => 'nullable|string|max:50',
            'organization.subdomain_code' => 'nullable|string|max:100',
            'organization.cui' => 'nullable|string|max:50',
            'organization.reg_com' => 'nullable|string|max:50',
            'organization.address' => 'nullable|string|max:255',
            'organization.city' => 'nullable|string|max:100',
            'organization.county' => 'nullable|string|max:100',
            'organization.country' => 'nullable|string|max:10',
            'organization.postal_code' => 'nullable|string|max:20',
            'organization.contact_email' => 'nullable|email|max:255',
            'organization.contact_phone' => 'nullable|string|max:50',
            'organization.email' => 'nullable|email|max:255',
            'organization.phone' => 'nullable|string|max:50',
            'organization.website' => 'nullable|url|max:255',
            'organization.logo_url' => 'nullable|url|max:500',
            'organization.is_active' => 'nullable|boolean',
        ])['organization'];

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['name'] ?? 'org')) ?: null;
        }

        // forceFill: the ported model $fillable omits several legacy columns
        // (slug, type, phone, email, …); models themselves are read-only this step.
        $organization->forceFill($data)->save();

        Toast::info(__('organizations.saved'));

        return redirect()->route('platform.organizations');
    }

    public function remove(Organization $organization): RedirectResponse
    {
        $clinics = $organization->clinics()->count();
        $pharmacies = $organization->pharmacies()->count();

        if ($clinics > 0 || $pharmacies > 0) {
            Toast::error(__('organizations.delete_blocked', ['clinics' => $clinics, 'pharmacies' => $pharmacies]));

            return redirect()->route('platform.organizations.edit', $organization);
        }

        $organization->delete();

        Toast::info(__('organizations.removed'));

        return redirect()->route('platform.organizations');
    }
}
