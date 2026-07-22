<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\CreateInvoiceRequest;
use App\Grpc\Generated\DocumentsResponse;
use App\Grpc\Generated\GetInvoicesRequest;
use App\Grpc\Generated\InvoiceResponse;
use App\Grpc\Generated\InvoicesResponse;
use App\Grpc\Generated\SearchDocumentsRequest;
use App\Grpc\GrpcDispatcher;
use Spiral\RoadRunner\GRPC\ContextInterface;

class InvoiceGrpcService implements \App\Grpc\Interfaces\InvoiceServiceInterface
{
    public function GetInvoices(ContextInterface $ctx, GetInvoicesRequest $request): InvoicesResponse
    {
        $vars = ['company_id' => $request->getCompanyId()];
        if ($request->getStatus()) $vars['status'] = $request->getStatus();
        if ($request->getSearch()) $vars['search'] = $request->getSearch();

        $result = app(GrpcDispatcher::class)->dispatch('POST', '/graphql', [
            'query' => 'query($company_id: ID!) { invoices(company_id: $company_id) { data { id invoice_number customer_name status total } } }',
            'variables' => $vars,
        ]);
        return (new InvoicesResponse())->setData($result);
    }

    public function CreateInvoice(ContextInterface $ctx, CreateInvoiceRequest $request): InvoiceResponse
    {
        $data = json_decode($request->getDataJson(), true);
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/graphql', [
            'query' => 'mutation($input: CreateInvoiceInput!) { createInvoice(input: $input) { id invoice_number } }',
            'variables' => ['input' => $data],
        ]);
        return (new InvoiceResponse())->setData($result)->setMessage('Invoice created.');
    }

    public function SearchDocuments(ContextInterface $ctx, SearchDocumentsRequest $request): DocumentsResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/documents/search', [
            'company_id' => $request->getCompanyId(),
            'query' => $request->getQuery(),
            'limit' => $request->getLimit() ?: 10,
        ]);
        return (new DocumentsResponse())->setData($result);
    }

    public function GetQuotations(ContextInterface $ctx, GetInvoicesRequest $request): InvoicesResponse
    {
        $vars = ['company_id' => $request->getCompanyId()];
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/graphql', [
            'query' => 'query($company_id: ID!) { quotations(company_id: $company_id) { data { id invoice_number customer_name status total } } }',
            'variables' => $vars,
        ]);
        return (new InvoicesResponse())->setData($result);
    }

    public function CreateQuotation(ContextInterface $ctx, CreateInvoiceRequest $request): InvoiceResponse
    {
        $data = json_decode($request->getDataJson(), true);
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/graphql', [
            'query' => 'mutation($input: CreateQuotationInput!) { createQuotation(input: $input) { id invoice_number } }',
            'variables' => ['input' => $data],
        ]);
        return (new InvoiceResponse())->setData($result)->setMessage('Quotation created.');
    }
}
