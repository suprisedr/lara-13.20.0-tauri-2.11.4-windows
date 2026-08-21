<?php

use App\Http\Controllers\Api\AssetActionController;
use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\CompanyActionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransactionSearchController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [AuthTokenController::class, 'issue']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (\Illuminate\Http\Request $r) => $r->user()->only(['id', 'name', 'email']));
    Route::post('/transactions/search', TransactionSearchController::class);
    Route::patch('/transactions/{transaction}', [TransactionController::class, 'update']);
    Route::post('/documents/search', \App\Http\Controllers\Api\DocumentSearchController::class);

    // Row model for the four primary statements, rendered to Word by the desktop MCP server.
    // Bound by id explicitly: Company::getRouteKeyName() is 'slug', but every MCP
    // tool addresses companies by numeric id.
    Route::get('/companies/{company:id}/afs-model', [\App\Http\Controllers\CompanyController::class, 'afsStatementModel']);

    // Narrative + derived statistics for the business plan, rendered to Word by the same server.
    Route::get('/companies/{company:id}/business-plan-model', [\App\Http\Controllers\CompanyController::class, 'businessPlanModel']);

    // Period + year-to-date trading, position, cash flow and ratios, rendered to Excel.
    Route::get('/companies/{company:id}/management-accounts-model', [\App\Http\Controllers\CompanyController::class, 'managementAccountsModel']);

    Route::get('/assets', [\App\Http\Controllers\Api\AssetController::class, 'index']);
    Route::post('/assets', [\App\Http\Controllers\Api\AssetController::class, 'store']);
    Route::get('/assets/{asset}', [\App\Http\Controllers\Api\AssetController::class, 'show']);
    Route::patch('/assets/{asset}', [\App\Http\Controllers\Api\AssetController::class, 'update']);
    Route::post('/assets/{asset}/dispose', [\App\Http\Controllers\Api\AssetController::class, 'dispose']);
    Route::post('/assets/{asset}/revalue', [AssetActionController::class, 'revalue']);
    Route::post('/assets/{asset}/impair', [AssetActionController::class, 'impair']);
    Route::post('/assets/{asset}/reverse-impairment', [AssetActionController::class, 'reverseImpairment']);
    Route::post('/assets/{asset}/capitalise', [AssetActionController::class, 'capitalise']);
    Route::get('/assets/{asset}/depreciation-schedule', [\App\Http\Controllers\Api\AssetController::class, 'depreciationSchedule']);

    // Intangible assets (IAS 38)
    Route::get('/intangibles', [\App\Http\Controllers\Api\IntangibleAssetController::class, 'index']);
    Route::post('/intangibles', [\App\Http\Controllers\Api\IntangibleAssetController::class, 'store']);
    Route::get('/intangibles/{intangible}', [\App\Http\Controllers\Api\IntangibleAssetController::class, 'show']);
    Route::patch('/intangibles/{intangible}', [\App\Http\Controllers\Api\IntangibleAssetController::class, 'update']);
    Route::post('/intangibles/{intangible}/dispose',  [\App\Http\Controllers\Api\IntangibleAssetController::class, 'dispose']);
    Route::post('/intangibles/{intangible}/revalue',  [\App\Http\Controllers\Api\IntangibleAssetActionController::class, 'revalue']);
    Route::post('/intangibles/{intangible}/impair',   [\App\Http\Controllers\Api\IntangibleAssetActionController::class, 'impair']);
    Route::post('/intangibles/{intangible}/reverse-impairment', [\App\Http\Controllers\Api\IntangibleAssetActionController::class, 'reverseImpairment']);
    Route::post('/intangibles/{intangible}/capitalise', [\App\Http\Controllers\Api\IntangibleAssetActionController::class, 'capitalise']);

    // Inventory (IAS 2)
    Route::get('/inventory', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
    Route::post('/inventory', [\App\Http\Controllers\Api\InventoryController::class, 'store']);
    Route::get('/inventory/{inventoryItem}', [\App\Http\Controllers\Api\InventoryController::class, 'show']);
    Route::patch('/inventory/{inventoryItem}', [\App\Http\Controllers\Api\InventoryController::class, 'update']);
    Route::post('/inventory/{inventoryItem}/movement', [\App\Http\Controllers\Api\InventoryController::class, 'recordMovement']);
    Route::post('/inventory/{inventoryItem}/write-down', [\App\Http\Controllers\Api\InventoryController::class, 'writeDown']);
    Route::post('/inventory/{inventoryItem}/reverse-write-down', [\App\Http\Controllers\Api\InventoryController::class, 'reverseWriteDown']);

    // Leases (IFRS 16)
    Route::get('/leases', [\App\Http\Controllers\Api\LeaseController::class, 'index']);
    Route::post('/leases', [\App\Http\Controllers\Api\LeaseController::class, 'store']);
    Route::get('/leases/{lease}', [\App\Http\Controllers\Api\LeaseController::class, 'show']);
    Route::patch('/leases/{lease}', [\App\Http\Controllers\Api\LeaseController::class, 'update']);
    Route::post('/leases/{lease}/modify', [\App\Http\Controllers\Api\LeaseController::class, 'modify']);
    Route::post('/leases/{lease}/impair', [\App\Http\Controllers\Api\LeaseController::class, 'impair']);
    Route::post('/leases/{lease}/reverse-impairment', [\App\Http\Controllers\Api\LeaseController::class, 'reverseImpairment']);
    Route::post('/leases/{lease}/terminate', [\App\Http\Controllers\Api\LeaseController::class, 'terminate']);
    Route::get('/leases/{lease}/schedule', [\App\Http\Controllers\Api\LeaseController::class, 'schedule']);

    // Investment properties (IAS 40)
    Route::get('/investment-properties', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'index']);
    Route::post('/investment-properties', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'store']);
    Route::get('/investment-properties/{investmentProperty}', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'show']);
    Route::patch('/investment-properties/{investmentProperty}', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'update']);
    Route::post('/investment-properties/{investmentProperty}/fair-value-adjust', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'fairValueAdjust']);
    Route::post('/investment-properties/{investmentProperty}/impair', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'impair']);
    Route::post('/investment-properties/{investmentProperty}/reverse-impairment', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'reverseImpairment']);
    Route::post('/investment-properties/{investmentProperty}/capitalise', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'capitalise']);
    Route::post('/investment-properties/{investmentProperty}/dispose', [\App\Http\Controllers\Api\InvestmentPropertyController::class, 'dispose']);

    // Assets held for sale (IFRS 5)
    Route::get('/held-for-sale', [\App\Http\Controllers\Api\AssetHeldForSaleController::class, 'index']);
    Route::get('/held-for-sale/{heldForSale}', [\App\Http\Controllers\Api\AssetHeldForSaleController::class, 'show']);
    Route::post('/assets/{asset}/reclassify-held-for-sale', [\App\Http\Controllers\Api\AssetHeldForSaleController::class, 'reclassify']);
    Route::post('/held-for-sale/{heldForSale}/reverse', [\App\Http\Controllers\Api\AssetHeldForSaleController::class, 'reverse']);
    Route::post('/held-for-sale/{heldForSale}/dispose', [\App\Http\Controllers\Api\AssetHeldForSaleController::class, 'dispose']);

    // Biological assets (IAS 41)
    Route::get('/biological-assets', [\App\Http\Controllers\Api\BiologicalAssetController::class, 'index']);
    Route::post('/biological-assets', [\App\Http\Controllers\Api\BiologicalAssetController::class, 'store']);
    Route::get('/biological-assets/{biologicalAsset}', [\App\Http\Controllers\Api\BiologicalAssetController::class, 'show']);
    Route::patch('/biological-assets/{biologicalAsset}', [\App\Http\Controllers\Api\BiologicalAssetController::class, 'update']);
    Route::post('/biological-assets/{biologicalAsset}/fair-value-adjust', [\App\Http\Controllers\Api\BiologicalAssetController::class, 'fairValueAdjust']);
    Route::post('/biological-assets/{biologicalAsset}/harvest', [\App\Http\Controllers\Api\BiologicalAssetController::class, 'harvest']);
    Route::post('/biological-assets/{biologicalAsset}/dispose', [\App\Http\Controllers\Api\BiologicalAssetController::class, 'dispose']);

    // Provisions (IAS 37)
    Route::get('/provisions', [\App\Http\Controllers\Api\ProvisionController::class, 'index']);
    Route::post('/provisions', [\App\Http\Controllers\Api\ProvisionController::class, 'store']);
    Route::get('/provisions/{provision}', [\App\Http\Controllers\Api\ProvisionController::class, 'show']);
    Route::patch('/provisions/{provision}', [\App\Http\Controllers\Api\ProvisionController::class, 'update']);
    Route::post('/provisions/{provision}/remeasure', [\App\Http\Controllers\Api\ProvisionController::class, 'remeasure']);
    Route::post('/provisions/{provision}/utilise', [\App\Http\Controllers\Api\ProvisionController::class, 'utilise']);
    Route::post('/provisions/{provision}/reverse', [\App\Http\Controllers\Api\ProvisionController::class, 'reverse']);

    // Related Parties (IAS 24)
    Route::get('/related-parties', [\App\Http\Controllers\Api\RelatedPartyController::class, 'index']);
    Route::post('/related-parties', [\App\Http\Controllers\Api\RelatedPartyController::class, 'store']);
    Route::get('/related-parties/{relatedParty}', [\App\Http\Controllers\Api\RelatedPartyController::class, 'show']);
    Route::patch('/related-parties/{relatedParty}', [\App\Http\Controllers\Api\RelatedPartyController::class, 'update']);
    Route::post('/related-parties/{relatedParty}/transactions', [\App\Http\Controllers\Api\RelatedPartyController::class, 'storeTransaction']);

    // Borrowing Costs (IAS 23)
    Route::get('/borrowing-costs', [\App\Http\Controllers\Api\BorrowingCostController::class, 'index']);
    Route::post('/borrowing-costs', [\App\Http\Controllers\Api\BorrowingCostController::class, 'store']);
    Route::get('/borrowing-costs/{borrowingCostCapitalisation}', [\App\Http\Controllers\Api\BorrowingCostController::class, 'show']);
    Route::patch('/borrowing-costs/{borrowingCostCapitalisation}', [\App\Http\Controllers\Api\BorrowingCostController::class, 'update']);
    Route::post('/borrowing-costs/{borrowingCostCapitalisation}/capitalise', [\App\Http\Controllers\Api\BorrowingCostController::class, 'capitalise']);

    // Revenue Contracts (IFRS 15)
    Route::get('/revenue-contracts', [\App\Http\Controllers\Api\RevenueContractController::class, 'index']);
    Route::post('/revenue-contracts', [\App\Http\Controllers\Api\RevenueContractController::class, 'store']);
    Route::get('/revenue-contracts/{revenueContract}', [\App\Http\Controllers\Api\RevenueContractController::class, 'show']);
    Route::patch('/revenue-contracts/{revenueContract}', [\App\Http\Controllers\Api\RevenueContractController::class, 'update']);
    Route::post('/revenue-contracts/{revenueContract}/obligations', [\App\Http\Controllers\Api\RevenueContractController::class, 'storeObligation']);
    Route::post('/revenue-contracts/{revenueContract}/recognise-revenue', [\App\Http\Controllers\Api\RevenueContractController::class, 'recogniseRevenue']);
    Route::post('/revenue-contracts/{revenueContract}/advance-receipt', [\App\Http\Controllers\Api\RevenueContractController::class, 'advanceReceipt']);
    Route::post('/revenue-contracts/{revenueContract}/release-liability', [\App\Http\Controllers\Api\RevenueContractController::class, 'releaseLiability']);

    // Government Grants (IAS 20)
    Route::get('/government-grants', [\App\Http\Controllers\Api\GovernmentGrantController::class, 'index']);
    Route::post('/government-grants', [\App\Http\Controllers\Api\GovernmentGrantController::class, 'store']);
    Route::get('/government-grants/{governmentGrant}', [\App\Http\Controllers\Api\GovernmentGrantController::class, 'show']);
    Route::patch('/government-grants/{governmentGrant}', [\App\Http\Controllers\Api\GovernmentGrantController::class, 'update']);
    Route::post('/government-grants/{governmentGrant}/recognise', [\App\Http\Controllers\Api\GovernmentGrantController::class, 'recognise']);
    Route::post('/government-grants/{governmentGrant}/amortise', [\App\Http\Controllers\Api\GovernmentGrantController::class, 'amortise']);
    Route::post('/government-grants/{governmentGrant}/refund', [\App\Http\Controllers\Api\GovernmentGrantController::class, 'refund']);

    // Share-Based Payments (IFRS 2)
    Route::get('/share-based-payments', [\App\Http\Controllers\Api\ShareBasedPaymentController::class, 'index']);
    Route::post('/share-based-payments', [\App\Http\Controllers\Api\ShareBasedPaymentController::class, 'store']);
    Route::get('/share-based-payments/{shareBasedPaymentArrangement}', [\App\Http\Controllers\Api\ShareBasedPaymentController::class, 'show']);
    Route::patch('/share-based-payments/{shareBasedPaymentArrangement}', [\App\Http\Controllers\Api\ShareBasedPaymentController::class, 'update']);
    Route::post('/share-based-payments/{shareBasedPaymentArrangement}/vesting-expense', [\App\Http\Controllers\Api\ShareBasedPaymentController::class, 'vestingExpense']);
    Route::post('/share-based-payments/{shareBasedPaymentArrangement}/exercise', [\App\Http\Controllers\Api\ShareBasedPaymentController::class, 'exercise']);
    Route::post('/share-based-payments/{shareBasedPaymentArrangement}/forfeit', [\App\Http\Controllers\Api\ShareBasedPaymentController::class, 'forfeit']);

    // Deferred Tax (IAS 12)
    Route::get('/deferred-tax', [\App\Http\Controllers\Api\DeferredTaxController::class, 'index']);
    Route::post('/deferred-tax', [\App\Http\Controllers\Api\DeferredTaxController::class, 'store']);
    Route::get('/deferred-tax/{deferredTaxItem}', [\App\Http\Controllers\Api\DeferredTaxController::class, 'show']);
    Route::patch('/deferred-tax/{deferredTaxItem}', [\App\Http\Controllers\Api\DeferredTaxController::class, 'update']);
    Route::post('/deferred-tax/{deferredTaxItem}/remeasure', [\App\Http\Controllers\Api\DeferredTaxController::class, 'remeasure']);
    Route::post('/deferred-tax/{deferredTaxItem}/reverse', [\App\Http\Controllers\Api\DeferredTaxController::class, 'reverse']);

    Route::post('/actions', [CompanyActionController::class, 'store']);
    Route::patch('/actions/{action}', [CompanyActionController::class, 'update']);

    Route::delete('/auth/token', [AuthTokenController::class, 'revoke']);
});
