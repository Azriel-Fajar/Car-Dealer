export type QueryParams = Record<
    string,
    string | number | boolean | null | undefined
>;

/**
 * Drop empty/null/false values so index URLs stay clean; numbers are
 * stringified and `true` becomes `'1'`.
 */
export function cleanQuery(params: QueryParams): Record<string, string> {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(params)) {
        if (
            value === null ||
            value === undefined ||
            value === false ||
            value === ''
        ) {
            continue;
        }

        query[key] = value === true ? '1' : String(value);
    }

    return query;
}
