<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\{ChartOfAccountResponse, ChartOfAccountsRequest, ChartOfAccountsResponse, CompanyRequest, CompanyResponse, CreateChartOfAccountRequest, SuggestChartOfAccountRequest, TrialBalanceRequest, TrialBalanceResponse, VatStatusResponse};
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface CompanyServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.CompanyService';

    public function GetCompany(ContextInterface $ctx, CompanyRequest $request): CompanyResponse;
    public function GetChartOfAccounts(ContextInterface $ctx, ChartOfAccountsRequest $request): ChartOfAccountsResponse;
    public function CreateChartOfAccount(ContextInterface $ctx, CreateChartOfAccountRequest $request): ChartOfAccountResponse;
    public function SuggestChartOfAccount(ContextInterface $ctx, SuggestChartOfAccountRequest $request): ChartOfAccountResponse;
    public function GetTrialBalance(ContextInterface $ctx, TrialBalanceRequest $request): TrialBalanceResponse;
    public function GetVatRegistrationStatus(ContextInterface $ctx, CompanyRequest $request): VatStatusResponse;
}
