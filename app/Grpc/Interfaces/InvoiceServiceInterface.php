<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\{CreateInvoiceRequest, DocumentsResponse, GetInvoicesRequest, InvoiceResponse, InvoicesResponse, SearchDocumentsRequest};
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface InvoiceServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.InvoiceService';

    public function GetInvoices(ContextInterface $ctx, GetInvoicesRequest $request): InvoicesResponse;
    public function CreateInvoice(ContextInterface $ctx, CreateInvoiceRequest $request): InvoiceResponse;
    public function SearchDocuments(ContextInterface $ctx, SearchDocumentsRequest $request): DocumentsResponse;
    public function GetQuotations(ContextInterface $ctx, GetInvoicesRequest $request): InvoicesResponse;
    public function CreateQuotation(ContextInterface $ctx, CreateInvoiceRequest $request): InvoiceResponse;
}
