import { describe, expect, it } from 'vitest';

import { cn } from './classes';

describe('cn', () => {
    it('merges conflicting Tailwind classes, the last one wins', () => {
        expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4');
    });

    it('skips falsy values', () => {
        expect(cn('text-sm', false && 'hidden', undefined, 'font-bold')).toBe('text-sm font-bold');
    });
});
