import { describe, expect, it } from 'vitest';
import { safeRedirect } from './redirect';

describe('safeRedirect', () => {
    it.each(['/', '/trash', '/tasks/12?tab=details'])('allows the in-app path %s', (path) => {
        expect(safeRedirect(path)).toBe(path);
    });

    it.each([
        ['protocol-relative', '//evil.example'],
        ['backslash trick', '/\\evil.example'],
        ['absolute url', 'https://evil.example'],
        ['javascript url', 'javascript:alert(1)'],
        ['missing', undefined],
        ['array from repeated query param', ['/a', '/b']],
    ])('falls back for a %s target', (_, target) => {
        expect(safeRedirect(target)).toBe('/');
    });
});
