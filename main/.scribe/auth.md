# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`Bearer {TOKEN}`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Set the Authorization header to <code>Bearer {TOKEN}</code>. If you are using Sanctum, first fetch a CSRF cookie from <code>/sanctum/csrf-cookie</code>, then include your token in the header. Ensure CORS is enabled.
