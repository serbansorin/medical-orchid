<?php

declare(strict_types=1);

namespace App\Orchid;

use App\Models\Role;
use App\Models\User;
use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // Shared-DB bridges: the legacy Spatie-style RBAC tables back the
        // Orchid access layer (see App\Models\User / App\Models\Role).
        Dashboard::useModel(\Orchid\Platform\Models\User::class, User::class);
        Dashboard::useModel(\Orchid\Platform\Models\Role::class, Role::class);
    }

    /**
     * Register the application menu (role-gated per the design menu map).
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        $user = auth()->user();

        $canAdmin = (bool) $user?->hasAnyAccess(['access_admin_panel']);
        $canCabinet = (bool) $user?->hasAnyAccess(['access_doctor_panel', 'view_any_appointment', 'access_admin_panel']);
        $canMedical = (bool) $user?->hasAnyAccess(['access_doctor_panel', 'view_any_prescription', 'access_admin_panel']);
        $canPharmacy = (bool) $user?->hasAnyAccess(['access_pharmacy_panel', 'access_admin_panel']);
        $canBilling = (bool) $user?->hasAnyAccess(['access_receptionist_panel', 'view_any_billing_invoice', 'access_admin_panel']);

        return [
            Menu::make(__('menu.item.dashboard'))
                ->icon('bs.speedometer2')
                ->route('platform.dashboard'),

            // Organize
            Menu::make(__('menu.item.organizations'))
                ->icon('bs.building')
                ->title(__('menu.section.organize'))
                ->route('platform.organizations')
                ->canSee($canAdmin),
            Menu::make(__('menu.item.clinics'))
                ->icon('bs.hospital')
                ->route('platform.clinics')
                ->canSee($canAdmin),
            Menu::make(__('menu.item.pharmacies'))
                ->icon('bs.capsule-pill')
                ->route('platform.pharmacies')
                ->canSee($canAdmin),
            Menu::make(__('menu.item.users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->canSee($canAdmin),
            Menu::make(__('menu.item.roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->canSee($canAdmin),
            Menu::make(__('menu.item.specialties'))
                ->icon('bs.award')
                ->route('platform.specialties')
                ->canSee($canAdmin)
                ->divider(),

            // Cabinet
            Menu::make(__('menu.item.patients'))
                ->icon('bs.person-vcard')
                ->title(__('menu.section.cabinet'))
                ->route('platform.patients')
                ->canSee($canCabinet),
            Menu::make(__('menu.item.appointments'))
                ->icon('bs.calendar-check')
                ->route('platform.appointments')
                ->canSee($canCabinet),
            Menu::make(__('menu.item.visits'))
                ->icon('bs.clipboard2-pulse')
                ->route('platform.visits')
                ->canSee($canCabinet),
            Menu::make(__('menu.item.schedules'))
                ->icon('bs.calendar-week')
                ->route('platform.schedules')
                ->canSee($canCabinet)
                ->divider(),

            // Medical
            Menu::make(__('menu.item.prescriptions'))
                ->icon('bs.file-medical')
                ->title(__('menu.section.medical'))
                ->route('platform.prescriptions')
                ->canSee($canMedical),
            Menu::make(__('menu.item.records'))
                ->icon('bs.folder2-open')
                ->route('platform.records')
                ->canSee($canMedical),
            Menu::make(__('menu.item.letters'))
                ->icon('bs.envelope-paper')
                ->route('platform.letters')
                ->canSee($canMedical),
            Menu::make(__('menu.item.diagnostics'))
                ->icon('bs.activity')
                ->route('platform.diagnostics')
                ->canSee($canMedical),
            Menu::make(__('menu.item.catalogs'))
                ->icon('bs.journals')
                ->slug('catalogs')
                ->canSee($canMedical)
                ->list([
                    Menu::make(__('menu.item.catalog_conditions'))
                        ->icon('bs.card-list')
                        ->route('platform.catalogs.conditions'),
                    Menu::make(__('menu.item.catalog_symptoms'))
                        ->icon('bs.card-list')
                        ->route('platform.catalogs.symptoms'),
                    Menu::make(__('menu.item.catalog_treatments'))
                        ->icon('bs.card-list')
                        ->route('platform.catalogs.treatments'),
                    Menu::make(__('menu.item.catalog_procedures'))
                        ->icon('bs.card-list')
                        ->route('platform.catalogs.procedures'),
                    Menu::make(__('menu.item.catalog_codes'))
                        ->icon('bs.card-list')
                        ->route('platform.catalogs.codes'),
                ]),
            Menu::make(__('menu.item.questionnaires'))
                ->icon('bs.ui-checks')
                ->route('platform.questionnaires')
                ->canSee($canMedical)
                ->divider(),

            // Pharmacy
            Menu::make(__('menu.item.fulfillment'))
                ->icon('bs.box-seam')
                ->title(__('menu.section.pharmacy'))
                ->route('platform.fulfillment')
                ->canSee($canPharmacy),
            Menu::make(__('menu.item.inventory'))
                ->icon('bs.boxes')
                ->route('platform.inventory')
                ->canSee($canPharmacy),
            Menu::make(__('menu.item.sales'))
                ->icon('bs.cart')
                ->route('platform.sales')
                ->canSee($canPharmacy),
            Menu::make(__('menu.item.purchase_orders'))
                ->icon('bs.cart-plus')
                ->route('platform.purchase-orders')
                ->canSee($canPharmacy),
            Menu::make(__('menu.item.suppliers'))
                ->icon('bs.truck')
                ->route('platform.suppliers')
                ->canSee($canPharmacy),
            Menu::make(__('menu.item.deliveries'))
                ->icon('bs.geo-alt')
                ->route('platform.deliveries')
                ->canSee($canPharmacy)
                ->divider(),

            // Billing
            Menu::make(__('menu.item.invoices'))
                ->icon('bs.receipt')
                ->title(__('menu.section.billing'))
                ->route('platform.invoices')
                ->canSee($canBilling),
            Menu::make(__('menu.item.payments'))
                ->icon('bs.credit-card')
                ->route('platform.payments')
                ->canSee($canBilling),
            Menu::make(__('menu.item.products'))
                ->icon('bs.bag')
                ->route('platform.products')
                ->canSee($canBilling),
            Menu::make(__('menu.item.promotions'))
                ->icon('bs.percent')
                ->route('platform.promotions')
                ->canSee($canBilling),
            Menu::make(__('menu.item.loyalty'))
                ->icon('bs.stars')
                ->route('platform.loyalty')
                ->canSee($canBilling),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('menu.perm.group'))
                ->addPermission('platform.systems.roles', __('menu.perm.roles'))
                ->addPermission('platform.systems.users', __('menu.perm.users')),
        ];
    }
}
