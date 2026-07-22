<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\ChartOfAccountResponse;
use App\Grpc\Generated\ChartOfAccountsRequest;
use App\Grpc\Generated\ChartOfAccountsResponse;
use App\Grpc\Generated\CompanyRequest;
use App\Grpc\Generated\CompanyResponse;
use App\Grpc\Generated\CreateChartOfAccountRequest;
use App\Grpc\Generated\SuggestChartOfAccountRequest;
use App\Grpc\Generated\TrialBalanceRequest;
use App\Grpc\Generated\TrialBalanceResponse;
use App\Grpc\Generated\VatStatusResponse;
use App\Grpc\GrpcDispatcher;
use Spiral\RoadRunner\GRPC\ContextInterface;

class CompanyGrpcService implements \App\Grpc\Interfaces\CompanyServiceInterface
{
    public function GetCompany(ContextInterface $ctx, CompanyRequest $request): CompanyResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/me');
        $response = new CompanyResponse();
        $response->setData($result);
        return $response;
    }

    public function GetChartOfAccounts(ContextInterface $ctx, ChartOfAccountsRequest $request): ChartOfAccountsResponse
    {
        $params = ['company_id' => $request->getCompanyId()];
        if ($request->getSearch()) $params['search'] = $request->getSearch();
        if ($request->getAccountType()) $params['account_type'] = $request->getAccountType();

        $result = app(GrpcDispatcher::class)->dispatch('GET', '/graphql', [
            'query' => 'query { chartOfAccounts(company_id: '.$request->getCompanyId().') { id account_code account_name account_type } }',
        ]);
        $response = new ChartOfAccountsResponse();
        $response->setData($result);
        return $response;
    }

    public function CreateChartOfAccount(ContextInterface $ctx, CreateChartOfAccountRequest $request): ChartOfAccountResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/graphql', [
            'query' => 'mutation { createChartOfAccount(input: {company_id: '.$request->getCompanyId().', account_code: "'.$request->getAccountCode().'", account_name: "'.$request->getAccountName().'", account_type: "'.$request->getAccountType().'"}) { id account_code account_name account_type } }',
        ]);
        $response = new ChartOfAccountResponse();
        $response->setData($result);
        return $response;
    }

    public function SuggestChartOfAccount(ContextInterface $ctx, SuggestChartOfAccountRequest $request): ChartOfAccountResponse
    {
        $response = new ChartOfAccountResponse();
        $response->setData('{}');
        return $response;
    }

    public function GetTrialBalance(ContextInterface $ctx, TrialBalanceRequest $request): TrialBalanceResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/graphql', [
            'query' => 'query { trialBalance(company_id: '.$request->getCompanyId().($request->getAsOfDate() ? ', as_of_date: "'.$request->getAsOfDate().'"' : '').') { accounts { id account_code account_name debit credit } } }',
        ]);
        $response = new TrialBalanceResponse();
        $response->setData($result);
        return $response;
    }

    public function GetVatRegistrationStatus(ContextInterface $ctx, CompanyRequest $request): VatStatusResponse
    {
        $response = new VatStatusResponse();
        $response->setData('{}');
        return $response;
    }
}
