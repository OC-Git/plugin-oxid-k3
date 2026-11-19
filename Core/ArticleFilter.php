<?php

namespace ObjectCode\K3\Core;

/**
 * Article filters of the export endpoint ("Filter für Artikel" in K3)
 *
 * K3 appends the filter text as query string to the export URL. Same filters
 * as the K3 plugins for JTL, Shopware and PrestaShop:
 *  - reference     article number, * as wildcard, e.g. reference=ZAUN-*
 *  - name          part of the article title
 *  - category      id or title of a category the article is assigned to
 *  - manufacturer  id or title of the manufacturer
 * All filters must match (AND), several values separated by comma (OR).
 * Unknown filters are rejected, so a misspelled filter never exports all
 * articles. Without a filter the K3 attribute decides as before.
 */
class ArticleFilter
{
    public const FILTERS = ['reference', 'name', 'category', 'manufacturer'];

    /**
     * Parameters OXID itself uses in the request
     */
    protected const OXID_PARAMS = ['cl', 'fnc', 'shp', 'lang', 'cur', 'force_sid', 'sid', 'stoken', 'actshop'];

    /**
     * @param array $query usually $_GET
     * @return array<string, string[]> filter name => values
     * @throws \InvalidArgumentException on an unknown filter
     */
    public function parse(array $query): array
    {
        $filters = [];
        foreach ($query as $key => $value) {
            if (in_array($key, self::OXID_PARAMS, true)) {
                continue;
            }
            if (!in_array($key, self::FILTERS, true) || !is_string($value)) {
                throw new \InvalidArgumentException(
                    "Unknown filter '$key'. Allowed: " . implode(', ', self::FILTERS)
                );
            }
            $values = array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
            if ($values) {
                $filters[$key] = $values;
            }
        }
        return $filters;
    }
}
