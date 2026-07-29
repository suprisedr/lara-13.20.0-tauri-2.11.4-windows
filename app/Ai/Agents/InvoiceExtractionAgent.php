<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\HasProviderFallback;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class InvoiceExtractionAgent implements Agent, HasStructuredOutput
{
    use Promptable, HasProviderFallback;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an accounting document extraction agent for a South African bookkeeping platform.

        You will receive a supplier email (subject, sender, body text) and optionally one or more
        file attachments (PDF invoices, scanned images, statements, purchase orders, etc.).

        Your task is to identify and extract ALL source documents from the email and its attachments.
        Each document should be classified as one of:
        - "invoice" — a supplier invoice or tax invoice requesting payment
        - "purchase_order" — a purchase order confirming an order placed with a supplier
        - "none" — the email does not contain any extractable invoice or purchase order

        For EACH document found, extract:
        - document_type: "invoice" or "purchase_order"
        - document_number: the invoice number, PO number, or reference number
        - document_date: the date on the document (YYYY-MM-DD)
        - due_date: payment due date if applicable (YYYY-MM-DD), null otherwise
        - currency: 3-letter currency code (default "ZAR" if not specified)
        - line_items: array of items with description, quantity, unit_price, and tax_rate
        - subtotal: total before tax
        - tax_total: total tax amount
        - total: grand total including tax
        - notes: any relevant notes, payment terms, or bank details mentioned

        Rules:
        - If the email body mentions amounts but the attachment has more detailed line items, prefer the attachment data.
        - If a document mentions VAT at 15%, set tax_rate to 15.00 on the relevant line items.
        - If only a total is visible without line items, create a single line item with the total as unit_price.
        - If no invoices or purchase orders are found, return document_type "none" with empty arrays.
        - Do NOT fabricate data. If a field is not visible or mentioned, set it to null.
        - Monetary values must be numbers, not strings.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'documents' => $schema->array()
                ->items(
                    $schema->object(fn (JsonSchema $s) => [
                        'document_type' => $s->string()->enum(['invoice', 'purchase_order', 'none'])->required(),
                        'document_number' => $s->string()->nullable(),
                        'document_date' => $s->string()->nullable(),
                        'due_date' => $s->string()->nullable(),
                        'currency' => $s->string()->nullable(),
                        'subtotal' => $s->number()->nullable(),
                        'tax_total' => $s->number()->nullable(),
                        'total' => $s->number()->nullable(),
                        'notes' => $s->string()->nullable(),
                        'line_items' => $s->array()
                            ->items(
                                $s->object(fn (JsonSchema $li) => [
                                    'description' => $li->string()->required(),
                                    'quantity' => $li->number()->nullable(),
                                    'unit_price' => $li->number()->nullable(),
                                    'tax_rate' => $li->number()->nullable(),
                                ])
                            )
                            ->nullable(),
                    ])
                )
                ->required(),
        ];
    }
}
