export class ApiClient {
    // Todo:
    // 1. Check token lifetime and refresh if expired
    // 2. Handle login failures gracefully
    // 3. Add error handling for fetch requests
    // 4. Implement retry logic for failed requests
    public static async fetch(path: string, options?: RequestInit): Promise<Response> {
        let token = localStorage.getItem('authToken');
        if (token === null) {
            const loginResponse = await fetch('/api/login', {
                method: 'POST',
            });
            const newToken = (await loginResponse.json()).token;
            if (!newToken) {
                throw new Error('Login failed');
            }

            localStorage.setItem('authToken', newToken);
            token = newToken;
        }

        return fetch(path, {
            ...options,
            headers: {
                ...options?.headers,
                Authorization: `Bearer ${token}`,
            },
        });
    }
}
