export function getJwtTokenExpirationDate(token: string): Date | null {
    const parts = token.split('.');
    if (parts.length !== 3) {
        return null;
    }

    try {
        const payload = JSON.parse(atob(parts[1]));
        if (payload.exp && !Number.isNaN(payload.exp)) {
            return new Date(payload.exp * 1000);
        }
    } catch (_e) {
        return null;
    }

    return null;
}
