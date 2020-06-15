<?php

global $wpdb;

$visitor_limit = get_option("makerspace_visitor_limit");
$error = "";


$today = new DateTime();
$offset = 0;
if (isset($_GET["offset"])) {
    $offset = $_GET["offset"];
}
$today = $today->modify($offset . " day");

$day = (object) array(
    "date" => $today,
    "start" => (clone $today->setTime(0, 0, 0)),
    "end" => (clone $today->setTime(23, 59, 59)),
    "count" => 0,
    "hours" => array()
);

// create arrive / leave log
if (isset($_POST["mp_create_log"])) {
    $sql_mp_create_log = "INSERT INTO makerspace_presence_logs (mpl_user_id, mpl_timestamp) values (%d, %d)";
    $wpdb->get_results($wpdb->prepare(
        $sql_mp_create_log,
        $_POST["mp_create_log"],
        new DateTime()
    ));
}






$sql_reservations = "
    SELECT mar_user_id, MIN(mar_from) as mar_from, MAX(mar_to) as mar_to 
    FROM `makerspace_advance_registrations` 
    WHERE mar_from > %d AND mar_to < %d AND mar_deleted = 0 GROUP BY mar_user_id
";

$reservations = $wpdb->get_results($wpdb->prepare(
    $sql_reservations,
    $day->start->getTimestamp(),
    $day->end->getTimestamp()
));

$day->count = count($reservations);

for ($hour = 15; $hour < 22; $hour++) {
    $hour_count = 0;
    $hour_timestamp_begin = (clone $day->date->setTime($hour, 0, 0))->getTimestamp();
    $hour_timestamp_end = (clone $day->date->setTime($hour, 59, 59))->getTimestamp();

    foreach ($reservations as $r) {
        if ($r->mar_from <= $hour_timestamp_begin && $r->mar_to >= $hour_timestamp_end) {
            $hour_count++;
        }
    }

    $sql_rvp = "SELECT * FROM makerspace_advance_registrations WHERE mar_from = %d AND mar_user_id = %d";
    $rvp = $wpdb->get_row($wpdb->prepare($sql_rvp, $hour_timestamp_begin, get_current_user_id()));

    $h = (object) array(
        "hour" => $hour,
        "count" => $hour_count,
        "start" => $hour_timestamp_begin,
        "end" => $hour_timestamp_end,
        "color" => $hour_count < $visitor_limit ? "rgb(161, 198, 57)" :  "#e40033",
        "reserved" => $rvp == null || $rvp->mar_deleted > 0 ? false : true
    );

    array_push($day->hours, $h);
}


?>

<?php if ($error != "") : ?>
    <div class="row mt-3" style="max-width: 100%;">
        <div class="col">
            <div class="alert alert-danger" role="alert" style="padding: 8px 12px; width: 100%;">
                <?php echo $error ?>
            </div>
        </div>
    </div>
<?php endif; ?>


<form method="POST" action="?page=reservations-pos&offset=<?php echo $offset ?>">

    <?php wp_nonce_field(basename(__FILE__), 'makerspace_advance_refistration_nonce'); ?>

    <div class="row mt-3" style="max-width: 100%;">
        <div class="col">
            <h1 class="wp-heading-inline" style="font-size: 23px;">POS</h1>
        </div>
    </div>

    <div class="row mt-3" style="max-width: 100%; margin-top: 0 !important;">
        <div class="col-12 col-md-9 order-1 order-md-0">
            <div class="card wp-settings" style="border-radius: 0; padding: 8px 12px;">
                <div class="card-body">


                    <div class="row">

                        <a href="?page=reservations-pos&offset=<?php echo ($offset - 1) ?>" class="w-100 d-md-none d-flex flex-md-column justify-content-center">
                            <clr-icon shape="angle" size="36" dir="up"></clr-icon>
                        </a>

                        <a href="?page=reservations-pos&offset=<?php echo ($offset - 1) ?>" class="d-none d-md-flex flex-md-column justify-content-center">
                            <clr-icon shape="angle" size="36" dir="left" class=""></clr-icon>
                        </a>

                        <div class="col d-flex justify-content-center">
                            <h3>
                                <span class=""> <?php echo dayToString($day->date->format('w')); ?></span>
                                <span class="ml-3"> <?php echo $day->date->format('d.m.'); ?></span>
                            </h3>
                        </div>

                        <a href="?page=reservations-pos&offset=<?php echo ($offset + 1) ?>" class="w-100 d-md-none d-flex flex-md-column justify-content-center">
                            <clr-icon shape="angle" size="36" dir="down"></clr-icon>
                        </a>

                        <a href="?page=reservations-pos&offset=<?php echo ($offset + 1) ?>" class="d-none d-md-flex flex-md-column justify-content-center">
                            <clr-icon shape="angle" size="36" dir="right" class=""></clr-icon>
                        </a>
                    </div>

                    <div class="row">

                        <div class="col-2 font-weight-bold">
                            <span class="mr-2">Vorname</span>
                        </div>
                        <div class="col-2 font-weight-bold">
                            <span class="mr-2">Nachname</span>
                        </div>
                        <div class="col-2 font-weight-bold">
                            <span class="mr-2">Username</span>
                        </div>
                        <div class="col-2 font-weight-bold">
                            <span class="mr-2">Von - Bis</span>
                        </div>
                        <div class="col-4 font-weight-bold"></div>
                    </div>



                    <?php foreach ($reservations as $r) : ?>
                        <div class="row mb-2">
                            <?php

                            $r_user = get_userdata($r->mar_user_id);
                            $r_from = new DateTime();
                            $r_from->setTimestamp($r->mar_from);
                            $r_to = new DateTime();
                            $r_to->setTimestamp($r->mar_to);

                            // $is_here = false;
                            // $mpl_sql = "SELECT * FROM makerspace_presence_logs WHERE mpl_datetime BETWEEN  %d AND %d AND mpl_user_id = %d";
                            // $mpl_entries = $wpdb->get_results($wpdb->prepare(
                            //     $mpl_sql,
                            //     $day->start,
                            //     $day->end,
                            //     $r->mar_user_id
                            // ));


                            ?>

                            <div class="col-2">
                                <span class="mr-2">
                                    <?php echo $r_user->user_firstname ?>
                                </span>
                            </div>
                            <div class="col-2">
                                <span class="mr-2">
                                    <?php echo $r_user->user_lastname  ?>
                                </span>
                            </div>
                            <div class="col-2">
                                <span class="mr-2">
                                    <?php echo $r_user->user_login  ?>
                                </span>
                            </div>

                            <div class="col-2">
                                <span class=""><?php echo $r_from->format('H:i') ?></span>
                                -
                                <span class=""><?php echo $r_to->format('H:i') ?></span>
                            </div>
                            <div class="col-4">

                                    <button type="submit" class="btn btn-outline-success btn-sm" id="mp_create_log" name="mp_create_log" value="<?php echo $r->mar_user_id ?>">kommen</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>

        <div class="col-12 col-md-3 order-0 order-md-1">
            <div class="card" style="padding: 0; border-radius: 0; font-size: 14px; ">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item" style="font-size: 14px; padding: 8px 12px; font-weight: bold;">Info</li>
                    <li class="list-group-item" style="font-size: 14px; padding: 8px 12px;">
                        Hier kannst du deine Anmeldungen sehen. Die Anmeldungen erfolgen stundenweise. Bitte überlege dir, wie viel Zeit du am Stück im Maker Space verbringen willst und buche entsprechend.
                    </li>
                    <li class="list-group-item" style="font-size: 14px; padding: 8px 12px;">
                        Da wir aktuell nur <?php echo $visitor_limit ?> Besuchende im Maker Space willkommen heißen dürfen, sei so fair und gib Zeiten wieder frei wenn du nicht kommen kannst.
                    </li>
                    <!-- <li class="list-group-item d-flex justify-content-end" style="background: #f5f5f5; font-size: 14px; padding: 8px 12px;"">
                        <button type=" submit" class="btn btn-primary btn-sm" style="background: #0071a1;">speichern</button>
                    </li> -->
                </ul>
            </div>
        </div>
    </div>

</form>