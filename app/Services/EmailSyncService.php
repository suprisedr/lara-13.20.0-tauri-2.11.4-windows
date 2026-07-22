<?php

namespace App\Services;

use App\Ai\Agents\EmailSummaryAgent;
use App\Enums\SupplierEmailStatus;
use App\Events\NewEmailSynced;
use App\Models\CompanyEmailAccount;
use App\Models\Supplier;
use App\Models\SupplierEmail;
use App\Models\SupplierEmailAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class EmailSyncService
{
    public function syncAccount(CompanyEmailAccount $account): int
    {
        $account->update(['last_error' => null]);

        try {
            $client = $this->buildImapClient($account);
            $client->connect();

            $folder = $client->getFolder('INBOX');
            $query = $folder->query();

            if ($account->last_synced_at) {
                $query->since($account->last_synced_at->subMinutes(5));
            } else {
                $query->since(now()->subDays(30));
            }

            $messages = $query->get();
            $synced = 0;
            $newEmails = [];

            foreach ($messages as $message) {
                $messageId = $message->getMessageId()?->toString() ?? $message->getUid();
                if (!$messageId) {
                    continue;
                }

                $exists = SupplierEmail::where('company_email_account_id', $account->id)
                    ->where('message_id', $messageId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $fromAddress = $message->getFrom()?->first()?->mail ?? '';
                $fromName = $message->getFrom()?->first()?->personal ?? '';

                if (!$fromAddress) {
                    continue;
                }

                $supplierEmail = $this->processEmail(
                    $account,
                    $messageId,
                    $fromAddress,
                    $fromName,
                    $message->getSubject()?->toString() ?? '',
                    $message->getTextBody() ?? $message->getHTMLBody() ?? '',
                    $message->getDate()?->toDate() ?? now(),
                );

                $this->processAttachments($supplierEmail, $message, $account);

                $newEmails[] = $supplierEmail;
                $synced++;
            }

            $client->disconnect();

            $account->update(['last_synced_at' => now()]);

            foreach ($newEmails as $email) {
                $this->runAiProcessing($email);
            }

            return $synced;
        } catch (ConnectionFailedException $e) {
            $account->update(['last_error' => 'Connection failed: ' . $e->getMessage()]);
            Log::error('Email sync connection failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'SSL') || str_contains($e->getMessage(), 'fread') || str_contains($e->getMessage(), 'Connection reset')) {
                $account->update(['last_error' => 'SSL connection reset by mail server']);
                Log::warning('Email sync SSL reset', ['account_id' => $account->id, 'error' => $e->getMessage()]);
                throw new ConnectionFailedException('SSL connection was reset by the mail server.', 0, $e);
            }
            $account->update(['last_error' => $e->getMessage()]);
            Log::error('Email sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $account->update(['last_error' => $e->getMessage()]);
            Log::error('Email sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function processEmail(
        CompanyEmailAccount $account,
        string $messageId,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $bodyText,
        \DateTimeInterface $receivedAt,
    ): SupplierEmail {
        $company = $account->company;
        $supplier = $company->suppliers()
            ->where('email', $fromEmail)
            ->first();

        $status = $supplier
            ? SupplierEmailStatus::Matched
            : SupplierEmailStatus::NewSupplier;

        $supplierEmail = SupplierEmail::create([
            'company_email_account_id' => $account->id,
            'supplier_id' => $supplier?->id,
            'message_id' => $messageId,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'subject' => $subject,
            'body_text' => $bodyText,
            'received_at' => $receivedAt,
            'status' => $status,
            'is_reviewed' => $status === SupplierEmailStatus::Matched,
        ]);

        event(NewEmailSynced::fromSupplierEmail($supplierEmail, $company->id));

        return $supplierEmail;
    }

    private function runAiProcessing(SupplierEmail $email): void
    {
        $this->generateSummary($email);

        if ($email->attachments()->exists()) {
            $this->extractDocuments($email);
        }
    }

    private function generateSummary(SupplierEmail $email): void
    {
        $truncatedBody = mb_substr($email->body_text ?? '', 0, 4000);
        $prompt = "From: {$email->from_name} <{$email->from_email}>\nSubject: {$email->subject}\n\n{$truncatedBody}";

        $delay = 10;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = EmailSummaryAgent::make()->prompt($prompt);
                $email->update(['ai_summary' => $response['summary'] ?? null]);

                return;
            } catch (ProviderOverloadedException|RateLimitedException $e) {
                Log::info("Summary retry {$attempt}/3 for email {$email->id}, waiting {$delay}s");

                if ($attempt === 3) {
                    Log::warning('Email summary failed after retries', ['email_id' => $email->id, 'error' => $e->getMessage()]);

                    return;
                }

                sleep($delay);
                $delay *= 2;
            } catch (\Throwable $e) {
                Log::warning('Email summary generation failed', ['email_id' => $email->id, 'error' => $e->getMessage()]);

                return;
            }
        }
    }

    private function processAttachments(SupplierEmail $supplierEmail, $message, CompanyEmailAccount $account): void
    {
        $attachments = $message->getAttachments();
        $companyId = $account->company_id;

        foreach ($attachments as $attachment) {
            $filename = $attachment->getName() ?? 'attachment';
            $mimeType = $attachment->getMimeType() ?? 'application/octet-stream';
            $content = $attachment->getContent();

            if (!$content) {
                continue;
            }

            $storagePath = "supplier-attachments/{$companyId}/{$supplierEmail->id}";
            $safeFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            $fullPath = $storagePath . '/' . $safeFilename;

            Storage::disk('local')->put($fullPath, $content);

            SupplierEmailAttachment::create([
                'supplier_email_id' => $supplierEmail->id,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'file_path' => $fullPath,
                'file_size' => strlen($content),
            ]);
        }
    }

    public function processSingleMessage(CompanyEmailAccount $account, $message): void
    {
        $messageId = $message->getMessageId()?->toString() ?? $message->getUid();
        if (!$messageId) {
            return;
        }

        $exists = SupplierEmail::where('company_email_account_id', $account->id)
            ->where('message_id', $messageId)
            ->exists();

        if ($exists) {
            return;
        }

        $fromAddress = $message->getFrom()?->first()?->mail ?? '';
        $fromName = $message->getFrom()?->first()?->personal ?? '';

        if (!$fromAddress) {
            return;
        }

        $supplierEmail = $this->processEmail(
            $account,
            $messageId,
            $fromAddress,
            $fromName,
            $message->getSubject()?->toString() ?? '',
            $message->getTextBody() ?? $message->getHTMLBody() ?? '',
            $message->getDate()?->toDate() ?? now(),
        );

        $this->processAttachments($supplierEmail, $message, $account);

        $account->update(['last_synced_at' => now()]);

        $this->runAiProcessing($supplierEmail);
    }

    private function extractDocuments(SupplierEmail $email): void
    {
        try {
            app(SupplierDocumentService::class)->extractAndCreate($email);
        } catch (\Throwable $e) {
            Log::warning('Document extraction failed', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function testConnection(CompanyEmailAccount|array $config): bool
    {
        try {
            if ($config instanceof CompanyEmailAccount) {
                $client = $this->buildImapClient($config);
            } else {
                $cm = new ClientManager();
                $protocol = ($config['provider'] ?? 'imap') === 'pop' ? 'pop3' : 'imap';
                $client = $cm->make([
                    'host' => $config['imap_host'],
                    'port' => (int) $config['imap_port'],
                    'encryption' => $config['imap_encryption'] ?? 'ssl',
                    'validate_cert' => true,
                    'username' => $config['imap_username'] ?? $config['email_address'],
                    'password' => $config['imap_password'],
                    'protocol' => $protocol,
                    'timeout' => 15,
                ]);
            }

            $client->connect();
            $client->disconnect();

            return true;
        } catch (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'SSL') || str_contains($e->getMessage(), 'fread') || str_contains($e->getMessage(), 'Connection reset')) {
                throw new ConnectionFailedException("SSL connection was reset by the mail server. Check your host, port, and encryption settings.", 0, $e);
            }
            throw $e;
        }
    }

    public function buildImapClient(CompanyEmailAccount $account): \Webklex\PHPIMAP\Client
    {
        $cm = new ClientManager();

        if ($account->provider->usesOAuth()) {
            return $cm->make([
                'host' => $account->getEffectiveImapHost(),
                'port' => $account->getEffectiveImapPort(),
                'encryption' => 'ssl',
                'validate_cert' => true,
                'username' => $account->email_address,
                'password' => $account->oauth_token,
                'protocol' => 'imap',
                'authentication' => 'oauth',
            ]);
        }

        return $cm->make([
            'host' => $account->getEffectiveImapHost(),
            'port' => $account->getEffectiveImapPort(),
            'encryption' => $account->imap_encryption ?? 'ssl',
            'validate_cert' => true,
            'username' => $account->imap_username ?? $account->email_address,
            'password' => $account->imap_password,
            'protocol' => $account->provider === \App\Enums\EmailProvider::Pop ? 'pop3' : 'imap',
        ]);
    }
}
