import { getJwtTokenExpirationDate } from '../helper/jwt';

export class ApiClient {
    public async fetch(path: string, options?: RequestInit): Promise<Response> {
        let token = localStorage.getItem('authToken');
        let fetchNewToken = false;

        if (token === null) {
            fetchNewToken = true;
        } else {
            const tokenExpiration = getJwtTokenExpirationDate(token);

            // Refresh if the token is expired or about to expire in the next 3 minutes
            const tokenCutoff = new Date();
            tokenCutoff.setMinutes(tokenCutoff.getMinutes() + 3);

            if (tokenExpiration === null || tokenExpiration <= tokenCutoff) {
                fetchNewToken = true;
            }
        }

        if (fetchNewToken) {
            token = await this.getAndSaveNewToken();
        }

        return fetch(path, {
            ...options,
            headers: {
                ...options?.headers,
                Authorization: `Bearer ${token}`,
            },
        });
    }

    private async getAndSaveNewToken(): Promise<string | null> {
        const loginResponse = await fetch('/api/login', {
            method: 'POST',
        });

        const data = await loginResponse.json();
        localStorage.setItem('authToken', data.token);

        return data.token;
    }
}
