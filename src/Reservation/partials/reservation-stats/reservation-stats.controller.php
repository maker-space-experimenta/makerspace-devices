<?php

global $wpdb;



$stats_sql = '
SELECT
	count(mpl_date) as mpl_count,
    mpl_date
FROM (
	SELECT
        mpl_user_id,
        DATE_FORMAT(mpl_datetime, "%Y-%m-%d") as mpl_date
    FROM
        makerspace_presence_logs
    GROUP BY
        DATE_FORMAT(mpl_datetime, "%Y-%m-%d"),
        mpl_user_id
    ) as tmp
GROUP BY mpl_date
ORDER BY mpl_date ASC
';

$rows = $wpdb->get_results($stats_sql);

$visitors_stats_sql = '
SELECT
	count(mpl_date) as mpl_count,
    mpl_date
FROM (
	SELECT
        mpl_temp_visitor_id,
        DATE_FORMAT(mpl_datetime, "%Y-%m-%d") as mpl_date
    FROM
        makerspace_presence_logs
    GROUP BY
        DATE_FORMAT(mpl_datetime, "%Y-%m-%d"),
        mpl_temp_visitor_id
    ) as tmp
GROUP BY mpl_date  
ORDER BY `tmp`.`mpl_date`  DESC
';

$visitors_rows = $wpdb->get_results($visitors_stats_sql);

$dates = array();

$period = new DatePeriod(
    new DateTime('2020-06-01'),
    new DateInterval('P1D'),
    get_datetime()
);



foreach ($period as $key => $value) {
    $count = 0;
    $visitors = 0;

    foreach ($rows as $row) {
        if ($row->mpl_date == $value->format("Y-m-d")) {
            $count = $row->mpl_count;
        }
    }
    
    foreach ($visitors_rows as $row) {
        if ($row->mpl_date == $value->format("Y-m-d")) {
            $visitors = $row->mpl_count;
        }
    }

    array_push($dates, (object) array(
        "date" => $value->format("Y-m-d"),
        "count" => $count,
        "guests" => $visitors
    ));
}


require dirname(__FILE__) . "/reservation-stats.partial.php";
