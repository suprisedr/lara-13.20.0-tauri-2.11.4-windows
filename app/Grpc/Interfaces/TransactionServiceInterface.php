<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\{CreateTransactionRequest, GetTransactionByReferenceRequest, SearchTransactionsRequest, TransactionResponse, TransactionsResponse, UpdateTransactionRequest};
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface TransactionServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.TransactionService';

    public function CreateTransaction(ContextInterface $ctx, CreateTransactionRequest $request): TransactionResponse;
    public function UpdateTransaction(ContextInterface $ctx, UpdateTransactionRequest $request): TransactionResponse;
    public function SearchTransactions(ContextInterface $ctx, SearchTransactionsRequest $request): TransactionsResponse;
    public function GetTransactionByReference(ContextInterface $ctx, GetTransactionByReferenceRequest $request): TransactionResponse;
}
