import { getJwtTokenExpirationDate } from '../helper/jwt';

export class ApiClient {
    public async fetch(path: string, options?: RequestInit): Promise<Response> {
        const storedToken = localStorage.getItem('authToken');
        let token: string;

        if (storedToken === null) {
            token = await this.getAndSaveNewToken();
        } else {
            const tokenExpiration = getJwtTokenExpirationDate(storedToken);

            // Refresh if the token is expired or about to expire in the next 3 minutes
            const tokenCutoff = new Date();
            tokenCutoff.setMinutes(tokenCutoff.getMinutes() + 3);

            if (tokenExpiration === null || tokenExpiration <= tokenCutoff) {
                token = await this.getAndSaveNewToken();
            } else {
                token = storedToken;
            }
        }

        return fetch(path, {
            ...options,
            headers: {
                ...options?.headers,
                'X-Access-Token': token,
            },
        });
    }

    private async getAndSaveNewToken(): Promise<string> {
        const loginResponse = await fetch('/api/login', {
            method: 'POST',
        });

        const data = await loginResponse.json();
        localStorage.setItem('authToken', data.token);

        return data.token;
    }
}
