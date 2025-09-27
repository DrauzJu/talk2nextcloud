import { getJwtTokenExpirationDate } from "../helper/jwt";

export class ApiClient {
    public static async fetch(path: string, options?: RequestInit): Promise<Response> {
        let token = localStorage.getItem('authToken');
        let fetchNewToken = false;

        if (token === null) {
            fetchNewToken = true;
        } else {
            let tokenExpiration = getJwtTokenExpirationDate(token);

            // Refresh if the token is expired or about to expire in the next 3 minutes
            let tokenCutoff = new Date();
            tokenCutoff.setMinutes(tokenCutoff.getMinutes() + 3);

            if (tokenExpiration === null || tokenExpiration <= tokenCutoff) {
                fetchNewToken = true;
            }
        }

        if (fetchNewToken) {
            token = await ApiClient.getAndSaveNewToken();
        }

        return fetch(path, {
            ...options,
            headers: {
                ...options?.headers,
                Authorization: `Bearer ${token}`,
            },
        });
    }

    private static async getAndSaveNewToken(): Promise<string | null> {
        const loginResponse = await fetch('/api/login', {
            method: 'POST',
        });

        const data = await loginResponse.json();
        localStorage.setItem('authToken', data.token);

        return data.token;
    }
}
