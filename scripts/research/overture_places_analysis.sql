-- Camperwolf Overture Places research
-- Release: 2026-09-23.1
-- Scope: Germany, obvious camping-related place categories only.

INSTALL httpfs;
LOAD httpfs;
SET s3_region = 'us-west-2';

CREATE OR REPLACE TEMP TABLE cw_camping_places AS
SELECT
    id,
    names.primary AS name,
    basic_category,
    taxonomy.primary AS taxonomy_primary,
    taxonomy.hierarchy AS taxonomy_hierarchy,
    taxonomy.alternates AS taxonomy_alternates,
    confidence,
    operating_status,
    addresses,
    websites,
    emails,
    phones,
    socials,
    sources,
    bbox.xmin AS longitude,
    bbox.ymin AS latitude
FROM read_parquet(
    's3://overturemaps-us-west-2/release/2026-09-23.1/theme=places/type=place/*',
    hive_partitioning = 1
)
WHERE addresses[1].country = 'DE'
  AND taxonomy.primary IN ('campground', 'rv_park', 'holiday_park');

COPY (
    SELECT
        taxonomy_primary AS category,
        COUNT(*) AS records
    FROM cw_camping_places
    GROUP BY taxonomy_primary
    ORDER BY records DESC, category
) TO '01_category_counts.csv' WITH (HEADER, DELIMITER ',');

COPY (
    SELECT
        taxonomy_primary AS category,
        COALESCE(CAST(operating_status AS VARCHAR), 'unknown') AS operating_status,
        COUNT(*) AS records
    FROM cw_camping_places
    GROUP BY taxonomy_primary, operating_status
    ORDER BY taxonomy_primary, records DESC, operating_status
) TO '02_operating_status.csv' WITH (HEADER, DELIMITER ',');

COPY (
    SELECT
        taxonomy_primary AS category,
        CASE
            WHEN confidence IS NULL THEN 'unknown'
            WHEN confidence < 0.50 THEN '0.00-0.49'
            WHEN confidence < 0.70 THEN '0.50-0.69'
            WHEN confidence < 0.80 THEN '0.70-0.79'
            WHEN confidence < 0.90 THEN '0.80-0.89'
            ELSE '0.90-1.00'
        END AS confidence_bucket,
        COUNT(*) AS records
    FROM cw_camping_places
    GROUP BY taxonomy_primary, confidence_bucket
    ORDER BY taxonomy_primary,
        CASE confidence_bucket
            WHEN 'unknown' THEN 0
            WHEN '0.00-0.49' THEN 1
            WHEN '0.50-0.69' THEN 2
            WHEN '0.70-0.79' THEN 3
            WHEN '0.80-0.89' THEN 4
            WHEN '0.90-1.00' THEN 5
        END
) TO '03_confidence_distribution.csv' WITH (HEADER, DELIMITER ',');

COPY (
    SELECT
        taxonomy_primary AS category,
        COUNT(*) AS total_records,
        COUNT(*) FILTER (WHERE name IS NOT NULL AND TRIM(name) <> '') AS with_name,
        COUNT(*) FILTER (WHERE addresses IS NOT NULL AND length(addresses) > 0) AS with_address,
        COUNT(*) FILTER (WHERE websites IS NOT NULL AND length(websites) > 0) AS with_website,
        COUNT(*) FILTER (WHERE phones IS NOT NULL AND length(phones) > 0) AS with_phone,
        COUNT(*) FILTER (WHERE emails IS NOT NULL AND length(emails) > 0) AS with_email,
        COUNT(*) FILTER (WHERE socials IS NOT NULL AND length(socials) > 0) AS with_social,
        COUNT(*) FILTER (WHERE sources IS NOT NULL AND length(sources) > 0) AS with_source
    FROM cw_camping_places
    GROUP BY taxonomy_primary
    ORDER BY taxonomy_primary
) TO '04_field_completeness.csv' WITH (HEADER, DELIMITER ',');

COPY (
    SELECT
        p.taxonomy_primary AS category,
        COALESCE(s.source.dataset, '') AS dataset,
        COALESCE(s.source.provider, '') AS provider,
        COALESCE(s.source.resource, '') AS resource,
        COALESCE(s.source.license, '') AS license,
        COUNT(*) AS source_mentions,
        COUNT(DISTINCT p.id) AS records
    FROM cw_camping_places p,
         UNNEST(p.sources) AS s(source)
    GROUP BY
        p.taxonomy_primary,
        s.source.dataset,
        s.source.provider,
        s.source.resource,
        s.source.license
    ORDER BY p.taxonomy_primary, records DESC, source_mentions DESC
) TO '05_sources_and_licenses.csv' WITH (HEADER, DELIMITER ',');

COPY (
    SELECT
        id,
        name,
        taxonomy_primary AS category,
        basic_category,
        ROUND(confidence, 4) AS confidence,
        COALESCE(CAST(operating_status AS VARCHAR), 'unknown') AS operating_status,
        latitude,
        longitude,
        addresses[1].freeform AS address,
        addresses[1].locality AS locality,
        addresses[1].postcode AS postcode,
        addresses[1].region AS region,
        addresses[1].country AS country,
        CASE WHEN websites IS NOT NULL AND length(websites) > 0 THEN websites[1] END AS website,
        CASE WHEN phones IS NOT NULL AND length(phones) > 0 THEN phones[1] END AS phone,
        CASE WHEN emails IS NOT NULL AND length(emails) > 0 THEN emails[1] END AS email,
        CAST(taxonomy_hierarchy AS JSON) AS taxonomy_hierarchy,
        CAST(taxonomy_alternates AS JSON) AS taxonomy_alternates,
        CAST(sources AS JSON) AS sources
    FROM cw_camping_places
    ORDER BY taxonomy_primary, name, id
) TO '06_all_candidates.csv' WITH (HEADER, DELIMITER ',');

COPY (
    SELECT * EXCLUDE (sample_rank)
    FROM (
        SELECT
            id,
            name,
            taxonomy_primary AS category,
            basic_category,
            ROUND(confidence, 4) AS confidence,
            COALESCE(CAST(operating_status AS VARCHAR), 'unknown') AS operating_status,
            latitude,
            longitude,
            addresses[1].freeform AS address,
            addresses[1].locality AS locality,
            addresses[1].postcode AS postcode,
            addresses[1].region AS region,
            CASE WHEN websites IS NOT NULL AND length(websites) > 0 THEN websites[1] END AS website,
            CASE WHEN phones IS NOT NULL AND length(phones) > 0 THEN phones[1] END AS phone,
            CASE WHEN emails IS NOT NULL AND length(emails) > 0 THEN emails[1] END AS email,
            CAST(taxonomy_alternates AS JSON) AS taxonomy_alternates,
            CAST(sources AS JSON) AS sources,
            ROW_NUMBER() OVER (
                PARTITION BY taxonomy_primary
                ORDER BY hash(id)
            ) AS sample_rank
        FROM cw_camping_places
    )
    WHERE sample_rank <= 100
    ORDER BY category, sample_rank
) TO '07_sample_100_per_category.csv' WITH (HEADER, DELIMITER ',');

COPY (
    SELECT
        taxonomy_primary AS category,
        COUNT(*) AS records,
        ROUND(AVG(confidence), 4) AS avg_confidence,
        ROUND(MIN(confidence), 4) AS min_confidence,
        ROUND(MAX(confidence), 4) AS max_confidence,
        COUNT(*) FILTER (
            WHERE operating_status = 'permanently_closed'
        ) AS permanently_closed,
        COUNT(*) FILTER (
            WHERE confidence >= 0.80
        ) AS confidence_ge_080,
        COUNT(*) FILTER (
            WHERE confidence >= 0.90
        ) AS confidence_ge_090
    FROM cw_camping_places
    GROUP BY taxonomy_primary
    ORDER BY records DESC, category
) TO '08_compact_summary.csv' WITH (HEADER, DELIMITER ',');

SELECT
    taxonomy_primary AS category,
    COUNT(*) AS records,
    ROUND(AVG(confidence), 4) AS avg_confidence,
    COUNT(*) FILTER (WHERE confidence >= 0.80) AS confidence_ge_080,
    COUNT(*) FILTER (WHERE operating_status = 'permanently_closed') AS permanently_closed
FROM cw_camping_places
GROUP BY taxonomy_primary
ORDER BY records DESC, category;
