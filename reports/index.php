<!DOCTYPE html>
<html>
<head>

    <title>Reports | MediTrack</title>

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            font-family:Arial, sans-serif;
            min-height:100vh;
            color:#1e293b;

            background:
            linear-gradient(
                rgba(240,248,250,0.92),
                rgba(240,248,250,0.92)
            ),
            url('https://images.unsplash.com/photo-1585435557343-3b092031a831?auto=format&fit=crop&w=1600&q=80')
            no-repeat center center fixed;

            background-size:cover;
        }



        /* ---------- TOP BAR ---------- */

        .topbar{
            background:#0f766e;
            color:white;

            padding:17px 35px;

            display:flex;
            justify-content:space-between;
            align-items:center;

            box-shadow:0 4px 15px rgba(0,0,0,0.15);
        }

        .logo{
            font-size:23px;
            font-weight:bold;
        }

        .dashboard-btn{
            background:#115e59;
            color:white;
            text-decoration:none;

            padding:10px 17px;
            border-radius:8px;

            font-weight:bold;

            transition:0.3s;
        }

        .dashboard-btn:hover{
            background:#134e4a;
            transform:translateY(-2px);
        }



        /* ---------- MAIN ---------- */

        .container{
            max-width:1100px;
            margin:40px auto;
            padding:0 20px;
        }



        /* ---------- HEADING ---------- */

        .heading{
            text-align:center;
            margin-bottom:30px;
        }

        .heading h1{
            margin:0;
            color:#134e4a;
            font-size:32px;
        }

        .heading p{
            margin-top:10px;
            color:#64748b;
            font-size:15px;
        }



        /* ---------- DATE TIME ---------- */

        .datetime{
            background:rgba(255,255,255,0.95);

            width:max-content;
            max-width:100%;

            margin:0 auto 35px;

            padding:12px 25px;

            border-radius:12px;

            box-shadow:0 4px 15px rgba(0,0,0,0.10);

            color:#334155;
            font-weight:bold;
        }

        .datetime span{
            color:#0f766e;
        }



        /* ---------- REPORT GRID ---------- */

        .report-grid{

            display:grid;

            grid-template-columns:
            repeat(4, 1fr);

            gap:20px;
        }



        /* ---------- REPORT CARD ---------- */

        .report-card{

            position:relative;

            background:rgba(255,255,255,0.97);

            border-radius:16px;

            padding:28px 22px;

            min-height:230px;

            box-shadow:
            0 7px 20px rgba(0,0,0,0.12);

            border-top:5px solid #0f766e;

            text-decoration:none;

            color:#1e293b;

            transition:
            transform 0.3s,
            box-shadow 0.3s,
            border-color 0.3s;

            overflow:hidden;
        }



        /* ---------- H1 SALES CARD ---------- */

        .h1-report-card{

            grid-column:2 / 4;
        }



        .report-card:hover{

            transform:translateY(-8px);

            box-shadow:
            0 15px 30px rgba(0,0,0,0.18);

            border-color:#14b8a6;
        }



        /* ---------- ICON ---------- */

        .icon{

            width:65px;
            height:65px;

            margin:0 auto 18px;

            border-radius:50%;

            display:flex;
            align-items:center;
            justify-content:center;

            font-size:31px;

            background:#ccfbf1;
        }



        /* ---------- CARD TITLE ---------- */

        .report-card h2{

            margin:0;

            color:#134e4a;

            font-size:20px;
        }



        .report-card p{

            color:#64748b;

            font-size:13px;

            line-height:1.5;

            margin:10px 0 18px;
        }



        /* ---------- VIEW REPORT ---------- */

        .view-report{

            color:#0f766e;

            font-size:14px;

            font-weight:bold;
        }



        .arrow{

            transition:0.3s;
        }

        .report-card:hover .arrow{

            margin-left:6px;
        }



        /* ---------- FOOTER INFO ---------- */

        .info-box{

            margin-top:35px;

            background:rgba(255,255,255,0.95);

            padding:20px;

            border-radius:14px;

            text-align:center;

            box-shadow:0 5px 15px rgba(0,0,0,0.10);
        }

        .info-box h3{

            margin:0 0 8px;

            color:#134e4a;
        }

        .info-box p{

            margin:0;

            color:#64748b;

            font-size:14px;
        }



        /* ---------- RESPONSIVE ---------- */

        @media(max-width:900px){

            .report-grid{

                grid-template-columns:
                repeat(2, 1fr);
            }

            .h1-report-card{

                grid-column:1 / 3;
            }

        }



        @media(max-width:600px){

            .topbar{

                padding:15px 20px;
            }

            .logo{

                font-size:18px;
            }

            .dashboard-btn{

                padding:8px 11px;
                font-size:13px;
            }

            .container{

                margin:25px auto;
            }

            .heading h1{

                font-size:26px;
            }

            .report-grid{

                grid-template-columns:1fr;
            }

            .h1-report-card{

                grid-column:1;
            }

            .datetime{

                font-size:13px;
            }

        }

    </style>

</head>



<body>



<!-- ================= TOP BAR ================= -->

<div class="topbar">

    <div class="logo">
        💊 MediTrack Reports
    </div>

    <a href="../dashboard.php" class="dashboard-btn">
        🏠 Dashboard
    </a>

</div>





<!-- ================= MAIN ================= -->

<div class="container">



    <!-- Heading -->

    <div class="heading">

        <h1>📊 Reports Dashboard</h1>

        <p>
            View and manage pharmacy inventory, purchase, sales and expiry reports
        </p>

    </div>





    <!-- Date & Time -->

    <div class="datetime">

        📅 Date:
        <span id="date"></span>

        &nbsp;&nbsp; | &nbsp;&nbsp;

        🕒 Time:
        <span id="time"></span>

    </div>





    <!-- ================= REPORT CARDS ================= -->

    <div class="report-grid">



        <!-- STOCK -->

        <a
            href="stock_report.php"
            class="report-card"
        >

            <div class="icon">
                📦
            </div>

            <h2>
                Stock Report
            </h2>

            <p>
                View medicine stock, available quantities
                and inventory details.
            </p>

            <div class="view-report">
                View Report
                <span class="arrow">→</span>
            </div>

        </a>





        <!-- PURCHASE -->

        <a
            href="purchase_report.php"
            class="report-card"
        >

            <div class="icon">
                🛒
            </div>

            <h2>
                Purchase Report
            </h2>

            <p>
                Check purchase records, suppliers,
                quantities and purchase details.
            </p>

            <div class="view-report">
                View Report
                <span class="arrow">→</span>
            </div>

        </a>





        <!-- SALES -->

        <a
            href="sales_report.php"
            class="report-card"
        >

            <div class="icon">
                💰
            </div>

            <h2>
                Sales Report
            </h2>

            <p>
                View medicine sales, quantities,
                prices and sales transactions.
            </p>

            <div class="view-report">
                View Report
                <span class="arrow">→</span>
            </div>

        </a>





        <!-- EXPIRY -->

        <a
            href="expiry_report.php"
            class="report-card"
        >

            <div class="icon">
                ⏰
            </div>

            <h2>
                Expiry Report
            </h2>

            <p>
                Monitor medicine expiry dates and
                identify medicines nearing expiry.
            </p>

            <div class="view-report">
                View Report
                <span class="arrow">→</span>
            </div>

        </a>





        <!-- ================= H1 SALES ================= -->

        <a
            href="h1_sales_report.php"
            class="report-card h1-report-card"
        >

            <div class="icon">
                📋
            </div>

            <h2>
                H1 Sales Report
            </h2>

            <p>
                View H1 medicine sales with patient,
                doctor and prescription details.
            </p>

            <div class="view-report">
                View Report
                <span class="arrow">→</span>
            </div>

        </a>



    </div>





    <!-- ================= INFO ================= -->

    <div class="info-box">

        <h3>
            📋 Pharmacy Reports
        </h3>

        <p>
            Select any report above to view detailed information
            about your pharmacy records.
        </p>

    </div>



</div>





<!-- ================= DATE TIME SCRIPT ================= -->

<script>

function updateDateTime()
{
    const now = new Date();

    document.getElementById("date").innerHTML =
        now.toLocaleDateString('en-GB');

    document.getElementById("time").innerHTML =
        now.toLocaleTimeString('en-GB');
}

updateDateTime();

setInterval(updateDateTime, 1000);

</script>



</body>
</html>