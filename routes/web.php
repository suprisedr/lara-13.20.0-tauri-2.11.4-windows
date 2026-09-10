<?php

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $companies = auth()->user()->companies()->latest()->paginate(10);

    return view('dashboard', compact('companies'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/step/1', [OnboardingController::class, 'step1'])->name('step1');
    Route::post('/step/1', [OnboardingController::class, 'storeStep1'])->name('step1.store');
    Route::get('/{company}/step/2', [OnboardingController::class, 'step2'])->name('step2');
    Route::post('/{company}/step/2', [OnboardingController::class, 'storeStep2'])->name('step2.store');
    Route::get('/{company}/step/3', [OnboardingController::class, 'step3'])->name('step3');
    Route::post('/{company}/step/3', [OnboardingController::class, 'storeStep3'])->name('step3.store');
    Route::delete('/{company}/cancel', [OnboardingController::class, 'cancel'])->name('cancel');
});

Route::middleware(['auth', 'verified'])->prefix('companies')->name('companies.')->group(function () {
    Route::get('/{company}/dashboard', [\App\Http\Controllers\CompanyController::class, 'dashboard'])->name('dashboard');
    Route::get('/{company}', [\App\Http\Controllers\CompanyController::class, 'show'])->name('show');
    Route::get('/{company}/chart-of-accounts', [\App\Http\Controllers\CompanyController::class, 'chartOfAccounts'])->name('chart-of-accounts');
    Route::get('/{company}/chart-of-accounts/export', [\App\Http\Controllers\CompanyController::class, 'chartOfAccountsExport'])->name('chart-of-accounts.export');
    Route::post('/{company}/chart-of-accounts', [\App\Http\Controllers\CompanyController::class, 'storeChartOfAccount'])->name('chart-of-accounts.store');
    Route::post('/{company}/chart-of-accounts/{account}/items', [\App\Http\Controllers\CompanyController::class, 'storeChartOfAccountItem'])->name('chart-of-accounts.items.store');
    Route::patch('/{company}/chart-of-accounts/{account}/contra', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountContra'])->name('chart-of-accounts.contra');
    Route::patch('/{company}/chart-of-accounts/{account}/show-separately', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountShowSeparately'])->name('chart-of-accounts.show-separately');
    Route::patch('/{company}/chart-of-accounts/{account}/ppe', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountPpe'])->name('chart-of-accounts.ppe');
    Route::patch('/{company}/chart-of-accounts/{account}/intangible', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountIntangible'])->name('chart-of-accounts.intangible');
    Route::patch('/{company}/chart-of-accounts/{account}/oci', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountOci'])->name('chart-of-accounts.oci');
    Route::patch('/{company}/chart-of-accounts/{account}/inventory', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountInventory'])->name('chart-of-accounts.inventory');
    Route::patch('/{company}/chart-of-accounts/{account}/investment-property', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountInvestmentProperty'])->name('chart-of-accounts.investment-property');
    Route::patch('/{company}/chart-of-accounts/{account}/biological-asset', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountBiologicalAsset'])->name('chart-of-accounts.biological-asset');
    Route::patch('/{company}/chart-of-accounts/{account}/lease-asset', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountLeaseAsset'])->name('chart-of-accounts.lease-asset');
    Route::patch('/{company}/chart-of-accounts/{account}/cash', [\App\Http\Controllers\CompanyController::class, 'toggleChartOfAccountCash'])->name('chart-of-accounts.cash');
    Route::patch('/{company}/chart-of-accounts/{account}', [\App\Http\Controllers\CompanyController::class, 'updateChartOfAccount'])->name('chart-of-accounts.update');
    Route::delete('/{company}/chart-of-accounts/{account}', [\App\Http\Controllers\CompanyController::class, 'destroyChartOfAccount'])->name('chart-of-accounts.destroy');
    Route::patch('/{company}/status', [\App\Http\Controllers\CompanyController::class, 'toggleStatus'])->name('status.toggle');
    Route::patch('/{company}/profile', [\App\Http\Controllers\CompanyController::class, 'updateProfile'])->name('profile.update');
    Route::post('/{company}/oci-accounts', [\App\Http\Controllers\CompanyController::class, 'addOciAccount'])->name('oci-accounts.add');
    Route::delete('/{company}/oci-accounts/{accountId}', [\App\Http\Controllers\CompanyController::class, 'removeOciAccount'])->name('oci-accounts.remove');
    Route::get('/{company}/afs-details', [\App\Http\Controllers\CompanyController::class, 'editAfsDetails'])->name('afs-details.edit');
    Route::patch('/{company}/afs-details', [\App\Http\Controllers\CompanyController::class, 'updateAfsDetails'])->name('afs-details.update');
    Route::get('/{company}/cash-flow-mapping', [\App\Http\Controllers\CompanyController::class, 'editCashFlowMapping'])->name('cash-flow-mapping.edit');
    Route::patch('/{company}/cash-flow-mapping', [\App\Http\Controllers\CompanyController::class, 'updateCashFlowMapping'])->name('cash-flow-mapping.update');
    Route::get('/{company}/cash-flow-manual', [\App\Http\Controllers\CompanyController::class, 'editCashFlowManual'])->name('cash-flow-manual.edit');
    Route::put('/{company}/cash-flow-manual', [\App\Http\Controllers\CompanyController::class, 'saveCashFlowManual'])->name('cash-flow-manual.save');
    Route::get('/{company}/chart-of-accounts/opening-balances', [\App\Http\Controllers\CompanyController::class, 'editOpeningBalances'])->name('opening-balances.edit');
    Route::put('/{company}/chart-of-accounts/opening-balances', [\App\Http\Controllers\CompanyController::class, 'updateOpeningBalances'])->name('opening-balances.update');
    Route::post('/{company}/chart-of-accounts/financial-periods/next', [\App\Http\Controllers\CompanyController::class, 'openNextPeriod'])->name('financial-periods.next');
    Route::get('/{company}/transactions', [\App\Http\Controllers\CompanyController::class, 'transactions'])->name('transactions');
    Route::get('/{company}/transactions/search', [\App\Http\Controllers\CompanyController::class, 'transactionsSearch'])->name('transactions.search');
    Route::get('/{company}/customers/search', [\App\Http\Controllers\CompanyController::class, 'customersSearch'])->name('customers.search');
    Route::get('/{company}/inventory/search', [\App\Http\Controllers\CompanyController::class, 'inventorySearch'])->name('inventory.search');
    Route::get('/{company}/transactions/export', [\App\Http\Controllers\CompanyController::class, 'transactionsExport'])->name('transactions.export');
    Route::post('/{company}/transactions', [\App\Http\Controllers\TransactionController::class, 'store'])->name('transactions.store');
    Route::delete('/{company}/transactions/{transaction}', [\App\Http\Controllers\TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::post('/{company}/transactions/bulk-delete', [\App\Http\Controllers\TransactionController::class, 'bulkDestroy'])->name('transactions.bulk-delete');
    Route::post('/{company}/transactions/bulk-post', [\App\Http\Controllers\TransactionController::class, 'bulkPost'])->name('transactions.bulk-post');
    Route::post('/{company}/transactions/bulk-reverse', [\App\Http\Controllers\TransactionController::class, 'bulkReverse'])->name('transactions.bulk-reverse');
    Route::patch('/{company}/transactions/{transaction}/status', [\App\Http\Controllers\TransactionController::class, 'updateStatus'])->name('transactions.status');
    Route::post('/{company}/transactions/{transaction}/reverse', [\App\Http\Controllers\TransactionController::class, 'reverse'])->name('transactions.reverse');
    Route::post('/{company}/transactions/{transaction}/correction', [\App\Http\Controllers\TransactionController::class, 'createCorrection'])->name('transactions.correction');
    Route::patch('/{company}/transactions/{transaction}/source-document', [\App\Http\Controllers\TransactionController::class, 'updateSourceDocument'])->name('transactions.source-document');
    Route::patch('/{company}/transactions/{transaction}/notes', [\App\Http\Controllers\TransactionController::class, 'updateNotes'])->name('transactions.notes');
    Route::patch('/{company}/transactions/{transaction}/description', [\App\Http\Controllers\TransactionController::class, 'updateDescription'])->name('transactions.description');
    Route::patch('/{company}/transactions/{transaction}/date', [\App\Http\Controllers\TransactionController::class, 'updateDate'])->name('transactions.date');
    Route::post('/{company}/transactions/{transaction}/lines', [\App\Http\Controllers\TransactionController::class, 'storeLine'])->name('transactions.lines.store');
    Route::patch('/{company}/transactions/{transaction}/lines/{line}/note', [\App\Http\Controllers\TransactionController::class, 'updateLineNote'])->name('transactions.line-note');
    Route::patch('/{company}/transactions/{transaction}/lines/{line}/account', [\App\Http\Controllers\TransactionController::class, 'updateLineAccount'])->name('transactions.line-account');
    Route::patch('/{company}/transactions/{transaction}/lines/{line}/amount', [\App\Http\Controllers\TransactionController::class, 'updateLineAmount'])->name('transactions.line-amount');
    Route::delete('/{company}/transactions/{transaction}/lines/{line}', [\App\Http\Controllers\TransactionController::class, 'destroyLine'])->name('transactions.lines.destroy');

    // Invoices
    Route::get('/{company}/invoices', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/{company}/invoices/create', [\App\Http\Controllers\InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/{company}/invoices', [\App\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/{company}/invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/{company}/invoices/{invoice}/edit', [\App\Http\Controllers\InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/{company}/invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::get('/{company}/invoices/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::patch('/{company}/invoices/{invoice}/status', [\App\Http\Controllers\InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
    Route::post('/{company}/invoices/{invoice}/payments', [\App\Http\Controllers\InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');

    // Quotations
    Route::get('/{company}/quotations', [\App\Http\Controllers\QuotationController::class, 'index'])->name('quotations.index');
    Route::get('/{company}/quotations/create', [\App\Http\Controllers\QuotationController::class, 'create'])->name('quotations.create');
    Route::post('/{company}/quotations', [\App\Http\Controllers\QuotationController::class, 'store'])->name('quotations.store');
    Route::get('/{company}/quotations/{quotation}', [\App\Http\Controllers\QuotationController::class, 'show'])->name('quotations.show');
    Route::get('/{company}/quotations/{quotation}/edit', [\App\Http\Controllers\QuotationController::class, 'edit'])->name('quotations.edit');
    Route::put('/{company}/quotations/{quotation}', [\App\Http\Controllers\QuotationController::class, 'update'])->name('quotations.update');
    Route::get('/{company}/quotations/{quotation}/pdf', [\App\Http\Controllers\QuotationController::class, 'pdf'])->name('quotations.pdf');
    Route::patch('/{company}/quotations/{quotation}/status', [\App\Http\Controllers\QuotationController::class, 'updateStatus'])->name('quotations.update-status');
    Route::post('/{company}/quotations/{quotation}/convert', [\App\Http\Controllers\QuotationController::class, 'convertToInvoice'])->name('quotations.convert');

    Route::get('/{company}/credit-notes', [\App\Http\Controllers\CreditNoteController::class, 'index'])->name('credit-notes.index');
    Route::get('/{company}/credit-notes/create', [\App\Http\Controllers\CreditNoteController::class, 'create'])->name('credit-notes.create');
    Route::post('/{company}/credit-notes', [\App\Http\Controllers\CreditNoteController::class, 'store'])->name('credit-notes.store');
    Route::get('/{company}/credit-notes/{creditNote}', [\App\Http\Controllers\CreditNoteController::class, 'show'])->name('credit-notes.show');
    Route::patch('/{company}/credit-notes/{creditNote}/status', [\App\Http\Controllers\CreditNoteController::class, 'updateStatus'])->name('credit-notes.update-status');
    Route::get('/{company}/credit-notes/{creditNote}/pdf', [\App\Http\Controllers\CreditNoteController::class, 'pdf'])->name('credit-notes.pdf');

    Route::get('/{company}/delivery-notes', [\App\Http\Controllers\DeliveryNoteController::class, 'index'])->name('delivery-notes.index');
    Route::get('/{company}/delivery-notes/create', [\App\Http\Controllers\DeliveryNoteController::class, 'create'])->name('delivery-notes.create');
    Route::post('/{company}/delivery-notes', [\App\Http\Controllers\DeliveryNoteController::class, 'store'])->name('delivery-notes.store');
    Route::get('/{company}/delivery-notes/{deliveryNote}', [\App\Http\Controllers\DeliveryNoteController::class, 'show'])->name('delivery-notes.show');
    Route::patch('/{company}/delivery-notes/{deliveryNote}/status', [\App\Http\Controllers\DeliveryNoteController::class, 'updateStatus'])->name('delivery-notes.update-status');
    Route::get('/{company}/delivery-notes/{deliveryNote}/pdf', [\App\Http\Controllers\DeliveryNoteController::class, 'pdf'])->name('delivery-notes.pdf');

    // Customers
    Route::get('/{company}/customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/{company}/customers/create', [\App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create');
    Route::get('/{company}/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/{company}/customers', [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/{company}/customers/{customer}/statement', [\App\Http\Controllers\CustomerController::class, 'statement'])->name('customers.statement');
    Route::get('/{company}/customers/{customer}/statement/pdf', [\App\Http\Controllers\CustomerController::class, 'statementPdf'])->name('customers.statement.pdf');
    Route::get('/{company}/customers/{customer}/edit', [\App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
    Route::patch('/{company}/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');

    // Email Accounts
    Route::prefix('/{company}/email-accounts')->name('email-accounts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EmailAccountController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\EmailAccountController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\EmailAccountController::class, 'store'])->name('store');
        Route::get('/oauth/{provider}', [\App\Http\Controllers\EmailAccountController::class, 'redirectToOAuth'])->name('oauth');
        Route::get('/oauth/{provider}/callback', [\App\Http\Controllers\EmailAccountController::class, 'handleOAuthCallback'])->name('oauth.callback');
        Route::delete('/{account}', [\App\Http\Controllers\EmailAccountController::class, 'destroy'])->name('destroy');
        Route::get('/{account}/emails', [\App\Http\Controllers\EmailAccountController::class, 'emails'])->name('emails');
        Route::patch('/{account}/sync-interval', [\App\Http\Controllers\EmailAccountController::class, 'updateSyncInterval'])->name('sync-interval');
        Route::patch('/emails/{email}/review', [\App\Http\Controllers\EmailAccountController::class, 'reviewEmail'])->name('emails.review');
        Route::get('/attachments/{attachment}/download', [\App\Http\Controllers\EmailAccountController::class, 'downloadAttachment'])->name('attachments.download');
    });

    // Suppliers
    Route::get('/{company}/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/{company}/suppliers/create', [\App\Http\Controllers\SupplierController::class, 'create'])->name('suppliers.create');
    Route::get('/{company}/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'show'])->name('suppliers.show');
    Route::post('/{company}/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/{company}/suppliers/{supplier}/statement', [\App\Http\Controllers\SupplierController::class, 'statement'])->name('suppliers.statement');
    Route::get('/{company}/suppliers/{supplier}/statement/pdf', [\App\Http\Controllers\SupplierController::class, 'statementPdf'])->name('suppliers.statement.pdf');
    Route::get('/{company}/suppliers/{supplier}/edit', [\App\Http\Controllers\SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::patch('/{company}/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');

    // Inventory
    Route::get('/{company}/inventory', [\App\Http\Controllers\InventoryItemController::class, 'index'])->name('inventory.index');
    Route::post('/{company}/inventory', [\App\Http\Controllers\InventoryItemController::class, 'store'])->name('inventory.store');
    Route::get('/{company}/inventory/{inventoryItem}', [\App\Http\Controllers\InventoryItemController::class, 'show'])->name('inventory.show');
    Route::put('/{company}/inventory/{inventoryItem}', [\App\Http\Controllers\InventoryItemController::class, 'update'])->name('inventory.update');
    Route::delete('/{company}/inventory/{inventoryItem}', [\App\Http\Controllers\InventoryItemController::class, 'destroy'])->name('inventory.destroy');
    Route::post('/{company}/inventory/{inventoryItem}/movements', [\App\Http\Controllers\InventoryItemController::class, 'recordMovement'])->name('inventory.movements.store');
    Route::post('/{company}/inventory/{inventoryItem}/write-down', [\App\Http\Controllers\InventoryItemController::class, 'writeDown'])->name('inventory.write-down');
    Route::post('/{company}/inventory/{inventoryItem}/reverse-write-down', [\App\Http\Controllers\InventoryItemController::class, 'reverseWriteDown'])->name('inventory.reverse-write-down');

    // Asset register (fixed-asset / PPE management)
    Route::get('/{company}/assets', [\App\Http\Controllers\AssetController::class, 'index'])->name('assets.index');
    Route::post('/{company}/assets/classes', [\App\Http\Controllers\AssetController::class, 'storeClass'])->name('assets.classes.store');
    Route::delete('/{company}/assets/classes/{ppeClass}', [\App\Http\Controllers\AssetController::class, 'destroyClass'])->name('assets.classes.destroy');
    Route::post('/{company}/assets', [\App\Http\Controllers\AssetController::class, 'store'])->name('assets.store');
    // Intangible-asset register (IAS 38)
    Route::get('/{company}/intangibles', [\App\Http\Controllers\IntangibleAssetController::class, 'index'])->name('intangibles.index');
    Route::post('/{company}/intangibles/classes', [\App\Http\Controllers\IntangibleAssetController::class, 'storeClass'])->name('intangibles.classes.store');
    Route::delete('/{company}/intangibles/classes/{intangibleClass}', [\App\Http\Controllers\IntangibleAssetController::class, 'destroyClass'])->name('intangibles.classes.destroy');
    Route::post('/{company}/intangibles', [\App\Http\Controllers\IntangibleAssetController::class, 'store'])->name('intangibles.store');
    Route::get('/{company}/intangibles/{intangible}', [\App\Http\Controllers\IntangibleAssetController::class, 'show'])->name('intangibles.show');
    Route::patch('/{company}/intangibles/{intangible}', [\App\Http\Controllers\IntangibleAssetController::class, 'update'])->name('intangibles.update');
    Route::delete('/{company}/intangibles/{intangible}', [\App\Http\Controllers\IntangibleAssetController::class, 'destroy'])->name('intangibles.destroy');
    Route::post('/{company}/intangibles/{intangible}/revalue', [\App\Http\Controllers\IntangibleAssetController::class, 'revalue'])->name('intangibles.revalue');
    Route::post('/{company}/intangibles/{intangible}/impair', [\App\Http\Controllers\IntangibleAssetController::class, 'impair'])->name('intangibles.impair');
    Route::post('/{company}/intangibles/{intangible}/reverse-impairment', [\App\Http\Controllers\IntangibleAssetController::class, 'reverseImpairment'])->name('intangibles.reverse-impairment');
    Route::post('/{company}/intangibles/{intangible}/capitalise', [\App\Http\Controllers\IntangibleAssetController::class, 'capitalise'])->name('intangibles.capitalise');
    Route::post('/{company}/intangibles/{intangible}/dispose', [\App\Http\Controllers\IntangibleAssetController::class, 'dispose'])->name('intangibles.dispose');
    Route::get('/{company}/intangibles/{intangible}/history', [\App\Http\Controllers\IntangibleAssetController::class, 'history'])->name('intangibles.history');
    Route::post('/{company}/intangibles/{intangible}/events/{event}/retry-posting', [\App\Http\Controllers\IntangibleAssetController::class, 'retryPosting'])->name('intangibles.events.retry-posting');
    Route::get('/{company}/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'show'])->name('assets.show');
    Route::patch('/{company}/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'update'])->name('assets.update');
    Route::delete('/{company}/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('assets.destroy');
    Route::post('/{company}/assets/{asset}/revalue', [\App\Http\Controllers\AssetController::class, 'revalue'])->name('assets.revalue');
    Route::post('/{company}/assets/{asset}/impair', [\App\Http\Controllers\AssetController::class, 'impair'])->name('assets.impair');
    Route::post('/{company}/assets/{asset}/reverse-impairment', [\App\Http\Controllers\AssetController::class, 'reverseImpairment'])->name('assets.reverse-impairment');
    Route::post('/{company}/assets/{asset}/capitalise', [\App\Http\Controllers\AssetController::class, 'capitalise'])->name('assets.capitalise');
    Route::post('/{company}/assets/{asset}/dispose', [\App\Http\Controllers\AssetController::class, 'dispose'])->name('assets.dispose');
    Route::get('/{company}/assets/{asset}/history', [\App\Http\Controllers\AssetController::class, 'history'])->name('assets.history');
    Route::post('/{company}/assets/{asset}/reclassify-held-for-sale', [\App\Http\Controllers\AssetHeldForSaleController::class, 'reclassify'])->name('assets.reclassify-held-for-sale');
    Route::post('/{company}/assets/{asset}/events/{event}/retry-posting', [\App\Http\Controllers\AssetController::class, 'retryPosting'])->name('assets.events.retry-posting');

    // Assets Held for Sale (IFRS 5)
    Route::get('/{company}/held-for-sale', [\App\Http\Controllers\AssetHeldForSaleController::class, 'index'])->name('held-for-sale.index');
    Route::get('/{company}/held-for-sale/{heldForSale}', [\App\Http\Controllers\AssetHeldForSaleController::class, 'show'])->name('held-for-sale.show');
    Route::patch('/{company}/held-for-sale/{heldForSale}', [\App\Http\Controllers\AssetHeldForSaleController::class, 'update'])->name('held-for-sale.update');
    Route::post('/{company}/held-for-sale/{heldForSale}/reverse', [\App\Http\Controllers\AssetHeldForSaleController::class, 'reverse'])->name('held-for-sale.reverse');
    Route::post('/{company}/held-for-sale/{heldForSale}/dispose', [\App\Http\Controllers\AssetHeldForSaleController::class, 'dispose'])->name('held-for-sale.dispose');

    // Lease register (IFRS 16)
    Route::get('/{company}/leases', [\App\Http\Controllers\LeaseController::class, 'index'])->name('leases.index');
    Route::post('/{company}/leases', [\App\Http\Controllers\LeaseController::class, 'store'])->name('leases.store');
    Route::get('/{company}/leases/{lease}', [\App\Http\Controllers\LeaseController::class, 'show'])->name('leases.show');
    Route::patch('/{company}/leases/{lease}', [\App\Http\Controllers\LeaseController::class, 'update'])->name('leases.update');
    Route::delete('/{company}/leases/{lease}', [\App\Http\Controllers\LeaseController::class, 'destroy'])->name('leases.destroy');
    Route::post('/{company}/leases/{lease}/modify', [\App\Http\Controllers\LeaseController::class, 'modify'])->name('leases.modify');
    Route::post('/{company}/leases/{lease}/impair', [\App\Http\Controllers\LeaseController::class, 'impair'])->name('leases.impair');
    Route::post('/{company}/leases/{lease}/reverse-impairment', [\App\Http\Controllers\LeaseController::class, 'reverseImpairment'])->name('leases.reverse-impairment');
    Route::post('/{company}/leases/{lease}/terminate', [\App\Http\Controllers\LeaseController::class, 'terminate'])->name('leases.terminate');
    Route::get('/{company}/leases/{lease}/schedule', [\App\Http\Controllers\LeaseController::class, 'amortisationSchedule'])->name('leases.schedule');
    Route::post('/{company}/leases/{lease}/events/{event}/retry-posting', [\App\Http\Controllers\LeaseController::class, 'retryPosting'])->name('leases.events.retry-posting');

    // Investment Properties (IAS 40)
    Route::get('/{company}/investment-properties', [\App\Http\Controllers\InvestmentPropertyController::class, 'index'])->name('investment-properties.index');
    Route::post('/{company}/investment-properties/classes', [\App\Http\Controllers\InvestmentPropertyController::class, 'storeClass'])->name('investment-properties.classes.store');
    Route::delete('/{company}/investment-properties/classes/{investmentPropertyClass}', [\App\Http\Controllers\InvestmentPropertyController::class, 'destroyClass'])->name('investment-properties.classes.destroy');
    Route::post('/{company}/investment-properties', [\App\Http\Controllers\InvestmentPropertyController::class, 'store'])->name('investment-properties.store');
    Route::get('/{company}/investment-properties/{investmentProperty}', [\App\Http\Controllers\InvestmentPropertyController::class, 'show'])->name('investment-properties.show');
    Route::patch('/{company}/investment-properties/{investmentProperty}', [\App\Http\Controllers\InvestmentPropertyController::class, 'update'])->name('investment-properties.update');
    Route::delete('/{company}/investment-properties/{investmentProperty}', [\App\Http\Controllers\InvestmentPropertyController::class, 'destroy'])->name('investment-properties.destroy');
    Route::post('/{company}/investment-properties/{investmentProperty}/fair-value-adjust', [\App\Http\Controllers\InvestmentPropertyController::class, 'fairValueAdjust'])->name('investment-properties.fair-value-adjust');
    Route::post('/{company}/investment-properties/{investmentProperty}/impair', [\App\Http\Controllers\InvestmentPropertyController::class, 'impair'])->name('investment-properties.impair');
    Route::post('/{company}/investment-properties/{investmentProperty}/reverse-impairment', [\App\Http\Controllers\InvestmentPropertyController::class, 'reverseImpairment'])->name('investment-properties.reverse-impairment');
    Route::post('/{company}/investment-properties/{investmentProperty}/capitalise', [\App\Http\Controllers\InvestmentPropertyController::class, 'capitalise'])->name('investment-properties.capitalise');
    Route::post('/{company}/investment-properties/{investmentProperty}/dispose', [\App\Http\Controllers\InvestmentPropertyController::class, 'dispose'])->name('investment-properties.dispose');
    Route::get('/{company}/investment-properties/{investmentProperty}/history', [\App\Http\Controllers\InvestmentPropertyController::class, 'history'])->name('investment-properties.history');
    Route::post('/{company}/investment-properties/{investmentProperty}/events/{event}/retry-posting', [\App\Http\Controllers\InvestmentPropertyController::class, 'retryPosting'])->name('investment-properties.events.retry-posting');

    // Biological assets (IAS 41)
    Route::get('/{company}/biological-assets', [\App\Http\Controllers\BiologicalAssetController::class, 'index'])->name('biological-assets.index');
    Route::post('/{company}/biological-assets/classes', [\App\Http\Controllers\BiologicalAssetController::class, 'storeClass'])->name('biological-assets.classes.store');
    Route::delete('/{company}/biological-assets/classes/{biologicalAssetClass}', [\App\Http\Controllers\BiologicalAssetController::class, 'destroyClass'])->name('biological-assets.classes.destroy');
    Route::post('/{company}/biological-assets', [\App\Http\Controllers\BiologicalAssetController::class, 'store'])->name('biological-assets.store');
    Route::get('/{company}/biological-assets/{biologicalAsset}', [\App\Http\Controllers\BiologicalAssetController::class, 'show'])->name('biological-assets.show');
    Route::patch('/{company}/biological-assets/{biologicalAsset}', [\App\Http\Controllers\BiologicalAssetController::class, 'update'])->name('biological-assets.update');
    Route::delete('/{company}/biological-assets/{biologicalAsset}', [\App\Http\Controllers\BiologicalAssetController::class, 'destroy'])->name('biological-assets.destroy');
    Route::post('/{company}/biological-assets/{biologicalAsset}/fair-value-adjust', [\App\Http\Controllers\BiologicalAssetController::class, 'fairValueAdjust'])->name('biological-assets.fair-value-adjust');
    Route::post('/{company}/biological-assets/{biologicalAsset}/harvest', [\App\Http\Controllers\BiologicalAssetController::class, 'harvest'])->name('biological-assets.harvest');
    Route::post('/{company}/biological-assets/{biologicalAsset}/natural-increase', [\App\Http\Controllers\BiologicalAssetController::class, 'naturalIncrease'])->name('biological-assets.natural-increase');
    Route::post('/{company}/biological-assets/{biologicalAsset}/mortality', [\App\Http\Controllers\BiologicalAssetController::class, 'mortality'])->name('biological-assets.mortality');
    Route::post('/{company}/biological-assets/{biologicalAsset}/dispose', [\App\Http\Controllers\BiologicalAssetController::class, 'dispose'])->name('biological-assets.dispose');
    Route::get('/{company}/biological-assets/{biologicalAsset}/history', [\App\Http\Controllers\BiologicalAssetController::class, 'history'])->name('biological-assets.history');
    Route::post('/{company}/biological-assets/{biologicalAsset}/events/{event}/retry-posting', [\App\Http\Controllers\BiologicalAssetController::class, 'retryPosting'])->name('biological-assets.events.retry-posting');

    // ECL Register (IFRS 9)
    Route::get('/{company}/ecl-register', [\App\Http\Controllers\EclRegisterController::class, 'index'])->name('ecl-register.index');
    Route::get('/{company}/ecl-register/{date}', [\App\Http\Controllers\EclRegisterController::class, 'show'])->name('ecl-register.show')->where('date', '\d{4}-\d{2}-\d{2}');
    Route::post('/{company}/ecl-register/rates', [\App\Http\Controllers\EclRegisterController::class, 'updateRates'])->name('ecl-register.rates');
    Route::post('/{company}/ecl-register/post', [\App\Http\Controllers\EclRegisterController::class, 'postProvision'])->name('ecl-register.post');

    // Provisions (IAS 37)
    Route::get('/{company}/provisions', [\App\Http\Controllers\ProvisionController::class, 'index'])->name('provisions.index');
    Route::post('/{company}/provisions/classes', [\App\Http\Controllers\ProvisionController::class, 'storeClass'])->name('provisions.classes.store');
    Route::delete('/{company}/provisions/classes/{provisionClass}', [\App\Http\Controllers\ProvisionController::class, 'destroyClass'])->name('provisions.classes.destroy');
    Route::post('/{company}/provisions', [\App\Http\Controllers\ProvisionController::class, 'store'])->name('provisions.store');
    Route::get('/{company}/provisions/{provision}', [\App\Http\Controllers\ProvisionController::class, 'show'])->name('provisions.show');
    Route::patch('/{company}/provisions/{provision}', [\App\Http\Controllers\ProvisionController::class, 'update'])->name('provisions.update');
    Route::delete('/{company}/provisions/{provision}', [\App\Http\Controllers\ProvisionController::class, 'destroy'])->name('provisions.destroy');
    Route::post('/{company}/provisions/{provision}/remeasure', [\App\Http\Controllers\ProvisionController::class, 'remeasure'])->name('provisions.remeasure');
    Route::post('/{company}/provisions/{provision}/unwind', [\App\Http\Controllers\ProvisionController::class, 'unwind'])->name('provisions.unwind');
    Route::post('/{company}/provisions/{provision}/utilise', [\App\Http\Controllers\ProvisionController::class, 'utilise'])->name('provisions.utilise');
    Route::post('/{company}/provisions/{provision}/reverse', [\App\Http\Controllers\ProvisionController::class, 'reverse'])->name('provisions.reverse');
    Route::get('/{company}/provisions/{provision}/history', [\App\Http\Controllers\ProvisionController::class, 'history'])->name('provisions.history');
    Route::post('/{company}/provisions/{provision}/events/{event}/retry-posting', [\App\Http\Controllers\ProvisionController::class, 'retryPosting'])->name('provisions.events.retry-posting');

    // Related Parties (IAS 24)
    Route::get('/{company}/related-parties', [\App\Http\Controllers\RelatedPartyController::class, 'index'])->name('related-parties.index');
    Route::post('/{company}/related-parties', [\App\Http\Controllers\RelatedPartyController::class, 'store'])->name('related-parties.store');
    Route::get('/{company}/related-parties/{relatedParty}', [\App\Http\Controllers\RelatedPartyController::class, 'show'])->name('related-parties.show');
    Route::patch('/{company}/related-parties/{relatedParty}', [\App\Http\Controllers\RelatedPartyController::class, 'update'])->name('related-parties.update');
    Route::delete('/{company}/related-parties/{relatedParty}', [\App\Http\Controllers\RelatedPartyController::class, 'destroy'])->name('related-parties.destroy');
    Route::post('/{company}/related-parties/{relatedParty}/transactions', [\App\Http\Controllers\RelatedPartyController::class, 'storeTransaction'])->name('related-parties.transactions.store');
    Route::delete('/{company}/related-parties/{relatedParty}/transactions/{transaction}', [\App\Http\Controllers\RelatedPartyController::class, 'destroyTransaction'])->name('related-parties.transactions.destroy');

    // Borrowing Costs (IAS 23)
    Route::get('/{company}/borrowing-costs', [\App\Http\Controllers\BorrowingCostController::class, 'index'])->name('borrowing-costs.index');
    Route::post('/{company}/borrowing-costs', [\App\Http\Controllers\BorrowingCostController::class, 'store'])->name('borrowing-costs.store');
    Route::get('/{company}/borrowing-costs/{borrowingCostCapitalisation}', [\App\Http\Controllers\BorrowingCostController::class, 'show'])->name('borrowing-costs.show');
    Route::patch('/{company}/borrowing-costs/{borrowingCostCapitalisation}', [\App\Http\Controllers\BorrowingCostController::class, 'update'])->name('borrowing-costs.update');
    Route::delete('/{company}/borrowing-costs/{borrowingCostCapitalisation}', [\App\Http\Controllers\BorrowingCostController::class, 'destroy'])->name('borrowing-costs.destroy');
    Route::post('/{company}/borrowing-costs/{borrowingCostCapitalisation}/capitalise', [\App\Http\Controllers\BorrowingCostController::class, 'capitalise'])->name('borrowing-costs.capitalise');
    Route::post('/{company}/borrowing-costs/{borrowingCostCapitalisation}/suspend', [\App\Http\Controllers\BorrowingCostController::class, 'suspend'])->name('borrowing-costs.suspend');
    Route::post('/{company}/borrowing-costs/{borrowingCostCapitalisation}/complete', [\App\Http\Controllers\BorrowingCostController::class, 'complete'])->name('borrowing-costs.complete');
    Route::get('/{company}/borrowing-costs/{borrowingCostCapitalisation}/history', [\App\Http\Controllers\BorrowingCostController::class, 'history'])->name('borrowing-costs.history');
    Route::post('/{company}/borrowing-costs/{borrowingCostCapitalisation}/events/{event}/retry-posting', [\App\Http\Controllers\BorrowingCostController::class, 'retryPosting'])->name('borrowing-costs.events.retry-posting');

    // Revenue Contracts (IFRS 15)
    Route::get('/{company}/revenue-contracts', [\App\Http\Controllers\RevenueContractController::class, 'index'])->name('revenue-contracts.index');
    Route::post('/{company}/revenue-contracts', [\App\Http\Controllers\RevenueContractController::class, 'store'])->name('revenue-contracts.store');
    Route::get('/{company}/revenue-contracts/{revenueContract}', [\App\Http\Controllers\RevenueContractController::class, 'show'])->name('revenue-contracts.show');
    Route::patch('/{company}/revenue-contracts/{revenueContract}', [\App\Http\Controllers\RevenueContractController::class, 'update'])->name('revenue-contracts.update');
    Route::delete('/{company}/revenue-contracts/{revenueContract}', [\App\Http\Controllers\RevenueContractController::class, 'destroy'])->name('revenue-contracts.destroy');
    Route::post('/{company}/revenue-contracts/{revenueContract}/obligations', [\App\Http\Controllers\RevenueContractController::class, 'storeObligation'])->name('revenue-contracts.obligations.store');
    Route::delete('/{company}/revenue-contracts/{revenueContract}/obligations/{obligation}', [\App\Http\Controllers\RevenueContractController::class, 'destroyObligation'])->name('revenue-contracts.obligations.destroy');
    Route::post('/{company}/revenue-contracts/{revenueContract}/recognise-revenue', [\App\Http\Controllers\RevenueContractController::class, 'recogniseRevenue'])->name('revenue-contracts.recognise-revenue');
    Route::post('/{company}/revenue-contracts/{revenueContract}/advance-receipt', [\App\Http\Controllers\RevenueContractController::class, 'advanceReceipt'])->name('revenue-contracts.advance-receipt');
    Route::post('/{company}/revenue-contracts/{revenueContract}/release-liability', [\App\Http\Controllers\RevenueContractController::class, 'releaseLiability'])->name('revenue-contracts.release-liability');
    Route::get('/{company}/revenue-contracts/{revenueContract}/history', [\App\Http\Controllers\RevenueContractController::class, 'history'])->name('revenue-contracts.history');
    Route::post('/{company}/revenue-contracts/{revenueContract}/events/{event}/retry-posting', [\App\Http\Controllers\RevenueContractController::class, 'retryPosting'])->name('revenue-contracts.events.retry-posting');

    // Government Grants (IAS 20)
    Route::get('/{company}/government-grants', [\App\Http\Controllers\GovernmentGrantController::class, 'index'])->name('government-grants.index');
    Route::post('/{company}/government-grants', [\App\Http\Controllers\GovernmentGrantController::class, 'store'])->name('government-grants.store');
    Route::get('/{company}/government-grants/{governmentGrant}', [\App\Http\Controllers\GovernmentGrantController::class, 'show'])->name('government-grants.show');
    Route::patch('/{company}/government-grants/{governmentGrant}', [\App\Http\Controllers\GovernmentGrantController::class, 'update'])->name('government-grants.update');
    Route::delete('/{company}/government-grants/{governmentGrant}', [\App\Http\Controllers\GovernmentGrantController::class, 'destroy'])->name('government-grants.destroy');
    Route::post('/{company}/government-grants/{governmentGrant}/recognise', [\App\Http\Controllers\GovernmentGrantController::class, 'recognise'])->name('government-grants.recognise');
    Route::post('/{company}/government-grants/{governmentGrant}/amortise', [\App\Http\Controllers\GovernmentGrantController::class, 'amortise'])->name('government-grants.amortise');
    Route::post('/{company}/government-grants/{governmentGrant}/refund', [\App\Http\Controllers\GovernmentGrantController::class, 'refund'])->name('government-grants.refund');
    Route::get('/{company}/government-grants/{governmentGrant}/history', [\App\Http\Controllers\GovernmentGrantController::class, 'history'])->name('government-grants.history');
    Route::post('/{company}/government-grants/{governmentGrant}/events/{event}/retry-posting', [\App\Http\Controllers\GovernmentGrantController::class, 'retryPosting'])->name('government-grants.events.retry-posting');

    // Share-Based Payments (IFRS 2)
    Route::get('/{company}/share-based-payments', [\App\Http\Controllers\ShareBasedPaymentController::class, 'index'])->name('share-based-payments.index');
    Route::post('/{company}/share-based-payments', [\App\Http\Controllers\ShareBasedPaymentController::class, 'store'])->name('share-based-payments.store');
    Route::get('/{company}/share-based-payments/{shareBasedPaymentArrangement}', [\App\Http\Controllers\ShareBasedPaymentController::class, 'show'])->name('share-based-payments.show');
    Route::patch('/{company}/share-based-payments/{shareBasedPaymentArrangement}', [\App\Http\Controllers\ShareBasedPaymentController::class, 'update'])->name('share-based-payments.update');
    Route::delete('/{company}/share-based-payments/{shareBasedPaymentArrangement}', [\App\Http\Controllers\ShareBasedPaymentController::class, 'destroy'])->name('share-based-payments.destroy');
    Route::post('/{company}/share-based-payments/{shareBasedPaymentArrangement}/vesting-expense', [\App\Http\Controllers\ShareBasedPaymentController::class, 'vestingExpense'])->name('share-based-payments.vesting-expense');
    Route::post('/{company}/share-based-payments/{shareBasedPaymentArrangement}/exercise', [\App\Http\Controllers\ShareBasedPaymentController::class, 'exercise'])->name('share-based-payments.exercise');
    Route::post('/{company}/share-based-payments/{shareBasedPaymentArrangement}/forfeit', [\App\Http\Controllers\ShareBasedPaymentController::class, 'forfeit'])->name('share-based-payments.forfeit');
    Route::get('/{company}/share-based-payments/{shareBasedPaymentArrangement}/history', [\App\Http\Controllers\ShareBasedPaymentController::class, 'history'])->name('share-based-payments.history');
    Route::post('/{company}/share-based-payments/{shareBasedPaymentArrangement}/events/{event}/retry-posting', [\App\Http\Controllers\ShareBasedPaymentController::class, 'retryPosting'])->name('share-based-payments.events.retry-posting');

    // Deferred Tax (IAS 12)
    Route::get('/{company}/deferred-tax', [\App\Http\Controllers\DeferredTaxController::class, 'index'])->name('deferred-tax.index');
    Route::post('/{company}/deferred-tax', [\App\Http\Controllers\DeferredTaxController::class, 'store'])->name('deferred-tax.store');
    Route::get('/{company}/deferred-tax/{deferredTaxItem}', [\App\Http\Controllers\DeferredTaxController::class, 'show'])->name('deferred-tax.show');
    Route::patch('/{company}/deferred-tax/{deferredTaxItem}', [\App\Http\Controllers\DeferredTaxController::class, 'update'])->name('deferred-tax.update');
    Route::delete('/{company}/deferred-tax/{deferredTaxItem}', [\App\Http\Controllers\DeferredTaxController::class, 'destroy'])->name('deferred-tax.destroy');
    Route::post('/{company}/deferred-tax/{deferredTaxItem}/remeasure', [\App\Http\Controllers\DeferredTaxController::class, 'remeasure'])->name('deferred-tax.remeasure');
    Route::post('/{company}/deferred-tax/{deferredTaxItem}/reverse', [\App\Http\Controllers\DeferredTaxController::class, 'reverse'])->name('deferred-tax.reverse');
    Route::get('/{company}/deferred-tax/{deferredTaxItem}/history', [\App\Http\Controllers\DeferredTaxController::class, 'history'])->name('deferred-tax.history');
    Route::post('/{company}/deferred-tax/{deferredTaxItem}/events/{event}/retry-posting', [\App\Http\Controllers\DeferredTaxController::class, 'retryPosting'])->name('deferred-tax.events.retry-posting');

    // Notes to Annual Financial Statements
    Route::prefix('/{company}/notes-to-afs')->name('notes-to-afs.')->scopeBindings()->group(function () {
        Route::get('/', [\App\Http\Controllers\FinancialStatementNotesController::class, 'index'])->name('index');
        Route::get('/{note}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'show'])->name('show');
        Route::patch('/{note}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'updateText'])->name('update');
        Route::patch('/{note}/include', [\App\Http\Controllers\FinancialStatementNotesController::class, 'toggleIncludeInAfs'])->name('include.toggle');

        // PPE class management (Note 5)
        Route::post('/ppe/classes', [\App\Http\Controllers\FinancialStatementNotesController::class, 'storePpeClass'])->name('ppe.classes.store');
        Route::patch('/ppe/classes/{ppeClass}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'updatePpeClass'])->name('ppe.classes.update');
        Route::patch('/ppe/classes/{ppeClass}/links', [\App\Http\Controllers\FinancialStatementNotesController::class, 'updatePpeClassLinks'])->name('ppe.classes.links.update');
        Route::delete('/ppe/classes/{ppeClass}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'destroyPpeClass'])->name('ppe.classes.destroy');

        // Intangible class management (Note 6)
        Route::post('/intangible/classes', [\App\Http\Controllers\FinancialStatementNotesController::class, 'storeIntangibleClass'])->name('intangible.classes.store');
        Route::patch('/intangible/classes/{intangibleClass}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'updateIntangibleClass'])->name('intangible.classes.update');
        Route::delete('/intangible/classes/{intangibleClass}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'destroyIntangibleClass'])->name('intangible.classes.destroy');

        // Per-note figure account links (figures table on a note)
        Route::post('/{note}/lines', [\App\Http\Controllers\FinancialStatementNotesController::class, 'storeNoteLine'])->name('lines.store');
        Route::delete('/{note}/lines/{line}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'destroyNoteLine'])->name('lines.destroy');

        // Asset register (Note 5)
        Route::post('/assets', [\App\Http\Controllers\FinancialStatementNotesController::class, 'storeAsset'])->name('assets.store');
        Route::patch('/assets/{asset}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'updateAsset'])->name('assets.update');
        Route::delete('/assets/{asset}', [\App\Http\Controllers\FinancialStatementNotesController::class, 'destroyAsset'])->name('assets.destroy');
    });

    // Payroll
    Route::prefix('/{company}/payroll')->name('payroll.')->group(function () {
        // Components
        Route::get('/components', [\App\Http\Controllers\PayrollController::class, 'components'])->name('components');
        Route::post('/components', [\App\Http\Controllers\PayrollController::class, 'storeComponent'])->name('components.store');
        Route::patch('/components/{component}', [\App\Http\Controllers\PayrollController::class, 'updateComponent'])->name('components.update');
        Route::delete('/components/{component}', [\App\Http\Controllers\PayrollController::class, 'destroyComponent'])->name('components.destroy');

        // Employees
        Route::get('/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [\App\Http\Controllers\EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [\App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/edit', [\App\Http\Controllers\EmployeeController::class, 'edit'])->name('employees.edit');
        Route::patch('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy');

        // Runs
        Route::get('/runs', [\App\Http\Controllers\PayrollController::class, 'runs'])->name('runs.index');
        Route::get('/runs/create', [\App\Http\Controllers\PayrollController::class, 'createRun'])->name('runs.create');
        Route::post('/runs', [\App\Http\Controllers\PayrollController::class, 'storeRun'])->name('runs.store');
        Route::get('/runs/{run}', [\App\Http\Controllers\PayrollController::class, 'showRun'])->name('runs.show');
        Route::post('/runs/{run}/recalculate', [\App\Http\Controllers\PayrollController::class, 'recalculateRun'])->name('runs.recalculate');
        Route::post('/runs/{run}/post', [\App\Http\Controllers\PayrollController::class, 'postRun'])->name('runs.post');
        Route::delete('/runs/{run}', [\App\Http\Controllers\PayrollController::class, 'destroyRun'])->name('runs.destroy');

        // Payslip PDF + adjustment
        Route::get('/runs/{run}/payslips/{payslip}/pdf', [\App\Http\Controllers\PayrollController::class, 'payslipPdf'])->name('payslip.pdf');
        Route::patch('/runs/{run}/payslips/{payslip}/adjust', [\App\Http\Controllers\PayrollController::class, 'adjustPayslip'])->name('payslip.adjust');
        Route::patch('/runs/{run}/hours-worksheet', [\App\Http\Controllers\PayrollController::class, 'updateHoursWorksheet'])->name('runs.hours-worksheet');

        // EMP201
        Route::get('/runs/{run}/emp201', [\App\Http\Controllers\PayrollController::class, 'emp201'])->name('emp201');

        // IRP5/IT3(a) Tax Certificates
        Route::get('/irp5', [\App\Http\Controllers\Irp5Controller::class, 'index'])->name('irp5.index');
        Route::get('/irp5/{employee}', [\App\Http\Controllers\Irp5Controller::class, 'show'])->name('irp5.show');
        Route::get('/irp5/{employee}/pdf', [\App\Http\Controllers\Irp5Controller::class, 'pdf'])->name('irp5.pdf');
        Route::get('/irp5-bulk-pdf', [\App\Http\Controllers\Irp5Controller::class, 'bulkPdf'])->name('irp5.bulk-pdf');

        // EMP501 Annual Reconciliation
        Route::get('/emp501', [\App\Http\Controllers\Irp5Controller::class, 'emp501'])->name('emp501.index');
        Route::get('/emp501/pdf', [\App\Http\Controllers\Irp5Controller::class, 'emp501Pdf'])->name('emp501.pdf');
    });

    // Supplier Invoices
    Route::prefix('/{company}/supplier-invoices')->name('supplier-invoices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SupplierInvoiceController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SupplierInvoiceController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SupplierInvoiceController::class, 'store'])->name('store');
        Route::get('/{supplierInvoice}', [\App\Http\Controllers\SupplierInvoiceController::class, 'show'])->name('show');
        Route::get('/{supplierInvoice}/edit', [\App\Http\Controllers\SupplierInvoiceController::class, 'edit'])->name('edit');
        Route::put('/{supplierInvoice}', [\App\Http\Controllers\SupplierInvoiceController::class, 'update'])->name('update');
        Route::patch('/{supplierInvoice}/status', [\App\Http\Controllers\SupplierInvoiceController::class, 'updateStatus'])->name('status');
        Route::get('/{supplierInvoice}/pdf', [\App\Http\Controllers\SupplierInvoiceController::class, 'pdf'])->name('pdf');
        Route::delete('/{supplierInvoice}', [\App\Http\Controllers\SupplierInvoiceController::class, 'destroy'])->name('destroy');
    });

    // Purchase Orders
    Route::prefix('/{company}/purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{purchaseOrder}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('show');
        Route::get('/{purchaseOrder}/edit', [\App\Http\Controllers\PurchaseOrderController::class, 'edit'])->name('edit');
        Route::put('/{purchaseOrder}', [\App\Http\Controllers\PurchaseOrderController::class, 'update'])->name('update');
        Route::patch('/{purchaseOrder}/status', [\App\Http\Controllers\PurchaseOrderController::class, 'updateStatus'])->name('status');
        Route::post('/{purchaseOrder}/convert', [\App\Http\Controllers\PurchaseOrderController::class, 'convertToInvoice'])->name('convert');
        Route::get('/{purchaseOrder}/pdf', [\App\Http\Controllers\PurchaseOrderController::class, 'pdf'])->name('pdf');
        Route::delete('/{purchaseOrder}', [\App\Http\Controllers\PurchaseOrderController::class, 'destroy'])->name('destroy');
    });

    // Group accounting (IFRS 10 consolidation)
    Route::prefix('/{company}/group')->name('group.')->group(function () {
        Route::get('/structure', [\App\Http\Controllers\GroupController::class, 'structure'])->name('structure');
        Route::get('/subsidiaries/{subsidiary}', [\App\Http\Controllers\GroupController::class, 'showSubsidiary'])->name('subsidiaries.show');
        Route::post('/enable', [\App\Http\Controllers\GroupController::class, 'enable'])->name('enable');
        Route::post('/subsidiaries', [\App\Http\Controllers\GroupController::class, 'addSubsidiary'])->name('subsidiaries.add');
        Route::patch('/subsidiaries/{subsidiary}', [\App\Http\Controllers\GroupController::class, 'updateSubsidiary'])->name('subsidiaries.update');
        Route::delete('/subsidiaries/{subsidiary}', [\App\Http\Controllers\GroupController::class, 'removeSubsidiary'])->name('subsidiaries.remove');
        Route::get('/balance-sheet', [\App\Http\Controllers\GroupController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/income-statement', [\App\Http\Controllers\GroupController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/ownership-events', [\App\Http\Controllers\GroupController::class, 'ownershipEvents'])->name('ownership-events');
        Route::post('/ownership-events', [\App\Http\Controllers\GroupController::class, 'storeOwnershipEvent'])->name('ownership-events.store');
        Route::delete('/ownership-events/{event}', [\App\Http\Controllers\GroupController::class, 'destroyOwnershipEvent'])->name('ownership-events.destroy');
        Route::get('/eliminations', [\App\Http\Controllers\GroupController::class, 'eliminations'])->name('eliminations');
        Route::post('/eliminations', [\App\Http\Controllers\GroupController::class, 'storeElimination'])->name('eliminations.store');
        Route::delete('/eliminations/{elimination}', [\App\Http\Controllers\GroupController::class, 'destroyElimination'])->name('eliminations.destroy');
    });

    Route::prefix('/{company}/reports')->name('reports.')->group(function () {
        Route::get('/income-statement', [\App\Http\Controllers\CompanyController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/income-statement/pdf', [\App\Http\Controllers\CompanyController::class, 'incomeStatementPdf'])->name('income-statement.pdf');
        Route::get('/cash-flow', [\App\Http\Controllers\CompanyController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/cash-flow/pdf', [\App\Http\Controllers\CompanyController::class, 'cashFlowPdf'])->name('cash-flow.pdf');
        Route::get('/balance-sheet', [\App\Http\Controllers\CompanyController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/balance-sheet/pdf', [\App\Http\Controllers\CompanyController::class, 'balanceSheetPdf'])->name('balance-sheet.pdf');
        Route::get('/changes-in-equity', [\App\Http\Controllers\CompanyController::class, 'changesInEquity'])->name('changes-in-equity');
        Route::get('/changes-in-equity/pdf', [\App\Http\Controllers\CompanyController::class, 'changesInEquityPdf'])->name('changes-in-equity.pdf');
        Route::get('/annual-financial-statements/pdf', [\App\Http\Controllers\CompanyController::class, 'afsBundlePdf'])->name('afs-bundle.pdf');
        Route::get('/general-ledger', [\App\Http\Controllers\CompanyController::class, 'generalLedger'])->name('general-ledger');
        Route::get('/general-ledger/pdf', [\App\Http\Controllers\CompanyController::class, 'generalLedgerPdf'])->name('general-ledger.pdf');
        Route::get('/trial-balance', [\App\Http\Controllers\CompanyController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/trial-balance/pdf', [\App\Http\Controllers\CompanyController::class, 'trialBalancePdf'])->name('trial-balance.pdf');
        Route::get('/trial-balance/excel', [\App\Http\Controllers\CompanyController::class, 'trialBalanceExcel'])->name('trial-balance.excel');
        Route::post('/trial-balance/import', [\App\Http\Controllers\CompanyController::class, 'trialBalanceImport'])->name('trial-balance.import');
        Route::get('/registers/export', [\App\Http\Controllers\CompanyController::class, 'exportRegisters'])->name('registers.export');
        Route::get('/vat-return', [\App\Http\Controllers\CompanyController::class, 'vatReturn'])->name('vat-return');
        Route::get('/vat-return/pdf', [\App\Http\Controllers\CompanyController::class, 'vatReturnPdf'])->name('vat-return.pdf');
        Route::get('/age-analysis', [\App\Http\Controllers\CompanyController::class, 'ageAnalysis'])->name('age-analysis');
        Route::post('/age-analysis/ecl-rates', [\App\Http\Controllers\CompanyController::class, 'updateEclRates'])->name('age-analysis.ecl-rates');
    });

    Route::prefix('/{company}/actions')->name('actions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CompanyActionController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\CompanyActionController::class, 'export'])->name('export');
        Route::post('/', [\App\Http\Controllers\CompanyActionController::class, 'store'])->name('store');
        Route::patch('/{action}/resolve', [\App\Http\Controllers\CompanyActionController::class, 'resolve'])->name('resolve');
        Route::patch('/{action}/reopen', [\App\Http\Controllers\CompanyActionController::class, 'reopen'])->name('reopen');
        Route::delete('/{action}', [\App\Http\Controllers\CompanyActionController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

    Route::prefix('troubleshooting')->name('troubleshooting.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TroubleshootingController::class, 'index'])->name('index');
        Route::get('/feed', [\App\Http\Controllers\TroubleshootingController::class, 'feed'])->name('feed');
        Route::get('/logs-feed', [\App\Http\Controllers\TroubleshootingController::class, 'logsFeed'])->name('logs-feed');
        Route::post('/jobs/{id}/retry', [\App\Http\Controllers\TroubleshootingController::class, 'retryJob'])->name('jobs.retry');
        Route::delete('/jobs/{id}', [\App\Http\Controllers\TroubleshootingController::class, 'deleteJob'])->name('jobs.delete');
        Route::post('/jobs/retry-all', [\App\Http\Controllers\TroubleshootingController::class, 'retryAll'])->name('jobs.retry-all');
    });
});

Route::middleware('auth')->post(
    '/transactions/semantic-match',
    \App\Http\Controllers\TransactionMatchController::class
)->name('transactions.semantic-match');

// ── Subscriptions ────────────────────────────────────
Route::middleware('auth')->prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('plans');
    Route::post('/{plan}/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::get('/callback', [\App\Http\Controllers\SubscriptionController::class, 'callback'])->name('callback');
    Route::get('/manage', [\App\Http\Controllers\SubscriptionController::class, 'manage'])->name('manage');
    Route::post('/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
});

// ── Paystack Webhook (no auth, no CSRF — verified by signature) ──
Route::post('/webhooks/paystack', [\App\Http\Controllers\PaystackWebhookController::class, 'handle'])
    ->name('webhooks.paystack')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__ . '/auth.php';
