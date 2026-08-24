SELECT
    m.id,
    m.name,

    COUNT(l.id) FILTER (
        WHERE l.status IN ('NEW', 'IN_PROGRESS')
    ) AS open_leads_count,

    AVG(
            done_history.created_at - in_progress_history.created_at
    ) AS average_time_in_work,

    COUNT(l.id) FILTER (
        WHERE l.status = 'DONE'
        AND done_history.created_at >= NOW() - INTERVAL '30 days'
    ) AS completed_leads_last_30_days

FROM managers m

         LEFT JOIN leads l
                   ON l.manager_id = m.id

         LEFT JOIN lead_status_histories in_progress_history
                   ON in_progress_history.lead_id = l.id
                       AND in_progress_history.to_status = 'IN_PROGRESS'

         LEFT JOIN lead_status_histories done_history
                   ON done_history.lead_id = l.id
                       AND done_history.to_status = 'DONE'

GROUP BY
    m.id,
    m.name

ORDER BY
    m.id;
