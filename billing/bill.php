<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include('../config/db.php');

date_default_timezone_set('Asia/Kolkata');

$india_timezone = new DateTimeZone('Asia/Kolkata');
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header('Location: ../login.php');
    exit();
}


/* =========================
   INITIAL SESSION
========================= */

if (!isset($_SESSION['bill_cart']) || !is_array($_SESSION['bill_cart'])) {
    $_SESSION['bill_cart'] = [];
}

if (!isset($_SESSION['bill_patient']) || !is_array($_SESSION['bill_patient'])) {
    $_SESSION['bill_patient'] = [
        'name' => '',
        'address' => '',
        'doctor' => '',
        'prescription_verified' => 0
    ];
}

$message = '';
$message_type = '';


/* =========================
   HELPER FUNCTIONS
========================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


function redirect_bill()
{
    header('Location: bill.php');
    exit();
}


/* GET MEDICINE */
function get_medicine($conn, $user_id, $medicine_id = 0, $barcode = '')
{
    $user_id = (int)$user_id;

    if ($barcode !== '') {

        $barcode_safe = mysqli_real_escape_string($conn, $barcode);

        $sql = "
            SELECT *
            FROM medicines
            WHERE barcode='$barcode_safe'
            AND user_id='$user_id'
            LIMIT 1
        ";

    } else {

        $medicine_id = (int)$medicine_id;

        $sql = "
            SELECT *
            FROM medicines
            WHERE id='$medicine_id'
            AND user_id='$user_id'
            LIMIT 1
        ";
    }

    $q = mysqli_query($conn, $sql);

    if (!$q || mysqli_num_rows($q) === 0) {
        return null;
    }

    return mysqli_fetch_assoc($q);
}


/* LATEST PURCHASE PRICE */
function get_latest_purchase_price(
    $conn,
    $medicine_id,
    $user_id,
    $fallback = 0
) {

    $medicine_id = (int)$medicine_id;
    $user_id = (int)$user_id;

    $q = mysqli_query(
        $conn,
        "
        SELECT purchase_price
        FROM purchases
        WHERE medicine_id='$medicine_id'
        AND user_id='$user_id'
        ORDER BY purchase_date DESC, id DESC
        LIMIT 1
        "
    );

    if ($q && mysqli_num_rows($q) > 0) {

        $row = mysqli_fetch_assoc($q);

        $price = (float)($row['purchase_price'] ?? 0);

        if ($price > 0) {
            return $price;
        }
    }

    return (float)$fallback;
}


/* FIFO BATCH PURCHASE COST */
function get_fifo_purchase_cost(
    $conn,
    $medicine_id,
    $user_id,
    $sale_units,
    $available_units,
    $pack_size,
    $master_pack_purchase
) {
    $medicine_id = (int)$medicine_id;
    $user_id = (int)$user_id;
    $sale_units = (int)$sale_units;
    $available_units = (int)$available_units;
    $pack_size = max(1, (int)$pack_size);
    $master_pack_purchase = (float)$master_pack_purchase;

    if ($sale_units <= 0) {
        return [
            'success' => false,
            'total_cost' => 0,
            'unit_cost' => 0,
            'error' => 'Invalid sale quantity.'
        ];
    }

    /*
     * Lock all purchase batches for this medicine.
     * Oldest purchase is consumed first (FIFO).
     */
    $purchase_rows = [];
    $tracked_units = 0;

    $purchase_q = mysqli_query(
        $conn,
        "
        SELECT
            id,
            batch_no,
            pack_size,
            remaining_units,
            purchase_price,
            purchase_date
        FROM purchases
        WHERE medicine_id='$medicine_id'
        AND user_id='$user_id'
        AND remaining_units > 0
        ORDER BY purchase_date ASC, id ASC
        FOR UPDATE
        "
    );

    if ($purchase_q) {
        while ($purchase = mysqli_fetch_assoc($purchase_q)) {
            $remaining = max(0, (int)($purchase['remaining_units'] ?? 0));
            if ($remaining <= 0) {
                continue;
            }

            $purchase_pack_size = max(
                1,
                (int)($purchase['pack_size'] ?? $pack_size)
            );

            $purchase_pack_price = (float)($purchase['purchase_price'] ?? 0);

            if ($purchase_pack_price <= 0) {
                continue;
            }

            $purchase_rows[] = [
                'id' => (int)$purchase['id'],
                'remaining_units' => $remaining,
                'pack_size' => $purchase_pack_size,
                'purchase_price' => $purchase_pack_price
            ];

            $tracked_units += $remaining;
        }
    }

    /*
     * Any current stock not represented by purchase batches is treated
     * as the original stock entered through Add Medicine, using the
     * medicine master purchase price.
     */
    $opening_units = max(
        0,
        $available_units - $tracked_units
    );

    $units_left = $sale_units;
    $total_cost = 0.0;

    if ($opening_units > 0 && $units_left > 0) {
        $use_opening = min($units_left, $opening_units);
        $opening_unit_cost =
            $master_pack_purchase > 0
            ? ($master_pack_purchase / $pack_size)
            : 0;

        if ($opening_unit_cost <= 0) {
            return [
                'success' => false,
                'total_cost' => 0,
                'unit_cost' => 0,
                'error' => 'Opening stock purchase price is missing.'
            ];
        }

        $total_cost += $use_opening * $opening_unit_cost;
        $units_left -= $use_opening;
    }

    /* Consume purchase batches oldest first. */
    foreach ($purchase_rows as $batch) {
        if ($units_left <= 0) {
            break;
        }

        $use_units = min(
            $units_left,
            (int)$batch['remaining_units']
        );

        $batch_unit_cost =
            (float)$batch['purchase_price']
            /
            max(1, (int)$batch['pack_size']);

        if ($batch_unit_cost <= 0) {
            return [
                'success' => false,
                'total_cost' => 0,
                'unit_cost' => 0,
                'error' => 'Purchase batch price is missing.'
            ];
        }

        $total_cost += $use_units * $batch_unit_cost;
        $new_remaining =
            (int)$batch['remaining_units'] - $use_units;

        $batch_id = (int)$batch['id'];

        $updated_batch = mysqli_query(
            $conn,
            "
            UPDATE purchases
            SET remaining_units='$new_remaining'
            WHERE id='$batch_id'
            AND medicine_id='$medicine_id'
            AND user_id='$user_id'
            "
        );

        if (!$updated_batch) {
            return [
                'success' => false,
                'total_cost' => 0,
                'unit_cost' => 0,
                'error' => 'Unable to update purchase batch stock.'
            ];
        }

        $units_left -= $use_units;
    }

    if ($units_left > 0) {
        return [
            'success' => false,
            'total_cost' => 0,
            'unit_cost' => 0,
            'error' => 'Purchase batch stock is not synchronized with current medicine stock.'
        ];
    }

    return [
        'success' => true,
        'total_cost' => $total_cost,
        'unit_cost' => $total_cost / $sale_units,
        'error' => ''
    ];
}


/* EXPIRED CHECK */
function medicine_is_expired($expiry_date)
{
    if (empty($expiry_date)) {
        return false;
    }

    $today = new DateTimeImmutable(
        'now',
        new DateTimeZone('Asia/Kolkata')
    );

    return $expiry_date < $today->format('Y-m-d');
}


/* CHECK H1 IN CART */
function cart_has_h1()
{
    foreach ($_SESSION['bill_cart'] ?? [] as $item) {

        if (!empty($item['is_h1'])) {
            return true;
        }
    }

    return false;
}


/* =========================
   NEW BILL
========================= */

if (isset($_GET['new'])) {

    unset(
        $_SESSION['bill_cart'],
        $_SESSION['bill_patient'],
        $_SESSION['last_bill']
    );

    $_SESSION['bill_cart'] = [];

    $_SESSION['bill_patient'] = [
        'name' => '',
        'address' => '',
        'doctor' => '',
        'prescription_verified' => 0
    ];

    redirect_bill();
}


/* =========================
   REMOVE MEDICINE
========================= */

if (isset($_GET['remove'])) {

    $remove_id = (int)$_GET['remove'];

    foreach ($_SESSION['bill_cart'] as $key => $item) {

        if ((int)($item['id'] ?? 0) === $remove_id) {

            unset($_SESSION['bill_cart'][$key]);
        }
    }

    $_SESSION['bill_cart'] =
        array_values($_SESSION['bill_cart']);

    redirect_bill();
}


/* =========================
   CLEAR BILL
========================= */

if (isset($_GET['clear_cart'])) {

    $_SESSION['bill_cart'] = [];

    $_SESSION['bill_patient']['prescription_verified'] = 0;

    redirect_bill();
}


/* =========================
   SAVE PATIENT DETAILS
========================= */

if (isset($_POST['save_patient'])) {

    $_SESSION['bill_patient']['name'] =
        trim($_POST['patient_name'] ?? '');

    $_SESSION['bill_patient']['address'] =
        trim($_POST['patient_address'] ?? '');

    $_SESSION['bill_patient']['doctor'] =
        trim($_POST['doctor_name'] ?? '');

    $_SESSION['bill_patient']['prescription_verified'] =
        isset($_POST['prescription_verified']) ? 1 : 0;

    $message = 'Patient details saved.';
    $message_type = 'success';
}


/* =========================
   ADD MEDICINE
========================= */

if (isset($_POST['add_medicine'])) {

    $medicine_id =
        (int)($_POST['medicine_id'] ?? 0);

    $barcode =
        trim($_POST['barcode'] ?? '');

    $quantity =
        (int)($_POST['quantity'] ?? 0);

    $selected_type =
        $_POST['medicine_type'] ?? 'General';


    /* ALLOWED TYPES */

    if (
        !in_array(
            $selected_type,
            ['General', 'Schedule', 'Veterinary'],
            true
        )
    ) {
        $selected_type = 'General';
    }


    /* GET MEDICINE */

    $medicine =
        get_medicine(
            $conn,
            $user_id,
            $medicine_id,
            $barcode
        );


    if (!$medicine) {

        $message =
            '❌ Medicine not found.';

        $message_type =
            'error';


    } elseif (
        ($medicine['medicine_type'] ?? 'General')
        !== $selected_type
    ) {

        $message =
            '❌ Selected medicine does not belong to the chosen category.';

        $message_type =
            'error';


    } elseif ($quantity <= 0) {

        $message =
            '❌ Enter a valid quantity.';

        $message_type =
            'error';


    } else {

        $expiry =
            $medicine['expiry_date'] ?? '';


        /* =========================
           EXPIRED MEDICINE
        ========================= */

        if (medicine_is_expired($expiry)) {

            $message =
                '❌ MEDICINE EXPIRED – '
                . $medicine['medicine_name']
                . ' expired on '
                . date('d-m-Y', strtotime($expiry))
                . '. This medicine cannot be sold.';

            $message_type =
                'error';


        } else {

            $pack_size =
                max(
                    1,
                    (int)($medicine['pack_size'] ?? 1)
                );


            /* ACTUAL UNIT STOCK */

            $stock_units =
                (int)($medicine['stock_units'] ?? 0);


            /* OLD RECORD SUPPORT */

            if (
                $stock_units <= 0
                &&
                (int)($medicine['quantity'] ?? 0) > 0
            ) {

                $stock_units =
                    (int)$medicine['quantity']
                    * $pack_size;
            }


            /* ALREADY ADDED QUANTITY */

            $existing_qty = 0;

            foreach (
                $_SESSION['bill_cart']
                as $item
            ) {

                if (
                    (int)($item['id'] ?? 0)
                    ===
                    (int)$medicine['id']
                ) {

                    $existing_qty =
                        (int)($item['quantity'] ?? 0);

                    break;
                }
            }


            /* STOCK CHECK */

            if (
                ($existing_qty + $quantity)
                >
                $stock_units
            ) {

                $message =
                    '❌ Only '
                    . max(
                        0,
                        $stock_units - $existing_qty
                    )
                    . ' unit(s) are available for this medicine.';

                $message_type =
                    'error';


            } else {

                /* FULL PACK SELLING PRICE */

                $pack_selling_price =
                    (float)($medicine['price'] ?? 0);


                /* FULL PACK PURCHASE PRICE */

                $pack_purchase_price =
                    (float)($medicine['purchase_price'] ?? 0);


                /* FALLBACK TO PURCHASE TABLE */

                if ($pack_purchase_price <= 0) {

                    $pack_purchase_price =
                        get_latest_purchase_price(
                            $conn,
                            $medicine['id'],
                            $user_id,
                            0
                        );
                }


                if ($pack_selling_price <= 0) {

                    $message =
                        '❌ Selling price is not available for this medicine.';

                    $message_type =
                        'error';


                } elseif ($pack_purchase_price <= 0) {

                    $message =
                        '❌ Purchase price is not available for this medicine.';

                    $message_type =
                        'error';


                } else {

                    /* UNIT PRICE */

                    $unit_selling_price =
                        $pack_selling_price / $pack_size;

                    $unit_purchase_price =
                        $pack_purchase_price / $pack_size;


                    /*
                     * H1 ONLY FOR
                     * SCHEDULE / VETERINARY
                     *
                     * GENERAL = NEVER H1
                     */

                    $is_h1 =
                        (
                            $selected_type !== 'General'
                            &&
                            !empty($medicine['is_h1'])
                        )
                        ? 1
                        : 0;


                    $found = false;


                    /* =========================
                       IF SAME MEDICINE ALREADY
                       IN BILL → ADD QUANTITY
                    ========================= */

                    foreach (
                        $_SESSION['bill_cart']
                        as &$item
                    ) {

                        if (
                            (int)($item['id'] ?? 0)
                            ===
                            (int)$medicine['id']
                        ) {

                            $item['quantity'] =
                                (int)($item['quantity'] ?? 0)
                                + $quantity;

                            $item['unit_price'] =
                                $unit_selling_price;

                            $item['unit_purchase_price'] =
                                $unit_purchase_price;

                            $item['pack_price'] =
                                $pack_selling_price;

                            $item['pack_purchase_price'] =
                                $pack_purchase_price;

                            $item['total'] =
                                $item['quantity']
                                * $unit_selling_price;

                            $found = true;

                            break;
                        }
                    }

                    unset($item);


                    /* =========================
                       NEW MEDICINE IN BILL
                    ========================= */

                    if (!$found) {

                        $_SESSION['bill_cart'][] = [

                            'id' =>
                                (int)$medicine['id'],

                            'medicine_name' =>
                                $medicine['medicine_name'],

                            'barcode' =>
                                $medicine['barcode'] ?? '',

                            'quantity' =>
                                $quantity,

                            'unit_price' =>
                                $unit_selling_price,

                            'unit_purchase_price' =>
                                $unit_purchase_price,

                            'pack_price' =>
                                $pack_selling_price,

                            'pack_purchase_price' =>
                                $pack_purchase_price,

                            'pack_size' =>
                                $pack_size,

                            'pack_type' =>
                                $medicine['pack_type']
                                ?? 'Tablet',

                            'batch_no' =>
                                $medicine['batch_no']
                                ?? '',

                            'expiry_date' =>
                                $expiry,

                            'medicine_type' =>
                                $selected_type,

                            'is_h1' =>
                                $is_h1,

                            'total' =>
                                $quantity
                                * $unit_selling_price
                        ];
                    }


                    $message =
                        '✅ Medicine added to bill.';

                    $message_type =
                        'success';
                }
            }
        }
    }
}


/* =========================
   CHECKOUT
========================= */

if (isset($_POST['checkout'])) {

    $_SESSION['bill_patient']['name'] =
        trim(
            $_POST['patient_name']
            ??
            $_SESSION['bill_patient']['name']
        );

    $_SESSION['bill_patient']['address'] =
        trim(
            $_POST['patient_address']
            ??
            $_SESSION['bill_patient']['address']
        );

    $_SESSION['bill_patient']['doctor'] =
        trim(
            $_POST['doctor_name']
            ??
            $_SESSION['bill_patient']['doctor']
        );

    $_SESSION['bill_patient']['prescription_verified'] =
        isset($_POST['prescription_verified'])
        ? 1
        : 0;


    /* PAYMENT */

    $payment_method_raw =
        trim($_POST['payment_method'] ?? 'Cash');

    /* Normalize payment values before saving. */
    $payment_method_key =
        strtolower($payment_method_raw);

    if (
        $payment_method_key === 'upi'
        ||
        $payment_method_key === 'online'
    ) {
        $payment_method = 'UPI';
    }
    elseif ($payment_method_key === 'card') {
        $payment_method = 'Card';
    }
    else {
        $payment_method = 'Cash';
    }


    /* DISCOUNT */

    $discount =
        (float)($_POST['discount'] ?? 0);

    if ($discount < 0) {
        $discount = 0;
    }


    /* GROSS */

    $gross_total = 0;

    foreach (
        $_SESSION['bill_cart']
        as $item
    ) {

        $gross_total +=
            (float)($item['total'] ?? 0);
    }


    if ($discount > $gross_total) {
        $discount = $gross_total;
    }


    $net_total =
        max(
            0,
            $gross_total - $discount
        );


    /* =========================
       VALIDATIONS
    ========================= */

    if (
        empty($_SESSION['bill_cart'])
    ) {

        $message =
            '❌ Bill is empty.';

        $message_type =
            'error';


    } elseif (
        cart_has_h1()
        &&
        empty($_SESSION['bill_patient']['name'])
    ) {

        $message =
            '❌ Patient Name is required for H1 medicine.';

        $message_type =
            'error';


    } elseif (
        cart_has_h1()
        &&
        empty($_SESSION['bill_patient']['doctor'])
    ) {

        $message =
            '❌ Doctor Name is required for H1 medicine.';

        $message_type =
            'error';


    } elseif (
        cart_has_h1()
        &&
        empty(
            $_SESSION['bill_patient']
            ['prescription_verified']
        )
    ) {

        $message =
            '❌ Prescription must be verified for H1 medicine.';

        $message_type =
            'error';


    } else {

        mysqli_begin_transaction($conn);

        $success = true;

        $error_message = '';


        /*
         * IMPORTANT:
         * Always create bill time
         * in India timezone.
         */

        $bill_now =
            new DateTimeImmutable(
                'now',
                $india_timezone
            );


        $sale_date =
            $bill_now->format('Y-m-d');


        /* =========================
           EACH MEDICINE
        ========================= */

        foreach (
            $_SESSION['bill_cart']
            as $item
        ) {

            $id =
                (int)($item['id'] ?? 0);

            $qty =
                (int)($item['quantity'] ?? 0);


            /* LOCK MEDICINE */

            $check =
                mysqli_query(
                    $conn,
                    "
                    SELECT *
                    FROM medicines
                    WHERE id='$id'
                    AND user_id='$user_id'
                    FOR UPDATE
                    "
                );


            if (
                !$check
                ||
                mysqli_num_rows($check) === 0
            ) {

                $success = false;

                $error_message =
                    'Medicine not found while generating bill.';

                break;
            }


            $med =
                mysqli_fetch_assoc($check);


            /* EXPIRED CHECK AGAIN */

            if (
                medicine_is_expired(
                    $med['expiry_date'] ?? ''
                )
            ) {

                $success = false;

                $error_message =
                    'Medicine '
                    . $med['medicine_name']
                    . ' is expired and cannot be sold.';

                break;
            }


            $pack_size =
                max(
                    1,
                    (int)($med['pack_size'] ?? 1)
                );


            /* STOCK UNITS */

            $available_units =
                (int)($med['stock_units'] ?? 0);


            if (
                $available_units <= 0
                &&
                (int)($med['quantity'] ?? 0) > 0
            ) {

                $available_units =
                    (int)$med['quantity']
                    * $pack_size;
            }


            if (
                $qty <= 0
                ||
                $qty > $available_units
            ) {

                $success = false;

                $error_message =
                    'Insufficient stock for '
                    . $med['medicine_name']
                    . '.';

                break;
            }


            /* PURCHASE PRICE - FIFO BATCH COST */

            $pack_purchase =
                (float)($med['purchase_price'] ?? 0);

            if ($pack_purchase <= 0) {

                $pack_purchase =
                    get_latest_purchase_price(
                        $conn,
                        $id,
                        $user_id,
                        0
                    );
            }

            /*
             * Calculate actual cost from purchase batches.
             * Oldest batch is consumed first.
             */
            $fifo_cost =
                get_fifo_purchase_cost(
                    $conn,
                    $id,
                    $user_id,
                    $qty,
                    $available_units,
                    $pack_size,
                    $pack_purchase
                );

            if (!$fifo_cost['success']) {

                $success = false;

                $error_message =
                    $fifo_cost['error'];

                break;
            }

            $unit_purchase =
                (float)$fifo_cost['unit_cost'];

            $unit_selling =
                (float)($item['unit_price'] ?? 0);

            if (
                $unit_purchase <= 0
                ||
                $unit_selling <= 0
            ) {

                $success = false;

                $error_message =
                    'Price is missing for '
                    . $med['medicine_name']
                    . '.';

                break;
            }

            $purchase_sql =
                mysqli_real_escape_string(
                    $conn,
                    (string)$unit_purchase
                );

            $selling_sql =
                mysqli_real_escape_string(
                    $conn,
                    (string)$unit_selling
                );


            /* =========================
               SAVE SALE
            ========================= */

            /*
             * Save patient details only for H1 medicine.
             * Normal medicines keep these fields empty.
             */

            $item_is_h1 =
                !empty($item['is_h1'])
                ? 1
                : 0;

            $patient_name_sql = '';
            $doctor_name_sql = '';
            $patient_address_sql = '';
            $prescription_verified_sql = 0;

            if ($item_is_h1 === 1) {

                $patient_name_sql =
                    mysqli_real_escape_string(
                        $conn,
                        trim(
                            $_SESSION['bill_patient']['name']
                            ?? ''
                        )
                    );

                $doctor_name_sql =
                    mysqli_real_escape_string(
                        $conn,
                        trim(
                            $_SESSION['bill_patient']['doctor']
                            ?? ''
                        )
                    );

                $patient_address_sql =
                    mysqli_real_escape_string(
                        $conn,
                        trim(
                            $_SESSION['bill_patient']['address']
                            ?? ''
                        )
                    );

                $prescription_verified_sql =
                    !empty(
                        $_SESSION['bill_patient']
                        ['prescription_verified']
                    )
                    ? 1
                    : 0;
            }


            /*
             * The discount is entered for the whole bill.
             * Allocate it proportionally to this medicine row
             * so Sales Report can calculate exact profit/loss.
             */

            $item_gross =
                (float)($item['total'] ?? 0);

            $item_discount =
                $gross_total > 0
                ? round(
                    ($item_gross / $gross_total) * $discount,
                    2
                )
                : 0;

            $discount_sql =
                mysqli_real_escape_string(
                    $conn,
                    (string)$item_discount
                );

            $payment_method_sql =
                mysqli_real_escape_string(
                    $conn,
                    $payment_method
                );


            /* INSERT SALE */

            $insert =
                mysqli_query(
                    $conn,
                    "
                    INSERT INTO sales
                    (
                        medicine_id,
                        quantity,
                        purchase_price,
                        selling_price,
                        discount_amount,
                        payment_method,
                        sale_date,
                        user_id,
                        patient_name,
                        doctor_name,
                        patient_address,
                        prescription_verified
                    )
                    VALUES
                    (
                        '$id',
                        '$qty',
                        '$purchase_sql',
                        '$selling_sql',
                        '$discount_sql',
                        '$payment_method_sql',
                        '$sale_date',
                        '$user_id',
                        '$patient_name_sql',
                        '$doctor_name_sql',
                        '$patient_address_sql',
                        '$prescription_verified_sql'
                    )
                    "
                );


            if (!$insert) {

                $success = false;

                $error_message =
                    'Unable to save sale.';

                break;
            }


            /* =========================
               REDUCE ACTUAL UNIT STOCK
            ========================= */

            $new_units =
                max(
                    0,
                    $available_units - $qty
                );


            $new_packs =
                intdiv(
                    $new_units,
                    $pack_size
                );


            $updated =
                mysqli_query(
                    $conn,
                    "
                    UPDATE medicines
                    SET
                        stock_units='$new_units',
                        quantity='$new_packs'
                    WHERE id='$id'
                    AND user_id='$user_id'
                    "
                );


            if (
                !$updated
                ||
                mysqli_affected_rows($conn) === 0
            ) {

                $success = false;

                $error_message =
                    'Unable to update stock.';

                break;
            }
        }


        /* =========================
           BILL SUCCESS
        ========================= */

        if ($success) {

            mysqli_commit($conn);


            /*
             * Invoice number and time
             * from exact India time
             */

            $invoice_no =
                'MT-'
                . $bill_now->format('YmdHis');


            $_SESSION['last_bill'] = [

                'invoice_no' =>
                    $invoice_no,

                'items' =>
                    $_SESSION['bill_cart'],

                'payment_method' =>
                    $payment_method,

                'discount' =>
                    $discount,

                'gross_total' =>
                    $gross_total,

                'net_total' =>
                    $net_total,

                'patient' =>
                    $_SESSION['bill_patient'],

                'date' =>
                    $bill_now->format('d-m-Y'),

                'time' =>
                    $bill_now->format('h:i:s A')
            ];


            /* CLEAR CURRENT CART */

            $_SESSION['bill_cart'] = [];


            /*
             * Keep patient details,
             * but reset prescription checkbox.
             */

            $_SESSION['bill_patient']
                ['prescription_verified'] = 0;


            $message =
                '✅ Bill generated successfully.';

            $message_type =
                'success';


        } else {

            mysqli_rollback($conn);

            $message =
                '❌ ' . $error_message;

            $message_type =
                'error';
        }
    }
}


/* =========================
   PHARMACY DETAILS
========================= */

$pharmacy = [

    'full_name' => '',
    'shop_name' => '',
    'shop_address' => '',
    'medical_license_no' => '',
    'gst_no' => '',
    'mobile' => '',
    'email' => '',
    'upi_id' => ''
];


$q_user =
    mysqli_query(
        $conn,
        "
        SELECT
            full_name,
            shop_name,
            shop_address,
            medical_license_no,
            gst_no,
            mobile,
            email,
            upi_id
        FROM users
        WHERE id='$user_id'
        LIMIT 1
        "
    );


if (
    $q_user
    &&
    mysqli_num_rows($q_user) > 0
) {

    $pharmacy =
        array_merge(
            $pharmacy,
            mysqli_fetch_assoc($q_user)
        );
}


$upi_id =
    trim(
        $pharmacy['upi_id'] ?? ''
    );


$last_bill =
    $_SESSION['last_bill'] ?? null;


/* =========================
   CART TOTAL
========================= */

$cart_total = 0;

foreach (
    $_SESSION['bill_cart']
    as $item
) {

    $cart_total +=
        (float)($item['total'] ?? 0);
}


$cart_has_h1 =
    cart_has_h1();


/* =========================
   MEDICINE SEARCH DATA
========================= */

$medicine_data = [];


$medicine_result =
    mysqli_query(
        $conn,
        "
        SELECT
            id,
            medicine_name,
            barcode,
            quantity,
            stock_units,
            price,
            purchase_price,
            medicine_type,
            is_h1,
            pack_type,
            pack_size,
            batch_no,
            expiry_date
        FROM medicines
        WHERE user_id='$user_id'
        ORDER BY medicine_name ASC
        "
    );


if ($medicine_result) {

    while (
        $m =
        mysqli_fetch_assoc(
            $medicine_result
        )
    ) {

        $pack_size =
            max(
                1,
                (int)($m['pack_size'] ?? 1)
            );


        $pack_price =
            (float)($m['price'] ?? 0);


        $purchase_price =
            (float)($m['purchase_price'] ?? 0);


        if ($purchase_price <= 0) {

            $purchase_price =
                get_latest_purchase_price(
                    $conn,
                    $m['id'],
                    $user_id,
                    0
                );
        }


        $stock_units =
            (int)($m['stock_units'] ?? 0);


        if (
            $stock_units <= 0
            &&
            (int)($m['quantity'] ?? 0) > 0
        ) {

            $stock_units =
                (int)$m['quantity']
                * $pack_size;
        }


        $medicine_data[] = [

            'id' =>
                (int)$m['id'],

            'name' =>
                $m['medicine_name'],

            'barcode' =>
                $m['barcode'] ?? '',

            'quantity' =>
                $stock_units,

            'pack_size' =>
                $pack_size,

            'pack_price' =>
                $pack_price,

            'purchase_price' =>
                $purchase_price,

            'expiry_date' =>
                $m['expiry_date'] ?? '',

            'type' =>
                $m['medicine_type']
                ?? 'General',

            'is_h1' =>
                (int)($m['is_h1'] ?? 0),

            'batch_no' =>
                $m['batch_no'] ?? '',

            'pack_type' =>
                $m['pack_type']
                ?? 'Tablet'
        ];
    }
}


$cart_items =
    $_SESSION['bill_cart'] ?? [];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>MediTrack - Billing</title>


<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#eef8f8;
    color:#263238;
}

.topbar{
    background:linear-gradient(135deg,#007c83,#00a6a6);
    color:#fff;
    padding:16px 25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 3px 10px rgba(0,0,0,.15);
}

.logo{
    font-size:24px;
    font-weight:bold;
}

.topbar a{
    color:#fff;
    text-decoration:none;
    background:rgba(255,255,255,.15);
    padding:9px 15px;
    border-radius:7px;
}

.container{
    width:94%;
    max-width:1200px;
    margin:25px auto;
}

.page-title{
    text-align:center;
    color:#007c83;
    margin-bottom:22px;
}

.page-title h1{
    margin-bottom:5px;
}

.page-title p{
    color:#607d8b;
}

.message{
    padding:12px 15px;
    border-radius:8px;
    margin-bottom:18px;
    font-weight:bold;
}

.success{
    background:#dcfce7;
    color:#166534;
    border:1px solid #86efac;
}

.error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fca5a5;
}

.card{
    background:#fff;
    border-radius:14px;
    padding:22px;
    margin-bottom:22px;
    box-shadow:0 4px 18px rgba(0,0,0,.08);
}

.card h2{
    margin-top:0;
    color:#007c83;
}

.type-tabs{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:18px;
}

.type-tab{
    border:1px solid #b0bec5;
    background:#fff;
    color:#455a64;
    padding:10px 18px;
    border-radius:8px;
    font-weight:bold;
    cursor:pointer;
}

.type-tab.active{
    background:#007c83;
    color:#fff;
    border-color:#007c83;
}

.form-grid{
    display:grid;
    grid-template-columns:1.4fr 2fr 1fr 1fr auto;
    gap:12px;
    align-items:end;
}

.form-group label{
    display:block;
    font-size:13px;
    font-weight:bold;
    margin-bottom:6px;
    color:#455a64;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:11px;
    border:1px solid #cfd8dc;
    border-radius:7px;
    outline:none;
    font-size:14px;
}

.form-group textarea{
    min-height:75px;
    resize:vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    border-color:#00a6a6;
    box-shadow:0 0 0 2px rgba(0,166,166,.12);
}

.stock-info,
.purchase-info{
    margin-top:5px;
    font-size:12px;
    color:#607d8b;
}

.purchase-info{
    color:#1976d2;
    font-weight:bold;
}


/* =========================
   SEARCH RESULTS
========================= */

.medicine-results{
    margin-top:7px;
    border:1px solid #cfd8dc;
    border-radius:8px;
    background:#fff;
    max-height:260px;
    overflow-y:auto;
    box-shadow:0 3px 10px rgba(0,0,0,.08);
}

.medicine-results:empty{
    display:none;
}

.medicine-result{
    width:100%;
    border:none;
    border-bottom:1px solid #eeeeee;
    background:#fff;
    padding:11px 12px;
    text-align:left;
    cursor:pointer;
    display:block;
    font-size:14px;
    color:#263238;
}

.medicine-result:last-child{
    border-bottom:none;
}

.medicine-result:hover{
    background:#e0f7f7;
}

.medicine-result-name{
    font-weight:bold;
    color:#007c83;
}

.medicine-result-info{
    margin-top:3px;
    font-size:11px;
    color:#607d8b;
}

.medicine-result-h1{
    color:#c2410c;
    font-weight:bold;
}

.no-medicine-result{
    padding:12px;
    text-align:center;
    color:#78909c;
    font-size:13px;
}

#medicine_id{
    display:none;
}

.expiry-warning{
    margin-top:8px;
    padding:10px;
    background:#fee2e2;
    border:1px solid #ef4444;
    color:#b91c1c;
    border-radius:7px;
    font-size:13px;
    font-weight:bold;
}

.h1-box{
    margin-top:15px;
    padding:13px;
    background:#fff7ed;
    border:1px solid #fb923c;
    border-radius:9px;
}

.h1-title{
    color:#c2410c;
    font-weight:bold;
}

.patient-grid{
    display:grid;
    grid-template-columns:1fr 1fr 1.5fr;
    gap:12px;
}

.check-row{
    display:flex;
    gap:8px;
    align-items:center;
    margin-top:10px;
}

.btn{
    border:none;
    padding:11px 18px;
    border-radius:7px;
    cursor:pointer;
    font-weight:bold;
    text-decoration:none;
    display:inline-block;
}

.btn-primary{
    background:#007c83;
    color:#fff;
}

.btn-primary:hover{
    background:#00656a;
}

.btn-danger{
    background:#dc2626;
    color:#fff;
}

.btn-secondary{
    background:#607d8b;
    color:#fff;
}

.btn-success{
    background:#16a34a;
    color:#fff;
}

.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#007c83;
    color:#fff;
    padding:12px;
    text-align:center;
}

td{
    padding:11px;
    border-bottom:1px solid #e0e0e0;
    text-align:center;
}

.total-box{
    display:flex;
    justify-content:flex-end;
    margin-top:20px;
}

.total{
    background:#e0f7f7;
    padding:15px 25px;
    border-radius:10px;
    color:#007c83;
    font-size:21px;
    font-weight:bold;
}

.payment-section{
    display:flex;
    justify-content:space-between;
    gap:20px;
    align-items:flex-start;
    flex-wrap:wrap;
}

.payment-method{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.payment-method label{
    padding:10px 15px;
    border:1px solid #cfd8dc;
    border-radius:7px;
    cursor:pointer;
}

.payment-method label:hover{
    background:#e0f7f7;
}

.upi-box{
    display:none;
    margin-top:18px;
    padding:18px;
    background:#f0fdfa;
    border:1px solid #99f6e4;
    border-radius:10px;
    text-align:center;
}

.qrcode{
    display:flex;
    justify-content:center;
    align-items:center;
    margin:12px auto;
    min-height:10px;
}

.qrcode img,
.qrcode canvas{
    margin:auto;
    max-width:220px;
}

.generated{
    border:2px solid #16a34a;
    background:#f0fdf4;
}

.bill-header{
    text-align:center;
    border-bottom:2px solid #007c83;
    padding-bottom:15px;
    margin-bottom:15px;
}

.bill-header h1{
    color:#007c83;
    margin:0;
}

.bill-header p{
    margin:4px 0;
}

.bill-meta{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-bottom:15px;
}

.bill-meta div{
    padding:4px;
}

.bill-table th,
.bill-table td{
    font-size:12px;
    padding:7px;
}

.bill-summary{
    margin-left:auto;
    width:330px;
    max-width:100%;
    margin-top:15px;
}

.summary-row{
    display:flex;
    justify-content:space-between;
    padding:5px 0;
}

.summary-row.net{
    font-weight:bold;
    font-size:16px;
    border-top:1px solid #555;
    padding-top:8px;
}

.signature{
    margin-top:20px;
    text-align:right;
    font-weight:bold;
}

.jurisdiction{
    text-align:center;
    margin-top:16px;
    padding-top:9px;
    border-top:1px dashed #777;
    font-size:12px;
    font-weight:bold;
    color:#333;
}

.legal-line{
    text-align:center;
    font-size:11px;
    margin-top:7px;
}

@media(max-width:900px){

    .form-grid{
        grid-template-columns:1fr 1fr;
    }

    .patient-grid{
        grid-template-columns:1fr;
    }

    .bill-meta{
        grid-template-columns:1fr;
    }
}

@media(max-width:600px){

    .form-grid{
        grid-template-columns:1fr;
    }

    .topbar{
        padding:14px;
    }

    .container{
        width:96%;
    }
}

@media print{

    body *{
        visibility:hidden!important;
    }

    #printBill,
    #printBill *{
        visibility:visible!important;
    }

    #printBill{
        position:absolute;
        left:0;
        top:0;
        width:100%;
        margin:0;
        padding:15px;
        box-shadow:none;
        border:none;
        background:#fff;
    }

    #printBill .no-print{
        display:none!important;
    }

    body{
        background:#fff!important;
    }
}

</style>

</head>


<body>


<div class="topbar no-print">

    <div class="logo">
        💊 MediTrack
    </div>

    <a href="../dashboard.php">
        ← Dashboard
    </a>

</div>


<div class="container">


<div class="page-title no-print">

    <h1>Billing &amp; Sales</h1>

    <p>
        Create a new medicine bill
    </p>

</div>


<?php if ($message !== ''): ?>

<div class="message <?= e($message_type) ?> no-print">
    <?= e($message) ?>
</div>

<?php endif; ?>


<!-- =========================
     ADD MEDICINE
========================= -->

<div class="card no-print">

<h2>➕ Add Medicine</h2>


<div class="type-tabs" id="typeTabs">

    <button
        type="button"
        class="type-tab active"
        data-type="General"
    >
        General
    </button>

    <button
        type="button"
        class="type-tab"
        data-type="Schedule"
    >
        Schedule
    </button>

    <button
        type="button"
        class="type-tab"
        data-type="Veterinary"
    >
        Veterinary
    </button>

</div>


<form method="POST" id="addForm">

<input
    type="hidden"
    name="medicine_type"
    id="medicine_type"
    value="General"
>


<div class="form-grid">


<div class="form-group">

<label>
    Search Medicine
</label>

<input
    type="text"
    id="medicineSearch"
    placeholder="Type medicine name or barcode..."
    autocomplete="off"
>

<!-- SEARCH RESULTS -->
<div
    id="medicineResults"
    class="medicine-results"
></div>

</div>


<div class="form-group">

<label>
    Medicine
</label>

<select
    name="medicine_id"
    id="medicine_id"
    required
>

<option value="">
    Select medicine
</option>

</select>


<div
    class="stock-info"
    id="stockInfo"
></div>


<div
    class="purchase-info"
    id="purchaseInfo"
></div>


<div
    class="stock-info"
    id="priceInfo"
></div>


<div
    id="expiryWarning"
    class="expiry-warning"
    style="display:none"
></div>

</div>


<div class="form-group">

<label>
    Quantity (Units)
</label>

<input
    type="number"
    name="quantity"
    id="quantity"
    min="1"
    value="1"
    required
>

</div>


<div class="form-group">

<label>
    Selling Price / Unit
</label>

<input
    type="number"
    id="selling_price"
    step="0.01"
    readonly
    required
>

</div>


<div>

<button
    type="submit"
    name="add_medicine"
    class="btn btn-primary"
>
    + Add
</button>

</div>


</div>

</form>

</div>


<!-- =========================
     PATIENT DETAILS
========================= -->

<div class="card no-print">

<h2>
    👤 Patient / Doctor Details
</h2>


<?php if ($cart_has_h1): ?>

<div class="h1-box">

<div class="h1-title">
    ⚠ H1 MEDICINE / PRESCRIPTION REQUIRED
</div>

<div style="font-size:12px;margin-top:5px">

Patient Name, Doctor Name and prescription
verification are required because this bill
contains an H1 medicine.

</div>

</div>

<?php endif; ?>


<form method="POST">


<div
    class="patient-grid"
    style="margin-top:15px"
>


<div class="form-group">

<label>
    Patient Name<?= $cart_has_h1 ? ' *' : '' ?>
</label>

<input
    type="text"
    name="patient_name"
    value="<?= e($_SESSION['bill_patient']['name'] ?? '') ?>"
    <?= $cart_has_h1 ? 'required' : '' ?>
>

</div>


<div class="form-group">

<label>
    Doctor Name<?= $cart_has_h1 ? ' *' : '' ?>
</label>

<input
    type="text"
    name="doctor_name"
    value="<?= e($_SESSION['bill_patient']['doctor'] ?? '') ?>"
    <?= $cart_has_h1 ? 'required' : '' ?>
>

</div>


<div class="form-group">

<label>
    Patient Address
</label>

<textarea
    name="patient_address"
    placeholder="Patient address"
><?= e($_SESSION['bill_patient']['address'] ?? '') ?></textarea>

</div>


</div>


<?php if ($cart_has_h1): ?>

<label class="check-row">

<input
    type="checkbox"
    name="prescription_verified"
    value="1"
    <?= !empty($_SESSION['bill_patient']['prescription_verified']) ? 'checked' : '' ?>
>

Prescription Verified

</label>

<?php endif; ?>


<button
    type="submit"
    name="save_patient"
    class="btn btn-secondary"
    style="margin-top:12px"
>
    Save Details
</button>

</form>

</div>


<!-- =========================
     CURRENT BILL
========================= -->

<div class="card no-print">

<h2>
    🧾 Current Bill
</h2>


<?php if (empty($cart_items)): ?>

<p style="text-align:center;color:#78909c">

No medicines added to bill yet.

</p>


<?php else: ?>


<div class="table-wrapper">

<table>

<thead>

<tr>

<th>Medicine</th>
<th>Pack</th>
<th>Qty</th>
<th>Rate/Unit</th>
<th>Total</th>
<th>Action</th>

</tr>

</thead>


<tbody>


<?php foreach ($cart_items as $item): ?>

<tr>

<td>

<?= e($item['medicine_name'] ?? '') ?>

<?php if (!empty($item['is_h1'])): ?>

<br>

<small
    style="color:#c2410c;font-weight:bold"
>
    H1
</small>

<?php endif; ?>

</td>


<td>
    1 × <?= (int)($item['pack_size'] ?? 1) ?>
</td>


<td>
    <?= (int)($item['quantity'] ?? 0) ?>
</td>


<td>
    ₹<?= number_format(
        (float)($item['unit_price'] ?? 0),
        2
    ) ?>
</td>


<td>
    ₹<?= number_format(
        (float)($item['total'] ?? 0),
        2
    ) ?>
</td>


<td>

<a
    class="btn btn-danger"
    href="bill.php?remove=<?= (int)($item['id'] ?? 0) ?>"
>
    Remove
</a>

</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>


<div class="total-box">

<div class="total">

Gross Total:
₹<?= number_format($cart_total,2) ?>

</div>

</div>


<a
    href="bill.php?clear_cart=1"
    class="btn btn-danger"
    style="margin-top:12px"
>
    Clear Bill
</a>


<?php endif; ?>

</div>


<!-- =========================
     PAYMENT
========================= -->

<?php if (!empty($cart_items)): ?>

<div class="card no-print">

<h2>
    💳 Payment &amp; Checkout
</h2>


<form
    method="POST"
    onsubmit="return confirmCheckout();"
>


<input
    type="hidden"
    name="patient_name"
    value="<?= e($_SESSION['bill_patient']['name'] ?? '') ?>"
    id="checkoutPatient"
>


<input
    type="hidden"
    name="patient_address"
    value="<?= e($_SESSION['bill_patient']['address'] ?? '') ?>"
    id="checkoutAddress"
>


<input
    type="hidden"
    name="doctor_name"
    value="<?= e($_SESSION['bill_patient']['doctor'] ?? '') ?>"
    id="checkoutDoctor"
>


<?php if (
    !empty(
        $_SESSION['bill_patient']
        ['prescription_verified']
    )
): ?>

<input
    type="hidden"
    name="prescription_verified"
    value="1"
>

<?php endif; ?>


<div class="payment-section">


<div>


<div class="payment-method">


<label>

<input
    type="radio"
    name="payment_method"
    value="UPI"
    onchange="paymentChanged()"
>

UPI

</label>


<label>

<input
    type="radio"
    name="payment_method"
    value="Cash"
    onchange="paymentChanged()"
>

Cash

</label>


<label>

<input
    type="radio"
    name="payment_method"
    value="Card"
    onchange="paymentChanged()"
>

Card

</label>


</div>


<div
    class="form-group"
    style="margin-top:15px"
>

<label>
    Discount
</label>

<input
    type="number"
    name="discount"
    id="discount"
    value="0"
    min="0"
    step="0.01"
    oninput="updateTotals()"
>

</div>


</div>


<div
    class="total-box"
    style="margin-top:0"
>

<div
    class="total"
    id="netTotalDisplay"
>

Net Total:
₹<?= number_format($cart_total,2) ?>

</div>

</div>


</div>


<!-- UPI QR -->

<div
    class="upi-box"
    id="upiBox"
>

<strong>
    Scan to Pay
</strong>


<div
    id="qrcode"
    class="qrcode"
></div>


<div
    id="upiStatus"
    style="font-size:12px;color:#607d8b"
></div>

</div>


<button
    type="submit"
    name="checkout"
    class="btn btn-success"
    style="margin-top:18px"
>

Generate Bill

</button>


</form>

</div>

<?php endif; ?>


<!-- =========================
     GENERATED BILL
========================= -->

<?php if ($last_bill): ?>

<div
    class="card generated"
    id="printBill"
>


<div class="bill-header">

<h1>
    GST INVOICE
</h1>


<p>
<strong>
<?= e(
    $pharmacy['shop_name']
    ?: $pharmacy['full_name']
) ?>
</strong>
</p>


<?php if (!empty($pharmacy['shop_address'])): ?>

<p>
<?= nl2br(
    e($pharmacy['shop_address'])
) ?>
</p>

<?php endif; ?>


<?php if (!empty($pharmacy['mobile'])): ?>

<p>
Phone:
<?= e($pharmacy['mobile']) ?>
</p>

<?php endif; ?>


<?php if (!empty($pharmacy['medical_license_no'])): ?>

<p>

<strong>
Drug / Medical License No.:
<?= e($pharmacy['medical_license_no']) ?>
</strong>

</p>

<?php endif; ?>


<?php if (!empty($pharmacy['gst_no'])): ?>

<p>
GST No.:
<?= e($pharmacy['gst_no']) ?>
</p>

<?php endif; ?>


</div>


<!-- BILL DETAILS -->

<div class="bill-meta">


<div>

<strong>
Invoice No.:
</strong>

<?= e(
    $last_bill['invoice_no']
    ?? ''
) ?>

</div>


<div>

<strong>
Date:
</strong>

<?= e(
    $last_bill['date']
    ?? ''
) ?>

<?= e(
    $last_bill['time']
    ?? ''
) ?>

</div>


<div>

<strong>
Patient:
</strong>

<?= e(
    $last_bill['patient']['name']
    ?? ''
) ?>

</div>


<div>

<strong>
Doctor:
</strong>

<?= e(
    $last_bill['patient']['doctor']
    ?? ''
) ?>

</div>


<div style="grid-column:1/-1">

<strong>
Patient Address:
</strong>

<?= e(
    $last_bill['patient']['address']
    ?? ''
) ?>

</div>


</div>


<!-- MEDICINE TABLE -->

<div class="table-wrapper">

<table class="bill-table">

<thead>

<tr>

<th>Sr.</th>
<th>Product Description</th>
<th>Pack</th>
<th>MRP/Rate</th>
<th>Batch No.</th>
<th>Exp.</th>
<th>Qty</th>
<th>Amount</th>

</tr>

</thead>


<tbody>


<?php

$sr = 1;

foreach (
    ($last_bill['items'] ?? [])
    as $item
):

?>

<tr>


<td>
<?= $sr++ ?>
</td>


<td style="text-align:left">

<?= e(
    $item['medicine_name']
    ?? ''
) ?>


<?php if (!empty($item['is_h1'])): ?>

<br>

<small>
SCHEDULE H1 MEDICINE
</small>

<?php endif; ?>


</td>


<td>

1 ×
<?= (int)(
    $item['pack_size']
    ?? 1
) ?>

</td>


<td>

₹<?= number_format(
    (float)(
        $item['unit_price']
        ?? 0
    ),
    2
) ?>

</td>


<td>

<?= e(
    $item['batch_no']
    ?? ''
) ?>

</td>


<td>

<?php

if (!empty($item['expiry_date'])) {

    echo e(
        date(
            'm/Y',
            strtotime(
                $item['expiry_date']
            )
        )
    );
}

?>

</td>


<td>

<?= (int)(
    $item['quantity']
    ?? 0
) ?>

</td>


<td>

₹<?= number_format(
    (float)(
        $item['total']
        ?? 0
    ),
    2
) ?>

</td>


</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>


<!-- BILL TOTAL -->

<div class="bill-summary">


<div class="summary-row">

<span>
GROSS:
</span>

<span>

₹<?= number_format(
    (float)(
        $last_bill['gross_total']
        ?? 0
    ),
    2
) ?>

</span>

</div>


<?php if (
    (float)(
        $last_bill['discount']
        ?? 0
    ) > 0
): ?>

<div class="summary-row">

<span>
DISCOUNT:
</span>

<span>

₹<?= number_format(
    (float)(
        $last_bill['discount']
    ),
    2
) ?>

</span>

</div>

<?php endif; ?>


<div class="summary-row net">

<span>
NET AMT:
</span>

<span>

₹<?= number_format(
    (float)(
        $last_bill['net_total']
        ?? 0
    ),
    2
) ?>

</span>

</div>


</div>


<div style="margin-top:12px">

<strong>
Payment Mode:
</strong>

<?= e(
    $last_bill['payment_method']
    ?? ''
) ?>

</div>


<?php

$last_bill_has_h1 = false;

foreach (
    ($last_bill['items'] ?? [])
    as $bill_item
) {

    if (!empty($bill_item['is_h1'])) {

        $last_bill_has_h1 = true;

        break;
    }
}

?>


<?php if ($last_bill_has_h1): ?>

<div
    style="margin-top:8px;font-size:11px"
>

*** SCHEDULE H1 MEDICINE.
PRESCRIPTION VERIFIED.

</div>

<?php endif; ?>


<!-- EXACT CLIENT FOOTER -->

<div class="legal-line">

*** OVERCASE IF ANY IS REFUNDABLE,E.&amp; O.E. SCHL.H.L

</div>


<div class="jurisdiction">

*** All disputes subject to MANMAD jurisdiction.

</div>


<div class="signature">

SIGN OF...........

</div>


<!-- PRINT BUTTONS -->

<div
    class="no-print"
    style="margin-top:20px;text-align:center"
>

<button
    onclick="printBillOnly()"
    class="btn btn-primary"
>

🖨 Print Bill

</button>


<a
    href="bill.php?new=1"
    class="btn btn-secondary"
>

+ New Bill

</a>

</div>


</div>

<?php endif; ?>


</div>


<script>


/* =========================
   MEDICINE DATA
========================= */

const medicines =
<?= json_encode(
    $medicine_data,
    JSON_UNESCAPED_UNICODE
    |
    JSON_UNESCAPED_SLASHES
) ?>;


const upiId =
<?= json_encode(
    $upi_id,
    JSON_UNESCAPED_UNICODE
    |
    JSON_UNESCAPED_SLASHES
) ?>;


const shopName =
<?= json_encode(
    $pharmacy['shop_name']
    ?: $pharmacy['full_name'],
    JSON_UNESCAPED_UNICODE
    |
    JSON_UNESCAPED_SLASHES
) ?>;


const grossTotal =
<?= json_encode(
    (float)$cart_total
) ?>;


let selectedType =
'General';


let qrLibraryLoading =
false;


/* =========================
   SEARCH MEDICINES
========================= */

function renderMedicines()
{
    const searchInput =
        document.getElementById('medicineSearch');

    const select =
        document.getElementById('medicine_id');

    const results =
        document.getElementById('medicineResults');

    if (!searchInput || !select || !results) {
        return;
    }


    const search =
        String(searchInput.value || '')
            .toLowerCase()
            .trim();


    /*
     * Remember selected medicine
     * before rebuilding the list.
     */
    const oldValue =
        select.value;


    /*
     * Clear old select options.
     */
    select.innerHTML =
        '<option value="">Select medicine</option>';


    /*
     * Clear visible search results.
     */
    results.innerHTML =
        '';


    let matchCount = 0;


    medicines.forEach(function(m)
    {

        /*
         * CATEGORY FILTER
         *
         * Only medicines from the
         * selected category are shown.
         */
        if (
            String(m.type || 'General')
            !==
            String(selectedType)
        ) {
            return;
        }


        /*
         * MEDICINE NAME
         */
        const medicineName =
            String(m.name || '')
                .toLowerCase()
                .trim();


        /*
         * BARCODE
         */
        const medicineBarcode =
            String(m.barcode || '')
                .toLowerCase()
                .trim();


        /*
         * SEARCH
         *
         * If search is empty:
         * show ALL medicines.
         *
         * If user types one letter:
         * show ALL medicines containing
         * that letter.
         *
         * Example:
         * a
         * am
         * para
         */
        if (search !== '')
        {

            const nameMatch =
                medicineName.includes(search);


            const barcodeMatch =
                medicineBarcode.includes(search);


            if (
                !nameMatch
                &&
                !barcodeMatch
            ) {
                return;
            }
        }


        matchCount++;


        /*
         * CREATE SELECT OPTION
         *
         * This is kept because
         * medicineChanged() uses it.
         */
        const option =
            document.createElement('option');


        option.value =
            m.id;


        option.textContent =
            String(m.name || '')
            +
            (
                Number(m.is_h1) === 1
                ? ' — H1'
                : ''
            );


        option.dataset.expiry =
            m.expiry_date || '';


        option.dataset.stock =
            m.quantity || 0;


        option.dataset.pack =
            m.pack_size || 1;


        option.dataset.price =
            m.pack_price || 0;


        option.dataset.purchase =
            m.purchase_price || 0;


        option.dataset.h1 =
            m.is_h1 || 0;


        option.dataset.batch =
            m.batch_no || '';


        select.appendChild(option);


        /*
         * CREATE VISIBLE SEARCH RESULT
         */
        const resultButton =
            document.createElement('button');


        resultButton.type =
            'button';


        resultButton.className =
            'medicine-result';


        /*
         * Medicine name
         */
        const nameDiv =
            document.createElement('div');


        nameDiv.className =
            'medicine-result-name';


        nameDiv.textContent =
            String(m.name || '');


        /*
         * H1 badge
         */
        if (Number(m.is_h1) === 1)
        {

            const h1Span =
                document.createElement('span');


            h1Span.className =
                'medicine-result-h1';


            h1Span.textContent =
                '  — H1';


            nameDiv.appendChild(
                h1Span
            );
        }


        /*
         * Medicine information
         */
        const infoDiv =
            document.createElement('div');


        infoDiv.className =
            'medicine-result-info';


        const stock =
            Number(m.quantity || 0);


        const pack =
            Number(m.pack_size || 1);


        let infoText =
            'Stock: '
            +
            stock
            +
            ' unit(s)'
            +
            ' | Pack: 1 × '
            +
            pack;


        if (m.barcode)
        {
            infoText +=
                ' | Barcode: '
                +
                String(m.barcode);
        }


        infoDiv.textContent =
            infoText;


        resultButton.appendChild(
            nameDiv
        );


        resultButton.appendChild(
            infoDiv
        );


        /*
         * CLICK RESULT
         */
        resultButton.addEventListener(
            'click',
            function()
            {

                selectMedicineFromSearch(
                    m.id
                );

            }
        );


        results.appendChild(
            resultButton
        );

    });


    /*
     * No medicine found
     */
    if (matchCount === 0)
    {

        const noResult =
            document.createElement('div');


        noResult.className =
            'no-medicine-result';


        if (search === '')
        {

            noResult.textContent =
                'No medicines available in this category.';

        }
        else
        {

            noResult.textContent =
                'No medicine found for "' +
                search +
                '".';

        }


        results.appendChild(
            noResult
        );
    }


    /*
     * Restore previous selected medicine
     * if it still exists in the list.
     */
    if (
        oldValue
        &&
        select.querySelector(
            'option[value="' +
            oldValue +
            '"]'
        )
    )
    {

        select.value =
            oldValue;
    }


    medicineChanged();
}


/* =========================
   SELECT MEDICINE FROM SEARCH
========================= */

function selectMedicineFromSearch(id)
{

    const select =
        document.getElementById(
            'medicine_id'
        );


    if (!select) {
        return;
    }


    /*
     * Set selected medicine.
     */
    select.value =
        String(id);


    /*
     * Update price, stock,
     * purchase and expiry.
     */
    medicineChanged();


    /*
     * Keep search text.
     * User can immediately see
     * which medicine was selected.
     */


    /*
     * Highlight selected result.
     */
    document
        .querySelectorAll(
            '.medicine-result'
        )
        .forEach(function(button)
        {
            button.style.background =
                '';

            button.style.borderLeft =
                '';
        });


    const selectedMedicine =
        medicines.find(
            function(m)
            {
                return Number(m.id) ===
                    Number(id);
            }
        );


    if (selectedMedicine)
    {

        const buttons =
            document.querySelectorAll(
                '.medicine-result'
            );


        buttons.forEach(function(button)
        {

            const nameElement =
                button.querySelector(
                    '.medicine-result-name'
                );


            if (
                nameElement
                &&
                nameElement.textContent
                    .replace('  — H1', '')
                    .trim()
                    ===
                    String(
                        selectedMedicine.name
                    ).trim()
            )
            {

                button.style.background =
                    '#e0f7f7';

                button.style.borderLeft =
                    '4px solid #007c83';
            }

        });
    }
}


/* =========================
   MEDICINE SELECTED
========================= */

function medicineChanged()
{

    const select =
        document.getElementById(
            'medicine_id'
        );


    const option =
        select.options[
            select.selectedIndex
        ];


    const warning =
        document.getElementById(
            'expiryWarning'
        );


    if (
        !option
        ||
        !option.value
    ) {

        document.getElementById(
            'stockInfo'
        ).textContent = '';


        document.getElementById(
            'purchaseInfo'
        ).textContent = '';


        document.getElementById(
            'priceInfo'
        ).textContent = '';


        document.getElementById(
            'selling_price'
        ).value = '';


        warning.style.display =
            'none';

        return;
    }


    const stock =
        Number(
            option.dataset.stock
            ||
            0
        );


    const pack =
        Number(
            option.dataset.pack
            ||
            1
        );


    const price =
        Number(
            option.dataset.price
            ||
            0
        );


    const purchase =
        Number(
            option.dataset.purchase
            ||
            0
        );


    const expiry =
        option.dataset.expiry
        ||
        '';


    document.getElementById(
        'stockInfo'
    ).textContent =
        'Available: '
        +
        stock
        +
        ' unit(s) | Pack: 1 × '
        +
        pack;


    document.getElementById(
        'purchaseInfo'
    ).textContent =
        'Purchase price/pack: ₹'
        +
        purchase.toFixed(2);


    document.getElementById(
        'selling_price'
    ).value =
        (
            price / pack
        ).toFixed(2);


    document.getElementById(
        'priceInfo'
    ).textContent =
        'Pack selling price: ₹'
        +
        price.toFixed(2)
        +
        ' | Unit price: ₹'
        +
        (
            price / pack
        ).toFixed(2);


    /* EXPIRED WARNING */

    if (
        expiry
        &&
        isExpiredDate(expiry)
    ) {

        warning.style.display =
            'block';


        warning.textContent =
            '⚠ MEDICINE EXPIRED — Expired on '
            +
            formatDate(expiry)
            +
            '. It cannot be sold.';

    } else {

        warning.style.display =
            'none';

        warning.textContent =
            '';
    }
}


/* =========================
   EXPIRY DATE
========================= */

function isExpiredDate(dateString)
{

    if (!dateString) {
        return false;
    }


    const today =
        new Date();


    today.setHours(
        0,
        0,
        0,
        0
    );


    const expiry =
        new Date(
            dateString
            +
            'T00:00:00'
        );


    expiry.setHours(
        0,
        0,
        0,
        0
    );


    return expiry < today;
}


function formatDate(dateString)
{

    const p =
        dateString.split('-');


    if (p.length !== 3) {
        return dateString;
    }


    return (
        p[2]
        +
        '-'
        +
        p[1]
        +
        '-'
        +
        p[0]
    );
}


/* =========================
   CATEGORY BUTTONS
========================= */

document
.querySelectorAll(
    '.type-tab'
)
.forEach(
    button =>
    {

        button.addEventListener(
            'click',
            function()
            {

                document
                .querySelectorAll(
                    '.type-tab'
                )
                .forEach(
                    b =>
                    b.classList
                    .remove(
                        'active'
                    )
                );


                this.classList
                    .add(
                        'active'
                    );


                selectedType =
                    this.dataset.type;


                document
                .getElementById(
                    'medicine_type'
                )
                .value =
                    selectedType;


                document
                .getElementById(
                    'medicineSearch'
                )
                .value =
                    '';


                renderMedicines();
            }
        );
    }
);


/* =========================
   SEARCH
========================= */

document
.getElementById(
    'medicineSearch'
)
.addEventListener(
    'input',
    renderMedicines
);


/* =========================
   SELECT
========================= */

document
.getElementById(
    'medicine_id'
)
.addEventListener(
    'change',
    medicineChanged
);


/* =========================
   STOP EXPIRED MEDICINE
========================= */

document
.getElementById(
    'addForm'
)
.addEventListener(
    'submit',
    function(e)
    {

        const option =
            document
            .getElementById(
                'medicine_id'
            )
            .selectedOptions[0];


        if (
            !option
            ||
            !option.value
        ) {
            return;
        }


        const expiry =
            option.dataset.expiry
            ||
            '';


        if (
            expiry
            &&
            isExpiredDate(expiry)
        ) {

            alert(
                'MEDICINE EXPIRED — This medicine cannot be sold.'
            );


            e.preventDefault();
        }
    }
);


/* =========================
   TOTAL
========================= */

function updateTotals()
{

    const discount =
        Math.max(
            0,
            Number(
                document
                .getElementById(
                    'discount'
                )
                ?.value
                ||
                0
            )
        );


    const net =
        Math.max(
            0,
            grossTotal - discount
        );


    const display =
        document.getElementById(
            'netTotalDisplay'
        );


    if (display) {

        display.textContent =
            'Net Total: ₹'
            +
            net.toFixed(2);
    }


    const checked =
        document
        .querySelector(
            'input[name="payment_method"]:checked'
        );


    if (
        checked
        &&
        checked.value === 'UPI'
    ) {

        generateQR();
    }
}


/* =========================
   PAYMENT
========================= */

function paymentChanged()
{

    const checked =
        document.querySelector(
            'input[name="payment_method"]:checked'
        );


    const box =
        document.getElementById(
            'upiBox'
        );


    if (!checked || !box) {
        return;
    }


    if (
        checked.value === 'UPI'
    ) {

        box.style.display =
            'block';


        generateQR();

    } else {

        box.style.display =
            'none';
    }
}


/* =========================
   LOAD QR LIBRARY
========================= */

function loadQRLibrary(callback)
{

    if (
        typeof QRCode !==
        'undefined'
    ) {

        callback();

        return;
    }


    if (qrLibraryLoading) {

        setTimeout(
            function()
            {
                loadQRLibrary(
                    callback
                );
            },
            250
        );

        return;
    }


    qrLibraryLoading =
        true;


    const urls = [

        'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',

        'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'

    ];


    let index = 0;


    function tryNext()
    {

        if (
            typeof QRCode !==
            'undefined'
        ) {

            qrLibraryLoading =
                false;

            callback();

            return;
        }


        if (
            index >=
            urls.length
        ) {

            qrLibraryLoading =
                false;


            const status =
                document.getElementById(
                    'upiStatus'
                );


            if (status) {

                status.textContent =
                    'QR library could not load. Please check internet connection.';
            }

            return;
        }


        const script =
            document.createElement(
                'script'
            );


        script.src =
            urls[index++];


        script.onload =
            function()
            {

                qrLibraryLoading =
                    false;

                callback();
            };


        script.onerror =
            tryNext;


        document.head.appendChild(
            script
        );
    }


    tryNext();
}


/* =========================
   GENERATE UPI QR
========================= */

function generateQR()
{

    const qr =
        document.getElementById(
            'qrcode'
        );


    const status =
        document.getElementById(
            'upiStatus'
        );


    if (!qr || !status) {
        return;
    }


    qr.innerHTML =
        '';


    status.textContent =
        '';


    if (!upiId) {

        status.textContent =
            'UPI ID is not configured in account settings.';

        return;
    }


    const discount =
        Math.max(
            0,
            Number(
                document
                .getElementById(
                    'discount'
                )
                ?.value
                ||
                0
            )
        );


    const amount =
        Math.max(
            0,
            grossTotal - discount
        );


    if (amount <= 0) {

        status.textContent =
            'Invalid bill amount.';

        return;
    }


    const upiLink =
        'upi://pay?pa='
        +
        encodeURIComponent(
            upiId
        )
        +
        '&pn='
        +
        encodeURIComponent(
            shopName
        )
        +
        '&am='
        +
        encodeURIComponent(
            amount.toFixed(2)
        )
        +
        '&cu=INR';


    loadQRLibrary(
        function()
        {

            qr.innerHTML =
                '';


            try {

                new QRCode(
                    qr,
                    {
                        text: upiLink,
                        width:220,
                        height:220,
                        correctLevel:
                            QRCode
                            .CorrectLevel
                            .H
                    }
                );


                status.textContent =
                    'Scan this QR code to pay ₹'
                    +
                    amount.toFixed(2);

            } catch(error) {

                status.textContent =
                    'Unable to generate QR code.';
            }

        }
    );
}


/* =========================
   CHECKOUT CONFIRMATION
========================= */

function confirmCheckout()
{

    const checked =
        document.querySelector(
            'input[name="payment_method"]:checked'
        );


    if (!checked) {

        alert(
            'Please select UPI, Cash or Card.'
        );

        return false;
    }


    <?php if ($cart_has_h1): ?>

    const patient =
        document
        .getElementById(
            'checkoutPatient'
        )
        ?.value
        .trim();


    const doctor =
        document
        .getElementById(
            'checkoutDoctor'
        )
        ?.value
        .trim();


    const verified =
        <?= !empty(
            $_SESSION['bill_patient']
            ['prescription_verified']
        )
        ? 'true'
        : 'false' ?>;


    if (
        !patient
        ||
        !doctor
        ||
        !verified
    ) {

        alert(
            'H1 medicine requires Patient Name, Doctor Name and Prescription Verified.'
        );

        return false;
    }

    <?php endif; ?>


    return confirm(
        'Are you sure you want to generate this bill?'
    );
}


/* =========================
   PRINT
========================= */

function printBillOnly()
{
    window.print();
}


/* =========================
   INITIAL MEDICINE LIST
========================= */

renderMedicines();

</script>


</body>
</html>