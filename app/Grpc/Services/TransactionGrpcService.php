<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\CreateTransactionRequest;
use App\Grpc\Generated\GetTransactionByReferenceRequest;
use App\Grpc\Generated\SearchTransactionsRequest;
use App\Grpc\Generated\TransactionResponse;
use App\Grpc\Generated\TransactionsResponse;
use App\Grpc\Generated\UpdateTransactionRequest;
use App\Grpc\GrpcDispatcher;
use Spiral\RoadRunner\GRPC\ContextInterface;

class TransactionGrpcService implements \App\Grpc\Interfaces\TransactionServiceInterface
{
    public function CreateTransaction(ContextInterface $ctx, CreateTransactionRequest $request): TransactionResponse
    {
        $data = json_decode($request->getLinesJson(), true);
        $data['company_id'] = $request->getCompanyId();
        $data['transaction_date'] = $request->getTransactionDate();
        $data['description'] = $request->getDescription();
        $data['reference'] = $request->getReference();
        $data['status'] = $request->getStatus() ?: 'draft';
        $data['notes'] = $request->getNotes();

        $result = app(GrpcDispatcher::class)->dispatch('POST', '/graphql', [
            'query' => 'mutation CreateTransaction($input: CreateTransactionInput!) { createTransaction(input: $input) { id reference description } }',
            'variables' => ['input' => $data],
        ]);
        return (new TransactionResponse())->setData($result);
    }

    public function UpdateTransaction(ContextInterface $ctx, UpdateTransactionRequest $request): TransactionResponse
    {
        $data = json_decode($request->getLinesJson(), true) ?? [];
        if ($request->getTransactionDate()) $data['transaction_date'] = $request->getTransactionDate();
        if ($request->getDescription()) $data['description'] = $request->getDescription();
        if ($request->getReference()) $data['reference'] = $request->getReference();
        if ($request->getStatus()) $data['status'] = $request->getStatus();
        if ($request->getNotes()) $data['notes'] = $request->getNotes();

        $result = app(GrpcDispatcher::class)->dispatch('PATCH', '/api/transactions/'.$request->getTransactionId(), $data);
        return (new TransactionResponse())->setData($result);
    }

    public function SearchTransactions(ContextInterface $ctx, SearchTransactionsRequest $request): TransactionsResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/transactions/search', [
            'company_id' => $request->getCompanyId(),
            'query' => $request->getQuery(),
            'limit' => $request->getLimit() ?: 20,
        ]);
        return (new TransactionsResponse())->setData($result);
    }

    public function GetTransactionByReference(ContextInterface $ctx, GetTransactionByReferenceRequest $request): TransactionResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/transactions/search', [
            'company_id' => $request->getCompanyId(),
            'query' => $request->getReference(),
            'limit' => 1,
        ]);
        return (new TransactionResponse())->setData($result);
    }
}
