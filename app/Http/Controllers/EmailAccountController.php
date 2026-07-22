<?php

namespace App\Http\Controllers;

use App\Enums\EmailProvider;
use App\Enums\SupplierEmailStatus;
use App\Models\Company;
use App\Models\CompanyEmailAccount;
use App\Models\Supplier;
use App\Models\SupplierEmail;
use App\Services\EmailSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use League\OAuth2\Client\Provider\Google;
use TheNetworg\OAuth2\Client\Provider\Azure;

class EmailAccountController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $accounts = $company->emailAccounts()->latest()->get();

        return view('companies.email-accounts.index', compact('company', 'accounts'));
    }

    public function create(Company $company): View|RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        if ($company->emailAccounts()->exists()) {
            return redirect()
                ->route('companies.email-accounts.index', $company)
                ->with('error', 'Only one email account can be linked per company. Disconnect the existing account first.');
        }

        $providers = EmailProvider::cases();

        return view('companies.email-accounts.create', compact('company', 'providers'));
    }

    public function store(Company $company, Request $request, EmailSyncService $syncService): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        if ($company->emailAccounts()->exists()) {
            return redirect()
                ->route('companies.email-accounts.index', $company)
                ->with('error', 'Only one email account can be linked per company. Disconnect the existing account first.');
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:imap,pop'],
            'email_address' => ['required', 'email', 'max:255'],
            'imap_host' => ['required', 'string', 'max:255'],
            'imap_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'imap_encryption' => ['required', 'string', 'in:ssl,tls,none'],
            'imap_username' => ['required', 'string', 'max:255'],
            'imap_password' => ['required', 'string', 'max:500'],
        ]);

        try {
            $syncService->testConnection($validated);
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput($request->except('imap_password'))
                ->with('error', 'Connection failed: ' . $e->getMessage());
        }

        $company->emailAccounts()->create($validated);

        return redirect()
            ->route('companies.email-accounts.index', $company)
            ->with('success', 'Email account linked. New emails will be synced automatically.');
    }

    public function redirectToOAuth(Company $company, string $provider): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless(in_array($provider, ['gmail', 'outlook']), 404);

        if ($company->emailAccounts()->exists()) {
            return redirect()
                ->route('companies.email-accounts.index', $company)
                ->with('error', 'Only one email account can be linked per company. Disconnect the existing account first.');
        }

        $oauthProvider = $this->getOAuthProvider($provider, $company);
        $authUrl = $oauthProvider->getAuthorizationUrl([
            'scope' => $this->getOAuthScopes($provider),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        session([
            'oauth_state' => $oauthProvider->getState(),
            'oauth_provider' => $provider,
            'oauth_company' => $company->id,
        ]);

        return redirect($authUrl);
    }

    public function handleOAuthCallback(Company $company, string $provider, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless(in_array($provider, ['gmail', 'outlook']), 404);

        if ($request->input('state') !== session('oauth_state')) {
            return redirect()
                ->route('companies.email-accounts.index', $company)
                ->with('error', 'OAuth state mismatch. Please try again.');
        }

        try {
            $oauthProvider = $this->getOAuthProvider($provider, $company);
            $token = $oauthProvider->getAccessToken('authorization_code', [
                'code' => $request->input('code'),
            ]);

            $ownerDetails = $oauthProvider->getResourceOwner($token);
            $email = $provider === 'gmail'
                ? $ownerDetails->getEmail()
                : $ownerDetails->claim('email') ?? $ownerDetails->claim('preferred_username');

            $emailProvider = $provider === 'gmail' ? EmailProvider::Gmail : EmailProvider::Outlook;

            $account = $company->emailAccounts()->updateOrCreate(
                ['email_address' => $email, 'company_id' => $company->id],
                [
                    'provider' => $emailProvider,
                    'oauth_token' => $token->getToken(),
                    'oauth_refresh_token' => $token->getRefreshToken(),
                    'oauth_expires_at' => $token->getExpires()
                        ? \Carbon\Carbon::createFromTimestamp($token->getExpires())
                        : null,
                    'imap_host' => $emailProvider->defaultImapHost(),
                    'imap_port' => $emailProvider->defaultImapPort(),
                    'imap_encryption' => 'ssl',
                    'is_active' => true,
                ]
            );

            session()->forget(['oauth_state', 'oauth_provider', 'oauth_company']);

            return redirect()
                ->route('companies.email-accounts.index', $company)
                ->with('success', "Connected {$emailProvider->label()} account: {$email}. New emails will be synced automatically.");
        } catch (\Throwable $e) {
            return redirect()
                ->route('companies.email-accounts.index', $company)
                ->with('error', 'OAuth failed: ' . $e->getMessage());
        }
    }

    public function destroy(Company $company, CompanyEmailAccount $account): RedirectResponse
    {
        abort_unless($company->id === $account->company_id && $company->user_id === auth()->id(), 403);

        $account->delete();

        return redirect()
            ->route('companies.email-accounts.index', $company)
            ->with('success', 'Email account disconnected.');
    }

    public function updateSyncInterval(Company $company, CompanyEmailAccount $account, Request $request): RedirectResponse
    {
        abort_unless($company->id === $account->company_id && $company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'sync_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $account->update($validated);

        return redirect()
            ->route('companies.email-accounts.emails', [$company, $account])
            ->with('success', 'Sync interval updated to ' . $validated['sync_interval_minutes'] . ' minutes.');
    }

    public function emails(Company $company, CompanyEmailAccount $account, Request $request): View
    {
        abort_unless($company->id === $account->company_id && $company->user_id === auth()->id(), 403);

        $query = $account->supplierEmails()->with(['supplier', 'attachments']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $emails = $query->orderByDesc('received_at')->paginate(25);

        return view('companies.email-accounts.emails', compact('company', 'account', 'emails'));
    }

    public function reviewEmail(Company $company, SupplierEmail $email, Request $request): RedirectResponse
    {
        $account = $email->emailAccount;
        abort_unless($company->id === $account->company_id && $company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'action' => ['required', 'in:confirm,reassign,dismiss'],
        ]);

        if ($validated['action'] === 'confirm') {
            $supplier = $email->supplier;

            if (!$supplier) {
                $supplier = $company->suppliers()->create([
                    'name' => $email->from_name ?: explode('@', $email->from_email)[0],
                    'email' => $email->from_email,
                    'is_active' => true,
                    'is_confirmed' => true,
                ]);
            } else {
                $supplier->update(['is_confirmed' => true]);
            }

            $email->update([
                'supplier_id' => $supplier->id,
                'is_reviewed' => true,
                'status' => SupplierEmailStatus::Matched,
            ]);

            SupplierEmail::where('company_email_account_id', $account->id)
                ->where('from_email', $email->from_email)
                ->whereNull('supplier_id')
                ->update(['supplier_id' => $supplier->id, 'status' => SupplierEmailStatus::Matched]);
        } elseif ($validated['action'] === 'reassign' && !empty($validated['supplier_id'])) {
            $email->update([
                'supplier_id' => $validated['supplier_id'],
                'is_reviewed' => true,
                'status' => SupplierEmailStatus::Matched,
            ]);
        } elseif ($validated['action'] === 'dismiss') {
            $email->update(['is_reviewed' => true]);
        }

        return redirect()
            ->route('companies.email-accounts.emails', [$company, $account])
            ->with('success', 'Email reviewed.');
    }

    public function downloadAttachment(Company $company, \App\Models\SupplierEmailAttachment $attachment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $email = $attachment->supplierEmail;
        $account = $email->emailAccount;
        abort_unless($company->id === $account->company_id && $company->user_id === auth()->id(), 403);

        return Storage::disk('local')->download($attachment->file_path, $attachment->filename);
    }

    private function getOAuthProvider(string $provider, Company $company): Google|Azure
    {
        $callbackUrl = route('companies.email-accounts.oauth.callback', [$company, $provider]);

        if ($provider === 'gmail') {
            return new Google([
                'clientId' => config('services.google_mail.client_id'),
                'clientSecret' => config('services.google_mail.client_secret'),
                'redirectUri' => $callbackUrl,
            ]);
        }

        return new Azure([
            'clientId' => config('services.microsoft_mail.client_id'),
            'clientSecret' => config('services.microsoft_mail.client_secret'),
            'redirectUri' => $callbackUrl,
            'tenant' => config('services.microsoft_mail.tenant_id', 'common'),
            'defaultEndPointVersion' => '2.0',
        ]);
    }

    private function getOAuthScopes(string $provider): array
    {
        if ($provider === 'gmail') {
            return [
                'https://mail.google.com/',
                'email',
                'profile',
            ];
        }

        return [
            'https://outlook.office365.com/IMAP.AccessAsUser.All',
            'offline_access',
            'email',
            'profile',
            'openid',
        ];
    }
}
