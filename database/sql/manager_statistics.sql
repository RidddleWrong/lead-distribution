WITH history_sequence AS (
    SELECT
        id,
        lead_id,
        to_status,
        created_at,
        LEAD(to_status) OVER (
            PARTITION BY lead_id
            ORDER BY created_at, id
            ) AS next_status,
        LEAD(created_at) OVER (
            PARTITION BY lead_id
            ORDER BY created_at, id
            ) AS next_created_at
    FROM lead_status_histories
),

     work_intervals AS (
         SELECT
             lead_id,
             SUM(
                 EXTRACT(
                     EPOCH FROM (next_created_at - created_at)
                 )
             ) AS total_time_in_work_seconds
         FROM history_sequence
         WHERE to_status = 'IN_PROGRESS'
           AND next_status = 'DONE'
         GROUP BY lead_id
     ),

     completed_leads AS (
         SELECT DISTINCT lead_id
         FROM history_sequence
         WHERE to_status = 'DONE'
           AND created_at >= NOW() - INTERVAL '30 days'
     )

SELECT
    m.id,
    m.name,

    COUNT(l.id) FILTER (
        WHERE l.status IN ('NEW', 'IN_PROGRESS')
        ) AS open_leads_count,

    AVG(work_intervals.total_time_in_work_seconds)
        * INTERVAL '1 second' AS average_time_in_work,

    COUNT(DISTINCT completed_leads.lead_id) AS completed_leads_last_30_days

FROM managers m

         LEFT JOIN leads l
                   ON l.manager_id = m.id

         LEFT JOIN work_intervals
                   ON work_intervals.lead_id = l.id

         LEFT JOIN completed_leads
                   ON completed_leads.lead_id = l.id

GROUP BY
    m.id,
    m.name

ORDER BY
    m.id;
