<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Orchid\Screens\Billing\Invoice\BillingInvoiceEditScreen;
use App\Orchid\Screens\Billing\Invoice\BillingInvoiceListScreen;
use App\Orchid\Screens\Billing\Invoice\BillingInvoiceScreen;
use App\Orchid\Screens\Billing\Loyalty\LoyaltyOverviewScreen;
use App\Orchid\Screens\Billing\Payment\PaymentEditScreen;
use App\Orchid\Screens\Billing\Payment\PaymentListScreen;
use App\Orchid\Screens\Billing\Product\ProductEditScreen;
use App\Orchid\Screens\Billing\Product\ProductListScreen;
use App\Orchid\Screens\Billing\Promotion\PromotionEditScreen;
use App\Orchid\Screens\Billing\Promotion\PromotionListScreen;
use App\Orchid\Screens\Cabinet\AppointmentEditScreen;
use App\Orchid\Screens\Cabinet\AppointmentListScreen;
use App\Orchid\Screens\Cabinet\AppointmentScreen;
use App\Orchid\Screens\Cabinet\DoctorScheduleEditScreen;
use App\Orchid\Screens\Cabinet\DoctorScheduleListScreen;
use App\Orchid\Screens\Cabinet\PatientEditScreen;
use App\Orchid\Screens\Cabinet\PatientListScreen;
use App\Orchid\Screens\Cabinet\PatientScreen;
use App\Orchid\Screens\Cabinet\VisitListScreen;
use App\Orchid\Screens\Cabinet\VisitScreen;
use App\Orchid\Screens\Dashboard\AdminDashboardScreen;
use App\Orchid\Screens\Dashboard\DoctorDashboardScreen;
use App\Orchid\Screens\Dashboard\PharmacistDashboardScreen;
use App\Orchid\Screens\Examples\ExampleActionsScreen;
use App\Orchid\Screens\Examples\ExampleCardsScreen;
use App\Orchid\Screens\Examples\ExampleChartsScreen;
use App\Orchid\Screens\Examples\ExampleFieldsAdvancedScreen;
use App\Orchid\Screens\Examples\ExampleFieldsScreen;
use App\Orchid\Screens\Examples\ExampleGridScreen;
use App\Orchid\Screens\Examples\ExampleLayoutsScreen;
use App\Orchid\Screens\Examples\ExampleScreen;
use App\Orchid\Screens\Examples\ExampleTextEditorsScreen;
use App\Orchid\Screens\Medical\Catalog\ConditionEditScreen;
use App\Orchid\Screens\Medical\Catalog\ConditionListScreen;
use App\Orchid\Screens\Medical\Catalog\MedicalCodeEditScreen;
use App\Orchid\Screens\Medical\Catalog\MedicalCodeListScreen;
use App\Orchid\Screens\Medical\Catalog\ProcedureEditScreen;
use App\Orchid\Screens\Medical\Catalog\ProcedureListScreen;
use App\Orchid\Screens\Medical\Catalog\SymptomEditScreen;
use App\Orchid\Screens\Medical\Catalog\SymptomListScreen;
use App\Orchid\Screens\Medical\Catalog\TreatmentEditScreen;
use App\Orchid\Screens\Medical\Catalog\TreatmentListScreen;
use App\Orchid\Screens\Medical\Diagnostic\PatientDiagnosticEditScreen;
use App\Orchid\Screens\Medical\Diagnostic\PatientDiagnosticListScreen;
use App\Orchid\Screens\Medical\Letter\MedicalLetterEditScreen;
use App\Orchid\Screens\Medical\Letter\MedicalLetterListScreen;
use App\Orchid\Screens\Medical\Letter\MedicalLetterTemplateEditScreen;
use App\Orchid\Screens\Medical\Letter\MedicalLetterTemplateListScreen;
use App\Orchid\Screens\Medical\Prescription\PrescriptionEditScreen;
use App\Orchid\Screens\Medical\Prescription\PrescriptionListScreen;
use App\Orchid\Screens\Medical\Questionnaire\QuestionnaireTemplateEditScreen;
use App\Orchid\Screens\Medical\Questionnaire\QuestionnaireTemplateListScreen;
use App\Orchid\Screens\Medical\Record\MedicalRecordEditScreen;
use App\Orchid\Screens\Medical\Record\MedicalRecordListScreen;
use App\Orchid\Screens\Organize\Clinics\ClinicEditScreen;
use App\Orchid\Screens\Organize\Clinics\ClinicListScreen;
use App\Orchid\Screens\Organize\Organizations\OrganizationEditScreen;
use App\Orchid\Screens\Organize\Organizations\OrganizationListScreen;
use App\Orchid\Screens\Organize\Pharmacies\PharmacyEditScreen;
use App\Orchid\Screens\Organize\Pharmacies\PharmacyListScreen;
use App\Orchid\Screens\Organize\Specialties\SpecialtyEditScreen;
use App\Orchid\Screens\Organize\Specialties\SpecialtyListScreen;
use App\Orchid\Screens\Pharmacy\FulfillmentScreen;
use App\Orchid\Screens\Pharmacy\InventoryEditScreen;
use App\Orchid\Screens\Pharmacy\InventoryListScreen;
use App\Orchid\Screens\Pharmacy\PharmacyDeliveryEditScreen;
use App\Orchid\Screens\Pharmacy\PharmacyDeliveryListScreen;
use App\Orchid\Screens\Pharmacy\PharmacySaleEditScreen;
use App\Orchid\Screens\Pharmacy\PharmacySaleListScreen;
use App\Orchid\Screens\Pharmacy\PharmacySupplierEditScreen;
use App\Orchid\Screens\Pharmacy\PharmacySupplierListScreen;
use App\Orchid\Screens\Pharmacy\PurchaseOrderEditScreen;
use App\Orchid\Screens\Pharmacy\PurchaseOrderListScreen;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Dashboards (Step 1: role-based landing)
Route::screen('/dashboard/admin', AdminDashboardScreen::class)->name('platform.dashboard.admin');
Route::screen('/dashboard/doctor', DoctorDashboardScreen::class)->name('platform.dashboard.doctor');
Route::screen('/dashboard/pharmacist', PharmacistDashboardScreen::class)->name('platform.dashboard.pharmacist');
Route::get('/dashboard', DashboardController::class)->name('platform.dashboard');

// Main
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

// ─── Organize (Step 2) ───
Route::screen('/organizations/create', OrganizationEditScreen::class)->name('platform.organizations.create');
Route::screen('/organizations/{organization}/edit', OrganizationEditScreen::class)->name('platform.organizations.edit');
Route::screen('/organizations', OrganizationListScreen::class)->name('platform.organizations');

Route::screen('/clinics/create', ClinicEditScreen::class)->name('platform.clinics.create');
Route::screen('/clinics/{clinic}/edit', ClinicEditScreen::class)->name('platform.clinics.edit');
Route::screen('/clinics', ClinicListScreen::class)->name('platform.clinics');

Route::screen('/pharmacies/create', PharmacyEditScreen::class)->name('platform.pharmacies.create');
Route::screen('/pharmacies/{pharmacy}/edit', PharmacyEditScreen::class)->name('platform.pharmacies.edit');
Route::screen('/pharmacies', PharmacyListScreen::class)->name('platform.pharmacies');

Route::screen('/specialties/create', SpecialtyEditScreen::class)->name('platform.specialties.create');
Route::screen('/specialties/{specialty}/edit', SpecialtyEditScreen::class)->name('platform.specialties.edit');
Route::screen('/specialties', SpecialtyListScreen::class)->name('platform.specialties');

// ─── Cabinet (Step 3) ───
Route::screen('/patients/create', PatientEditScreen::class)->name('platform.patients.create');
Route::screen('/patients/{patient}/edit', PatientEditScreen::class)->name('platform.patients.edit');
Route::screen('/patients/{patient}', PatientScreen::class)->name('platform.patients.detail');
Route::screen('/patients', PatientListScreen::class)->name('platform.patients');

Route::screen('/appointments/create', AppointmentEditScreen::class)->name('platform.appointments.create');
Route::screen('/appointments/{appointment}/edit', AppointmentEditScreen::class)->name('platform.appointments.edit');
Route::screen('/appointments/{appointment}', AppointmentScreen::class)->name('platform.appointments.detail');
Route::screen('/appointments', AppointmentListScreen::class)->name('platform.appointments');

Route::screen('/visits/{visit}', VisitScreen::class)->name('platform.visits.detail');
Route::screen('/visits', VisitListScreen::class)->name('platform.visits');

Route::screen('/schedules/create', DoctorScheduleEditScreen::class)->name('platform.schedules.create');
Route::screen('/schedules/{schedule}/edit', DoctorScheduleEditScreen::class)->name('platform.schedules.edit');
Route::screen('/schedules', DoctorScheduleListScreen::class)->name('platform.schedules');

// ─── Medical (Step 4) ───
Route::screen('/prescriptions/create', PrescriptionEditScreen::class)->name('platform.prescriptions.create');
Route::screen('/prescriptions/{prescription}/edit', PrescriptionEditScreen::class)->name('platform.prescriptions.edit');
Route::screen('/prescriptions', PrescriptionListScreen::class)->name('platform.prescriptions');

Route::screen('/records/create', MedicalRecordEditScreen::class)->name('platform.records.create');
Route::screen('/records/{record}/edit', MedicalRecordEditScreen::class)->name('platform.records.edit');
Route::screen('/records', MedicalRecordListScreen::class)->name('platform.records');

Route::screen('/letters/create', MedicalLetterEditScreen::class)->name('platform.letters.create');
Route::screen('/letters/{letter}/edit', MedicalLetterEditScreen::class)->name('platform.letters.edit');
Route::screen('/letters', MedicalLetterListScreen::class)->name('platform.letters');

Route::screen('/letter-templates/create', MedicalLetterTemplateEditScreen::class)->name('platform.letter-templates.create');
Route::screen('/letter-templates/{template}/edit', MedicalLetterTemplateEditScreen::class)->name('platform.letter-templates.edit');
Route::screen('/letter-templates', MedicalLetterTemplateListScreen::class)->name('platform.letter-templates');

Route::screen('/diagnostics/create', PatientDiagnosticEditScreen::class)->name('platform.diagnostics.create');
Route::screen('/diagnostics/{diagnostic}/edit', PatientDiagnosticEditScreen::class)->name('platform.diagnostics.edit');
Route::screen('/diagnostics', PatientDiagnosticListScreen::class)->name('platform.diagnostics');

Route::screen('/catalogs/conditions/create', ConditionEditScreen::class)->name('platform.catalogs.conditions.create');
Route::screen('/catalogs/conditions/{condition}/edit', ConditionEditScreen::class)->name('platform.catalogs.conditions.edit');
Route::screen('/catalogs/conditions', ConditionListScreen::class)->name('platform.catalogs.conditions');

Route::screen('/catalogs/symptoms/create', SymptomEditScreen::class)->name('platform.catalogs.symptoms.create');
Route::screen('/catalogs/symptoms/{symptom}/edit', SymptomEditScreen::class)->name('platform.catalogs.symptoms.edit');
Route::screen('/catalogs/symptoms', SymptomListScreen::class)->name('platform.catalogs.symptoms');

Route::screen('/catalogs/treatments/create', TreatmentEditScreen::class)->name('platform.catalogs.treatments.create');
Route::screen('/catalogs/treatments/{treatment}/edit', TreatmentEditScreen::class)->name('platform.catalogs.treatments.edit');
Route::screen('/catalogs/treatments', TreatmentListScreen::class)->name('platform.catalogs.treatments');

Route::screen('/catalogs/procedures/create', ProcedureEditScreen::class)->name('platform.catalogs.procedures.create');
Route::screen('/catalogs/procedures/{procedure}/edit', ProcedureEditScreen::class)->name('platform.catalogs.procedures.edit');
Route::screen('/catalogs/procedures', ProcedureListScreen::class)->name('platform.catalogs.procedures');

Route::screen('/catalogs/codes/create', MedicalCodeEditScreen::class)->name('platform.catalogs.codes.create');
Route::screen('/catalogs/codes/{code}/edit', MedicalCodeEditScreen::class)->name('platform.catalogs.codes.edit');
Route::screen('/catalogs/codes', MedicalCodeListScreen::class)->name('platform.catalogs.codes');

Route::screen('/questionnaires/create', QuestionnaireTemplateEditScreen::class)->name('platform.questionnaires.create');
Route::screen('/questionnaires/{template}/edit', QuestionnaireTemplateEditScreen::class)->name('platform.questionnaires.edit');
Route::screen('/questionnaires', QuestionnaireTemplateListScreen::class)->name('platform.questionnaires');

// ─── Pharmacy (Step 5) ───
Route::screen('/fulfillment', FulfillmentScreen::class)->name('platform.fulfillment');

Route::screen('/inventory/create', InventoryEditScreen::class)->name('platform.inventory.create');
Route::screen('/inventory/{inventory}/edit', InventoryEditScreen::class)->name('platform.inventory.edit');
Route::screen('/inventory', InventoryListScreen::class)->name('platform.inventory');

Route::screen('/sales/create', PharmacySaleEditScreen::class)->name('platform.sales.create');
Route::screen('/sales/{sale}/edit', PharmacySaleEditScreen::class)->name('platform.sales.edit');
Route::screen('/sales', PharmacySaleListScreen::class)->name('platform.sales');

Route::screen('/purchase-orders/create', PurchaseOrderEditScreen::class)->name('platform.purchase-orders.create');
Route::screen('/purchase-orders/{order}/edit', PurchaseOrderEditScreen::class)->name('platform.purchase-orders.edit');
Route::screen('/purchase-orders', PurchaseOrderListScreen::class)->name('platform.purchase-orders');

Route::screen('/suppliers/create', PharmacySupplierEditScreen::class)->name('platform.suppliers.create');
Route::screen('/suppliers/{supplier}/edit', PharmacySupplierEditScreen::class)->name('platform.suppliers.edit');
Route::screen('/suppliers', PharmacySupplierListScreen::class)->name('platform.suppliers');

Route::screen('/deliveries/create', PharmacyDeliveryEditScreen::class)->name('platform.deliveries.create');
Route::screen('/deliveries/{delivery}/edit', PharmacyDeliveryEditScreen::class)->name('platform.deliveries.edit');
Route::screen('/deliveries', PharmacyDeliveryListScreen::class)->name('platform.deliveries');

// ─── Billing (Step 6) ───
Route::screen('/invoices/create', BillingInvoiceEditScreen::class)->name('platform.invoices.create');
Route::screen('/invoices/{invoice}/edit', BillingInvoiceEditScreen::class)->name('platform.invoices.edit');
Route::screen('/invoices/{invoice}', BillingInvoiceScreen::class)->name('platform.invoices.detail');
Route::screen('/invoices', BillingInvoiceListScreen::class)->name('platform.invoices');

Route::screen('/payments/create', PaymentEditScreen::class)->name('platform.payments.create');
Route::screen('/payments/{payment}/edit', PaymentEditScreen::class)->name('platform.payments.edit');
Route::screen('/payments', PaymentListScreen::class)->name('platform.payments');

Route::screen('/products/create', ProductEditScreen::class)->name('platform.products.create');
Route::screen('/products/{product}/edit', ProductEditScreen::class)->name('platform.products.edit');
Route::screen('/products', ProductListScreen::class)->name('platform.products');

Route::screen('/promotions/create', PromotionEditScreen::class)->name('platform.promotions.create');
Route::screen('/promotions/{promotion}/edit', PromotionEditScreen::class)->name('platform.promotions.edit');
Route::screen('/promotions', PromotionListScreen::class)->name('platform.promotions');

Route::screen('/loyalty', LoyaltyOverviewScreen::class)->name('platform.loyalty');

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('menu.item.users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('menu.item.roles'), route('platform.systems.roles')));

// Example...
Route::screen('example', ExampleScreen::class)
    ->name('platform.example')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Example Screen'));

Route::screen('/examples/form/fields', ExampleFieldsScreen::class)->name('platform.example.fields');
Route::screen('/examples/form/advanced', ExampleFieldsAdvancedScreen::class)->name('platform.example.advanced');
Route::screen('/examples/form/editors', ExampleTextEditorsScreen::class)->name('platform.example.editors');
Route::screen('/examples/form/actions', ExampleActionsScreen::class)->name('platform.example.actions');

Route::screen('/examples/layouts', ExampleLayoutsScreen::class)->name('platform.example.layouts');
Route::screen('/examples/grid', ExampleGridScreen::class)->name('platform.example.grid');
Route::screen('/examples/charts', ExampleChartsScreen::class)->name('platform.example.charts');
Route::screen('/examples/cards', ExampleCardsScreen::class)->name('platform.example.cards');

// Route::screen('idea', Idea::class, 'platform.screens.idea');
