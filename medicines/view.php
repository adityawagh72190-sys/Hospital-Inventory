<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)
{
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");

/* =========================
   LOGGED-IN USER ID
========================= */

$user_id = $_SESSION['user_id'] ?? 0;

/* =========================
   FETCH ONLY ACTIVE OWNER MEDICINES
========================= */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM medicines
     WHERE user_id = '$user_id'
     AND is_active = 1
     ORDER BY medicine_name ASC"
);

/* =========================
   DATABASE ERROR CHECK
========================= */

if (!$result)
{
    die(
        "Database Error: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Medicines List - MediTrack</title>

<style>

/* =========================
   BODY
========================= */

body{
    font-family:Arial,sans-serif;

    margin:0;
    padding:30px;

    background:
    linear-gradient(
        rgba(255,255,255,0.72),
        rgba(255,255,255,0.72)
    ),
    url('https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1600&q=80')
    no-repeat center center fixed;

    background-size:cover;
}

/* =========================
   CONTAINER
========================= */

.container{
    max-width:1500px;
    margin:auto;
}

/* =========================
   TOP BAR
========================= */

.topbar{
    background:rgba(30,58,138,0.92);

    color:white;

    padding:15px 25px;

    border-radius:16px;

    margin-bottom:20px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    box-shadow:
    0 4px 12px
    rgba(0,0,0,0.15);
}

.topbar-title{
    font-size:24px;
    font-weight:bold;
}

.topbar-buttons{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.topbar a{
    background:#2563eb;

    color:white;

    text-decoration:none;

    padding:10px 16px;

    border-radius:10px;

    font-weight:bold;
}

.topbar a:hover{
    background:#1d4ed8;
}

/* =========================
   FILTER BOX
========================= */

.filter-box{
    background:rgba(255,255,255,0.96);

    padding:20px;

    border-radius:16px;

    margin-bottom:20px;

    box-shadow:
    0 6px 18px
    rgba(0,0,0,0.12);
}

/* =========================
   SEARCH
========================= */

.search-input{
    width:100%;

    box-sizing:border-box;

    padding:13px 15px;

    border:1px solid #cbd5e1;

    border-radius:10px;

    font-size:15px;

    outline:none;

    margin-bottom:15px;
}

.search-input:focus{
    border-color:#2563eb;

    box-shadow:
    0 0 0 3px
    rgba(37,99,235,0.12);
}

/* =========================
   FILTER BUTTONS
========================= */

.filter-buttons{
    display:flex;

    gap:10px;

    flex-wrap:wrap;
}

.filter-btn{
    border:none;

    padding:10px 18px;

    border-radius:10px;

    cursor:pointer;

    font-weight:bold;

    background:#e2e8f0;

    color:#334155;
}

.filter-btn:hover{
    background:#cbd5e1;
}

.filter-btn.active{
    background:#2563eb;

    color:white;
}

/* =========================
   TABLE BOX
========================= */

.table-box{
    background:rgba(255,255,255,0.96);

    padding:24px;

    border-radius:18px;

    box-shadow:
    0 8px 24px
    rgba(0,0,0,0.14);

    overflow-x:auto;
}

/* =========================
   HEADING
========================= */

.heading-row{
    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:20px;

    gap:15px;

    flex-wrap:wrap;
}

h2{
    color:#1e3a8a;

    margin:0;
}

.medicine-count{
    background:#eff6ff;

    color:#1e40af;

    padding:8px 13px;

    border-radius:8px;

    font-size:13px;

    font-weight:bold;
}

/* =========================
   TABLE
========================= */

table{
    width:100%;

    border-collapse:collapse;

    min-width:2450px;
}

th{
    background:#2563eb;

    color:white;

    padding:14px;

    text-align:left;

    white-space:nowrap;
}

td{
    padding:12px;

    border-bottom:
    1px solid #e5e7eb;

    white-space:nowrap;
}

tr:hover{
    background:#f8fafc;
}

/* =========================
   BARCODE
========================= */

.barcode{
    background:#eff6ff;

    color:#1e40af;

    padding:7px 10px;

    border-radius:7px;

    font-family:monospace;

    font-weight:bold;

    font-size:13px;

    white-space:nowrap;
}

/* =========================
   PRICE
========================= */

.price{
    font-weight:bold;

    color:#16a34a;
}

.purchase-price{
    font-weight:bold;

    color:#dc2626;
}

/* =========================
   STOCK
========================= */

.quantity{
    font-weight:bold;

    color:#ea580c;
}

.stock-units{
    font-weight:bold;

    color:#2563eb;
}

.pack-equivalent{
    color:#475569;

    font-size:13px;

    font-weight:bold;
}

/* =========================
   TYPE BADGES
========================= */

.type-badge{
    display:inline-block;

    padding:6px 10px;

    border-radius:8px;

    font-size:12px;

    font-weight:bold;
}

.general{
    background:#dcfce7;

    color:#166534;
}

.schedule{
    background:#fef3c7;

    color:#92400e;
}

.veterinary{
    background:#ede9fe;

    color:#5b21b6;
}

/* =========================
   H1 BADGES
========================= */

.h1-badge{
    display:inline-block;

    background:#fee2e2;

    color:#b91c1c;

    padding:6px 10px;

    border-radius:8px;

    font-size:12px;

    font-weight:bold;
}

.normal-badge{
    display:inline-block;

    background:#f1f5f9;

    color:#64748b;

    padding:6px 10px;

    border-radius:8px;

    font-size:12px;

    font-weight:bold;
}

/* =========================
   PACK
========================= */

.pack{
    font-weight:bold;

    color:#1e40af;
}

.pack-size{
    color:#475569;

    font-size:13px;
}

/* =========================
   EXPIRY
========================= */

.expired{
    background:#fee2e2 !important;

    color:#b91c1c;

    font-weight:bold;
}

.expiring{
    background:#ffedd5 !important;

    color:#c2410c;

    font-weight:bold;
}

.valid{
    color:#15803d;

    font-weight:bold;
}

.expiry-badge{
    display:block;

    font-size:11px;

    margin-top:4px;
}

/* =========================
   LOW STOCK
========================= */

.low-stock{
    color:#dc2626;

    font-weight:bold;
}

.available-stock{
    color:#16a34a;

    font-weight:bold;
}

/* =========================
   BUTTONS
========================= */

.btn{
    padding:7px 12px;

    border-radius:8px;

    text-decoration:none;

    color:white;

    font-size:14px;

    font-weight:bold;

    margin-right:6px;

    display:inline-block;
}

.edit{
    background:#16a34a;
}

.delete{
    background:#dc2626;
}

.edit:hover{
    background:#15803d;
}

.delete:hover{
    background:#b91c1c;
}

/* =========================
   NO DATA
========================= */

.no-data{
    text-align:center;

    padding:30px;

    color:#64748b;

    font-size:16px;
}

/* =========================
   MOBILE
========================= */

@media(max-width:700px){

    body{
        padding:15px;
    }

    .topbar{
        flex-direction:column;

        align-items:stretch;

        gap:15px;
    }

    .topbar-buttons{
        width:100%;
    }

    .topbar a{
        text-align:center;
    }

}

</style>

</head>

<body>

<div class="container">

<!-- =========================
     TOP BAR
========================= -->

<div class="topbar">

    <div class="topbar-title">
        💊 Medicines List
    </div>

    <div class="topbar-buttons">

        <a href="../dashboard.php">
            🏠 Dashboard
        </a>

        <a href="add.php">
            ➕ Add Medicine
        </a>

    </div>

</div>

<!-- =========================
     FILTER BOX
========================= -->

<div class="filter-box">

    <input
        type="text"
        id="searchInput"
        class="search-input"
        placeholder="🔎 Search medicine by name, barcode, batch or category..."
    >

    <div class="filter-buttons">

        <button
            type="button"
            class="filter-btn active"
            onclick="filterType('All', this)"
        >
            All
        </button>

        <button
            type="button"
            class="filter-btn"
            onclick="filterType('General', this)"
        >
            💊 General
        </button>

        <button
            type="button"
            class="filter-btn"
            onclick="filterType('Schedule', this)"
        >
            📋 Schedule
        </button>

        <button
            type="button"
            class="filter-btn"
            onclick="filterType('Veterinary', this)"
        >
            🐾 Veterinary
        </button>

        <button
            type="button"
            class="filter-btn"
            onclick="filterType('H1', this)"
        >
            ⚠ H1 Medicines
        </button>

        <button
            type="button"
            class="filter-btn"
            onclick="filterType('Expired', this)"
        >
            🔴 Expired
        </button>

    </div>

</div>

<!-- =========================
     TABLE BOX
========================= -->

<div class="table-box">

    <div class="heading-row">

        <h2>
            My Medicines
        </h2>

        <div class="medicine-count">

            Total Medicines:
            <span id="medicineCount">0</span>

        </div>

    </div>

    <table id="medicineTable">

        <thead>

        <tr>

            <th>ID</th>
            <th>Medicine Name</th>
            <th>Barcode</th>
            <th>Medicine Type</th>
            <th>H1 Status</th>
            <th>Category</th>
            <th>Pack Type</th>
            <th>Pack Size</th>
            <th>Batch No</th>
            <th>Expiry Date</th>
            <th>Full Packs</th>
            <th>Loose Units</th>
            <th>Total Units</th>
            <th>Purchase / Pack</th>
            <th>Purchase / Unit</th>
            <th>Selling / Pack</th>
            <th>Selling / Unit</th>
            <th>Supplier ID</th>
            <th>Actions</th>

        </tr>

        </thead>

        <tbody>

        <?php

        if (mysqli_num_rows($result) > 0)
        {

            while ($row = mysqli_fetch_assoc($result))
            {

                /* =========================
                   BASIC VALUES
                ========================= */

                $medicine_type =
                    $row['medicine_type'] ?? 'General';

                $is_h1 =
                    (int)($row['is_h1'] ?? 0);

                $pack_size =
                    max(
                        1,
                        (int)($row['pack_size'] ?? 1)
                    );

                $stock_units =
                    max(
                        0,
                        (int)($row['stock_units'] ?? 0)
                    );

                /* =========================
                   CURRENT STOCK CALCULATION
                ========================= */

                $full_packs =
                    intdiv(
                        $stock_units,
                        $pack_size
                    );

                $loose_units =
                    $stock_units % $pack_size;

                $total_units =
                    $stock_units;

                /* =========================
                   PRICE CALCULATION
                ========================= */

                $purchase_price_pack =
                    (float)(
                        $row['purchase_price'] ?? 0
                    );

                $selling_price_pack =
                    (float)(
                        $row['price'] ?? 0
                    );

                /* =========================
                   UNIT PRICE
                ========================= */

                $purchase_price_unit =
                    $purchase_price_pack /
                    $pack_size;

                $selling_price_unit =
                    $selling_price_pack /
                    $pack_size;

                /* =========================
                   EXPIRY
                ========================= */

                $expiry_date =
                    $row['expiry_date'] ?? '';

                $expiry_class = '';

                $expiry_text = '';

                if (!empty($expiry_date))
                {

                    try
                    {

                        $today =
                            new DateTime(
                                date('Y-m-d')
                            );

                        $expiry =
                            new DateTime(
                                $expiry_date
                            );

                        $days_left =
                            (int)
                            $today
                            ->diff($expiry)
                            ->format('%r%a');

                        if ($days_left < 0)
                        {

                            $expiry_class =
                                'expired';

                            $expiry_text =
                                'EXPIRED';

                        }

                        elseif ($days_left <= 90)
                        {

                            $expiry_class =
                                'expiring';

                            $expiry_text =
                                $days_left .
                                ' days left';

                        }

                        else
                        {

                            $expiry_class =
                                'valid';

                            $expiry_text =
                                'Valid';

                        }

                    }
                    catch(Exception $e)
                    {

                        $expiry_class = '';

                        $expiry_text =
                            'Invalid date';

                    }

                }

        ?>

        <tr
            class="medicine-row"

            data-type="<?php
                echo htmlspecialchars(
                    $medicine_type
                );
            ?>"

            data-h1="<?php
                echo $is_h1;
            ?>"

            data-expired="<?php
                echo (
                    $expiry_class === 'expired'
                ) ? '1' : '0';
            ?>"
        >

            <!-- ID -->

            <td>

                <?php
                echo htmlspecialchars(
                    $row['id']
                );
                ?>

            </td>

            <!-- MEDICINE NAME -->

            <td>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $row['medicine_name']
                    );
                    ?>

                </strong>

            </td>

            <!-- BARCODE -->

            <td>

                <?php

                if (!empty($row['barcode']))
                {

                ?>

                    <span class="barcode">

                        <?php
                        echo htmlspecialchars(
                            $row['barcode']
                        );
                        ?>

                    </span>

                <?php

                }
                else
                {

                ?>

                    <span
                        style="color:#94a3b8;"
                    >
                        No Barcode
                    </span>

                <?php

                }

                ?>

            </td>

            <!-- MEDICINE TYPE -->

            <td>

                <?php

                if ($medicine_type === 'Schedule')
                {

                    echo
                    '<span class="type-badge schedule">
                        Schedule
                    </span>';

                }

                elseif (
                    $medicine_type === 'Veterinary'
                )
                {

                    echo
                    '<span class="type-badge veterinary">
                        Veterinary
                    </span>';

                }

                else
                {

                    echo
                    '<span class="type-badge general">
                        General
                    </span>';

                }

                ?>

            </td>

            <!-- H1 -->

            <td>

                <?php

                if ($is_h1 == 1)
                {

                    echo
                    '<span class="h1-badge">
                        ⚠ H1
                    </span>';

                }

                else
                {

                    echo
                    '<span class="normal-badge">
                        Normal
                    </span>';

                }

                ?>

            </td>

            <!-- CATEGORY -->

            <td>

                <?php

                echo htmlspecialchars(
                    $row['category'] ?? ''
                );

                ?>

            </td>

            <!-- PACK TYPE -->

            <td>

                <span class="pack">

                    <?php

                    echo htmlspecialchars(
                        $row['pack_type'] ??
                        'Tablet'
                    );

                    ?>

                </span>

            </td>

            <!-- PACK SIZE -->

            <td>

                <span class="pack-size">

                    1 ×

                    <?php

                    echo htmlspecialchars(
                        $pack_size
                    );

                    ?>

                </span>

            </td>

            <!-- BATCH -->

            <td>

                <?php

                echo htmlspecialchars(
                    $row['batch_no'] ?? ''
                );

                ?>

            </td>

            <!-- EXPIRY -->

            <td
                class="<?php
                    echo $expiry_class;
                ?>"
            >

                <?php

                if (!empty($expiry_date))
                {

                    echo htmlspecialchars(
                        $expiry_date
                    );

                ?>

                    <span class="expiry-badge">

                        <?php

                        echo htmlspecialchars(
                            $expiry_text
                        );

                        ?>

                    </span>

                <?php

                }
                else
                {

                ?>

                    <span
                        style="color:#94a3b8;"
                    >
                        Not Set
                    </span>

                <?php

                }

                ?>

            </td>

            <!-- FULL PACKS -->

            <td class="quantity">

                <?php

                echo $full_packs;

                ?>

                <span
                    style="
                    font-size:12px;
                    "
                >
                    packs
                </span>

            </td>

            <!-- LOOSE UNITS -->

            <td>

                <?php

                if ($loose_units > 0)
                {

                    echo
                    '<span class="stock-units">';

                    echo $loose_units;

                    echo
                    '</span>';

                }

                else
                {

                    echo
                    '<span
                        style="color:#94a3b8;"
                    >
                        0
                    </span>';

                }

                ?>

                <div
                    style="
                    font-size:11px;
                    color:#64748b;
                    "
                >
                    loose
                </div>

            </td>

            <!-- TOTAL UNITS -->

            <td>

                <?php

                if ($total_units <= 0)
                {

                    echo
                    '<span class="low-stock">
                        0
                    </span>';

                }

                else
                {

                    echo
                    '<span class="stock-units">';

                    echo $total_units;

                    echo
                    '</span>';

                }

                ?>

                <div
                    style="
                    font-size:11px;
                    color:#64748b;
                    "
                >
                    units
                </div>

            </td>

            <!-- PURCHASE / PACK -->

            <td class="purchase-price">

                ₹<?php

                echo number_format(
                    $purchase_price_pack,
                    2
                );

                ?>

                <div
                    style="
                    font-size:11px;
                    color:#64748b;
                    "
                >
                    Per pack
                </div>

            </td>

            <!-- PURCHASE / UNIT -->

            <td class="purchase-price">

                ₹<?php

                echo number_format(
                    $purchase_price_unit,
                    2
                );

                ?>

                <div
                    style="
                    font-size:11px;
                    color:#64748b;
                    "
                >
                    Per unit
                </div>

            </td>

            <!-- SELLING / PACK -->

            <td class="price">

                ₹<?php

                echo number_format(
                    $selling_price_pack,
                    2
                );

                ?>

                <div
                    style="
                    font-size:11px;
                    color:#64748b;
                    "
                >
                    Per pack
                </div>

            </td>

            <!-- SELLING / UNIT -->

            <td class="price">

                ₹<?php

                echo number_format(
                    $selling_price_unit,
                    2
                );

                ?>

                <div
                    style="
                    font-size:11px;
                    color:#64748b;
                    "
                >
                    Per unit
                </div>

            </td>

            <!-- SUPPLIER ID -->

            <td>

                <?php

                if (
                    !empty(
                        $row['supplier_id']
                    )
                )
                {

                    echo
                    'Supplier #' .
                    htmlspecialchars(
                        $row['supplier_id']
                    );

                }

                else
                {

                    echo
                    '<span
                        style="
                        color:#94a3b8;
                        "
                    >
                        Not Set
                    </span>';

                }

                ?>

            </td>

            <!-- ACTIONS -->

            <td>

                <a
                    class="btn edit"

                    href="edit.php?id=<?php
                        echo $row['id'];
                    ?>"
                >
                    ✏ Edit
                </a>

                <a
                    class="btn delete"

                    href="delete.php?id=<?php
                        echo $row['id'];
                    ?>"

                    onclick="
                        return confirm(
                            'Archive this medicine? It will be removed from the active list but previous sales records will be preserved.'
                        );
                    "
                >
                    🗄 Archive
                </a>

            </td>

        </tr>

        <?php

            }

        }

        else

        {

        ?>

        <tr>

            <td
                colspan="19"
                class="no-data"
            >

                📦 No medicines added yet.

            </td>

        </tr>

        <?php

        }

        ?>

        </tbody>

    </table>

</div>

</div>

<script>

/* =========================
   CURRENT FILTER
========================= */

let selectedType = "All";

/* =========================
   ELEMENTS
========================= */

const searchInput =
    document.getElementById(
        "searchInput"
    );

const rows =
    document.querySelectorAll(
        ".medicine-row"
    );

const countElement =
    document.getElementById(
        "medicineCount"
    );

/* =========================
   FILTER TYPE
========================= */

function filterType(type, button)
{

    selectedType = type;

    document
        .querySelectorAll(
            ".filter-btn"
        )
        .forEach(function(btn)
        {

            btn.classList.remove(
                "active"
            );

        });

    button.classList.add(
        "active"
    );

    applyFilters();

}

/* =========================
   SEARCH
========================= */

searchInput.addEventListener(
    "input",
    applyFilters
);

/* =========================
   APPLY FILTERS
========================= */

function applyFilters()
{

    const search =
        searchInput.value
        .toLowerCase()
        .trim();

    let visibleCount = 0;

    rows.forEach(function(row)
    {

        const text =
            row.innerText
            .toLowerCase();

        const type =
            row.dataset.type;

        const isH1 =
            row.dataset.h1;

        const isExpired =
            row.dataset.expired;

        let typeMatch = true;

        /* =========================
           TYPE
        ========================= */

        if (
            selectedType ===
            "General"
        )
        {

            typeMatch =
                type === "General";

        }

        else if (
            selectedType ===
            "Schedule"
        )
        {

            typeMatch =
                type === "Schedule";

        }

        else if (
            selectedType ===
            "Veterinary"
        )
        {

            typeMatch =
                type === "Veterinary";

        }

        else if (
            selectedType ===
            "H1"
        )
        {

            typeMatch =
                isH1 === "1";

        }

        else if (
            selectedType ===
            "Expired"
        )
        {

            typeMatch =
                isExpired === "1";

        }

        /* =========================
           SEARCH MATCH
        ========================= */

        const searchMatch =
            text.includes(
                search
            );

        /* =========================
           SHOW / HIDE
        ========================= */

        if (
            typeMatch &&
            searchMatch
        )
        {

            row.style.display = "";

            visibleCount++;

        }

        else
        {

            row.style.display =
                "none";

        }

    });

    /* =========================
       UPDATE COUNT
    ========================= */

    countElement.innerText =
        visibleCount;

}

/* =========================
   INITIAL COUNT
========================= */

applyFilters();

</script>

</body>

</html>