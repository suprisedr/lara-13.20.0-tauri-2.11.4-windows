<?php

namespace App\Services;

use App\Ai\Agents\InvoiceExtractionAgent;
use App\Models\PurchaseOrder;
use App\Models\SupplierEmail;
use App\Models\SupplierInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Files\StoredDocument;
use Laravel\Ai\Files\StoredImage;

class SupplierDocumentService
{
    public function extractAndCreate(SupplierEmail $email): void
    {
        $email->loadMissing(['attachments', 'emailAccount']);

        $company = $email->emailAccount->company;

        $prompt = $this->buildPrompt($email);
        $attachments = $this->buildAttachments($email);

        $response = $this->promptWithRetry($prompt, $attachments, $email->id);

        if (!$response) {
            return;
        }

        $documents = $response['documents'] ?? [];

        foreach ($documents as $doc) {
            $type = $doc['document_type'] ?? 'none';

            if ($type === 'none') {
                continue;
            }

            if ($type === 'invoice') {
                $this->createSupplierInvoice($company, $email, $doc);
            } elseif ($type === 'purchase_order') {
                $this->createPurchaseOrder($company, $email, $doc);
            }
        }
    }

    private function promptWithRetry(string $prompt, array $attachments, int $emailId, int $maxAttempts = 3): mixed
    {
        $delay = 10;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return InvoiceExtractionAgent::make()->prompt($prompt, $attachments);
            } catch (ProviderOverloadedException|RateLimitedException $e) {
                Log::info("Extraction retry {$attempt}/{$maxAttempts} for email {$emailId}, waiting {$delay}s", [
                    'error' => $e->getMessage(),
                ]);

                if ($attempt === $maxAttempts) {
                    Log::warning('Supplier document extraction failed after retries', [
                        'email_id' => $emailId,
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }

                sleep($delay);
                $delay *= 2;
            } catch (\Throwable $e) {
                Log::warning('Supplier document extraction failed', [
                    'email_id' => $emailId,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }

    private function buildPrompt(SupplierEmail $email): string
    {
        $body = mb_substr($email->body_text ?? '', 0, 8000);

        $attachmentNote = '';
        if ($email->attachments->isNotEmpty()) {
            $names = $email->attachments->pluck('filename')->implode(', ');
            $attachmentNote = "\n\nAttachments ({$email->attachments->count()}): {$names}";
        }

        return "From: {$email->from_name} <{$email->from_email}>\nSubject: {$email->subject}{$attachmentNote}\n\n{$body}";
    }

    private function buildAttachments(SupplierEmail $email): array
    {
        $files = [];

        foreach ($email->attachments as $attachment) {
            if (!Storage::disk('local')->exists($attachment->file_path)) {
                continue;
            }

            $fullPath = Storage::disk('local')->path($attachment->file_path);
            $mime = $attachment->mime_type ?? '';

            if (str_starts_with($mime, 'image/')) {
                $files[] = new StoredImage($attachment->file_path, 'local');
            } elseif (
                str_contains($mime, 'pdf') ||
                str_contains($mime, 'spreadsheet') ||
                str_contains($mime, 'csv') ||
                str_contains($mime, 'text/')
            ) {
                $files[] = new StoredDocument($attachment->file_path, 'local');
            }
        }

        return $files;
    }

    private function createSupplierInvoice($company, SupplierEmail $email, array $doc): SupplierInvoice
    {
        $invoice = SupplierInvoice::create([
            'company_id' => $company->id,
            'supplier_id' => $email->supplier_id,
            'supplier_email_id' => $email->id,
            'invoice_number' => $doc['document_number'] ?? null,
            'invoice_date' => $this->parseDate($doc['document_date'] ?? null),
            'due_date' => $this->parseDate($doc['due_date'] ?? null),
            'currency' => $doc['currency'] ?? 'ZAR',
            'subtotal' => $doc['subtotal'] ?? 0,
            'tax_total' => $doc['tax_total'] ?? 0,
            'total' => $doc['total'] ?? 0,
            'status' => 'draft',
            'notes' => $doc['notes'] ?? null,
            'ai_raw_extraction' => json_encode($doc),
        ]);

        foreach ($doc['line_items'] ?? [] as $item) {
            $invoice->items()->create([
                'description' => $item['description'] ?? 'Line item',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? null,
            ]);
        }

        return $invoice;
    }

    private function createPurchaseOrder($company, SupplierEmail $email, array $doc): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'company_id' => $company->id,
            'supplier_id' => $email->supplier_id,
            'supplier_email_id' => $email->id,
            'po_number' => $doc['document_number'] ?? null,
            'order_date' => $this->parseDate($doc['document_date'] ?? null),
            'expected_delivery_date' => $this->parseDate($doc['due_date'] ?? null),
            'currency' => $doc['currency'] ?? 'ZAR',
            'subtotal' => $doc['subtotal'] ?? 0,
            'tax_total' => $doc['tax_total'] ?? 0,
            'total' => $doc['total'] ?? 0,
            'status' => 'draft',
            'notes' => $doc['notes'] ?? null,
            'ai_raw_extraction' => json_encode($doc),
        ]);

        foreach ($doc['line_items'] ?? [] as $item) {
            $po->items()->create([
                'description' => $item['description'] ?? 'Line item',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? null,
            ]);
        }

        return $po;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
