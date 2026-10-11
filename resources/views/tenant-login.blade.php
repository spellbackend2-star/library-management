<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tenant login</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: #f3f6fb; color: #182230; }
        main { width: min(100%, 420px); padding: 32px; border: 1px solid #e3e8ef; border-radius: 16px; background: #fff; box-shadow: 0 18px 48px #162b4d12; }
        h1 { margin: 0 0 8px; font-size: 1.65rem; }
        p { margin: 0 0 24px; color: #667085; }
        label { display: block; margin: 16px 0 7px; font-size: .9rem; font-weight: 600; }
        input { width: 100%; padding: 12px 13px; border: 1px solid #cfd7e3; border-radius: 8px; font: inherit; }
        input:focus { outline: 3px solid #dbeafe; border-color: #2563eb; }
        button { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 8px; background: #1d4ed8; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        button:disabled { opacity: .65; cursor: wait; }
        #message { min-height: 1.4em; margin: 16px 0 0; font-size: .92rem; }
        #message[data-state="error"] { color: #b42318; }
        #message[data-state="success"] { color: #067647; }
    </style>
</head>
<body>
    <main>
        <h1>Tenant login</h1>
        <p>Sign in to <strong>{{ tenant('company_name') }}</strong>.</p>
        <form id="login-form">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="username" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button id="submit-button" type="submit">Sign in</button>
            <div id="message" role="status" aria-live="polite"></div>
        </form>
    </main>
    <script>
        const form = document.getElementById('login-form');
        const button = document.getElementById('submit-button');
        const message = document.getElementById('message');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            button.disabled = true;
            message.dataset.state = '';
            message.textContent = 'Signing in…';

            try {
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: form.elements.email.value,
                        password: form.elements.password.value,
                    }),
                });
                const result = await response.json();

                if (!response.ok || !result?.data?.access_token) {
                    throw new Error(result.message || result.error_description || 'Login failed. Check your email and password.');
                }

                sessionStorage.setItem('tenant_access_token', result.data.access_token);
                sessionStorage.setItem('tenant_user', JSON.stringify(result.data.user));
                message.dataset.state = 'success';
                message.textContent = `Signed in as ${result.data.user?.name || result.data.user?.email || 'tenant user'}.`;
            } catch (error) {
                message.dataset.state = 'error';
                message.textContent = error.message || 'Unable to connect. Please try again.';
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>
</html>
