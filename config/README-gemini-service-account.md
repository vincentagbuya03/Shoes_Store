# Service Account for Gemini / Generative API

Place a Google Cloud Service Account JSON file at `config/service-account.json` (recommended) so the server can mint OAuth tokens and call the Generative Language API securely.

Steps

1. Create a service account in Google Cloud Console.
   - Go to IAM & Admin → Service Accounts → Create Service Account.
   - Grant it the `Cloud API` or `Cloud Platform` scope (or a role that allows access to the Generative API).
2. Create a JSON key for the service account and download the key file.
3. Place the JSON file at `config/service-account.json` in this project (do not commit it to source control).

Configuration

- `config/gemini.php` already points to `config/service-account.json` by default. You can override with:
  - `'service_account_file' => '/absolute/path/to/key.json'` or
  - `'service_account_json' => '<paste json here>'` (not recommended for long-term storage).

How it works

- The server creates a signed JWT with the service account's private key and exchanges it for an OAuth access token at `https://oauth2.googleapis.com/token`.
- The access token is cached in `storage/gemini_sa_token.json` until it is near expiry.

Testing

After placing the file, restart your PHP server (Laragon/Apache) if necessary and run:

```powershell
Invoke-RestMethod -Uri 'http://localhost/Shoes_Store/chat.php' -Method Post -Body (@{ message = 'shipping time' } | ConvertTo-Json) -ContentType 'application/json'
```

Troubleshooting

- If the server still returns a fallback reply, check `storage/ai-provider.log` for token errors and request URLs.
- Ensure the Generative Language API (Generative AI) is enabled in the project that owns the service account.
- Ensure the service account has the needed permissions and is not restricted by organization policy.

Security

- Keep service account JSON files out of source control. Add `config/service-account.json` to `.gitignore` if needed.
