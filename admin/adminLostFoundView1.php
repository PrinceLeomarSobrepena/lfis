<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT
        lost_found.*,

        ar.firstName AS admin_released_first,
        ar.lastName AS admin_released_last,

        sr.firstName AS staff_released_first,
        sr.lastName AS staff_released_last

    FROM lost_found

    LEFT JOIN admin ar
        ON lost_found.released_by = ar.id

    LEFT JOIN staff sr
        ON lost_found.released_by = sr.id

    WHERE lost_found.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("Item not found");
}


/*
|--------------------------------------------------------------------------
| RELEASED BY
|--------------------------------------------------------------------------
*/

if (!empty($item['admin_released_first'])) {

    $releasedBy =
        $item['admin_released_first'] . ' ' .
        $item['admin_released_last'];

}
elseif (!empty($item['staff_released_first'])) {

    $releasedBy =
        $item['staff_released_first'] . ' ' .
        $item['staff_released_last'];

}
else {

    $releasedBy = '-';

}


/*
|--------------------------------------------------------------------------
| PDF
|--------------------------------------------------------------------------
*/

if (isset($_GET['pdf'])) {

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    $html = '
    <h2 style="text-align:center;">Lost & Found Claim Details</h2>

    <hr>

    <h3>Item Information</h3>

    <p><b>Category:</b> ' . htmlspecialchars($item['category']) . '</p>

    <p><b>Status:</b> ' . htmlspecialchars($item['status']) . '</p>

    <p><b>Location:</b> ' . htmlspecialchars($item['reported_location']) . '</p>
    ';


    if ($item['category'] === 'Cash') {

        $html .= '
        <p>
            <b>Amount:</b>
            ₱' . number_format($item['cash_amount'], 2) . '
        </p>
        ';

    }

    elseif ($item['category'] === 'Gadget') {

        $html .= '
        <p>
            <b>Type:</b>
            ' . htmlspecialchars($item['gadget_type'] ?? '-') . '
        </p>

        <p>
            <b>Brand:</b>
            ' . htmlspecialchars($item['gadget_brand'] ?? '-') . '
        </p>

        <p>
            <b>Color:</b>
            ' . htmlspecialchars($item['gadget_color'] ?? '-') . '
        </p>

        <p>
            <b>Features:</b>
            ' . htmlspecialchars($item['gadget_features'] ?? '-') . '
        </p>
        ';

    }

    elseif ($item['category'] === 'Document') {

        $html .= '
        <p>
            <b>Document Type:</b>
            ' . htmlspecialchars($item['document_type'] ?? '-') . '
        </p>

        <p>
            <b>Name:</b>
            ' . htmlspecialchars($item['document_name'] ?? '-') . '
        </p>
        ';

    }

    elseif ($item['category'] === 'Other') {

        $html .= '
        <p>
            <b>Description:</b>
            ' . htmlspecialchars($item['other_description'] ?? '-') . '
        </p>
        ';
    }


    $html .= '

    <hr>

    <h3>Claim Information</h3>

    <p>
        <b>Claimed By:</b>
        ' . htmlspecialchars($item['claimed_by'] ?? '-') . '
    </p>

    <p>
        <b>Student ID:</b>
        ' . htmlspecialchars($item['claimed_id'] ?? '-') . '
    </p>

    <p>
        <b>Email:</b>
        ' . htmlspecialchars($item['claimed_email'] ?? '-') . '
    </p>

    <p>
        <b>Contact:</b>
        ' . htmlspecialchars($item['claimed_contact'] ?? '-') . '
    </p>

    <p>
        <b>Department:</b>
        ' . htmlspecialchars($item['claimed_department'] ?? '-') . '
    </p>

    <p>
        <b>Address:</b>
        ' . htmlspecialchars($item['claimed_address'] ?? '-') . '
    </p>

    <p>
        <b>Claim Date:</b>
        ' . htmlspecialchars($item['claimed_date'] ?? '-') . '
    </p>

    <p>
        <b>Released By:</b>
        ' . htmlspecialchars($releasedBy) . '
    </p>

    ';


    $dompdf->loadHtml($html);

    $dompdf->setPaper('A4', 'portrait');

    $dompdf->render();

    $dompdf->stream(
        "Claimed_Item_" . $id . ".pdf",
        [
            "Attachment" => true
        ]
    );

    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Claimed Item Details</title>

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

</head>


<body class="bg-light">


<div class="container py-5">

    <h3 class="mb-0">
        Claimed Item Details
    </h3>

    <div class="card shadow">

        <div class="card-body">

            <div class="row">


                <!-- IMAGE -->

                <div class="col-md-4 text-center">

                    <img src="../uploads/<?= htmlspecialchars($item['image']) ?>"
                         class="img-fluid rounded border mb-3"
                         style="max-height:100px;">


                    <table class="table table-bordered">

                        <tr>

                            <th>Category</th>

                            <td>
                                <?= htmlspecialchars($item['category']) ?>
                            </td>

                        </tr>


                        <tr>

                            <th>Status</th>

                            <td>
                                <?= htmlspecialchars($item['status']) ?>
                            </td>

                        </tr>


                        <tr>

                            <th>Reported Location</th>

                            <td>
                                <?= htmlspecialchars($item['reported_location']) ?>
                            </td>

                        </tr>

                    </table>

                </div>


                <!-- DETAILS -->

                <div class="col-md-8">

                    <h4>
                        Item Details
                    </h4>


                    <?php if ($item['category'] === 'Cash'): ?>

                        <table class="table table-bordered">

                            <tr>

                                <th width="200">
                                    Amount
                                </th>

                                <td>
                                    ₱<?= number_format($item['cash_amount'], 2) ?>
                                </td>

                            </tr>

                        </table>


                    <?php elseif ($item['category'] === 'Gadget'): ?>

                        <table class="table table-bordered">

                            <tr>

                                <th>
                                    Type
                                </th>

                                <td>
                                    <?= htmlspecialchars($item['gadget_type'] ?? '-') ?>
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Brand / Model
                                </th>

                                <td>
                                    <?= htmlspecialchars($item['gadget_brand'] ?? '-') ?>
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Color
                                </th>

                                <td>
                                    <?= htmlspecialchars($item['gadget_color'] ?? '-') ?>
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Features
                                </th>

                                <td>
                                    <?= htmlspecialchars($item['gadget_features'] ?? '-') ?>
                                </td>

                            </tr>

                        </table>


                    <?php elseif ($item['category'] === 'Document'): ?>

                        <table class="table table-bordered">

                            <tr>

                                <th>
                                    Document Type
                                </th>

                                <td>
                                    <?= htmlspecialchars($item['document_type'] ?? '-') ?>
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Name
                                </th>

                                <td>
                                    <?= htmlspecialchars($item['document_name'] ?? '-') ?>
                                </td>

                            </tr>

                        </table>


                    <?php elseif ($item['category'] === 'Other'): ?>

                        <table class="table table-bordered">

                            <tr>

                                <th>
                                    Description
                                </th>

                                <td>
                                    <?= htmlspecialchars($item['other_description'] ?? '-') ?>
                                </td>

                            </tr>

                        </table>

                    <?php endif; ?>


                    <hr>


                    <h4>
                        Claim Information
                    </h4>


                    <table class="table table-bordered">


                        <tr>

                            <th width="200">
                                Claimed By
                            </th>

                            <td>
                                <?= htmlspecialchars($item['claimed_by'] ?? '-') ?>
                            </td>

                        </tr>


                        <tr>

                            <th>
                                Student ID
                            </th>

                            <td>
                                <?= htmlspecialchars($item['claimed_id'] ?? '-') ?>
                            </td>

                        </tr>


                        <tr>

                            <th>
                                Email
                            </th>

                            <td>
                                <?= htmlspecialchars($item['claimed_email'] ?? '-') ?>
                            </td>

                        </tr>


                        <tr>

                            <th>
                                Contact Number
                            </th>

                            <td>
                                <?= htmlspecialchars($item['claimed_contact'] ?? '-') ?>
                            </td>

                        </tr>


                        <tr>

                            <th>
                                Department
                            </th>

                            <td>
                                <?= htmlspecialchars($item['claimed_department'] ?? '-') ?>
                            </td>

                        </tr>


                        <tr>

                            <th>
                                Address
                            </th>

                            <td>
                                <?= htmlspecialchars($item['claimed_address'] ?? '-') ?>
                            </td>

                        </tr>


                        <tr>

                            <th>
                                Claim Date
                            </th>

                            <td>

                                <?= !empty($item['claimed_date'])
                                    ? date(
                                        'M d, Y h:i A',
                                        strtotime($item['claimed_date'])
                                    )
                                    : '-' ?>

                            </td>

                        </tr>


                        <!-- RELEASED BY -->

                        <tr>

                            <th>
                                Released By
                            </th>

                            <td>
                                <?= htmlspecialchars($releasedBy) ?>
                            </td>

                        </tr>


                    </table>


                    <a href="adminLostFoundList.php"
                       class="btn btn-secondary">

                        Back to List

                    </a>


                    <button onclick="window.print()"
                            class="btn btn-primary">

                        Print

                    </button>


                    <a href="adminLostFoundView.php?id=<?= $id ?>&pdf=1" class="btn btn-danger"> Download PDF </a>

                </div>

            </div>

        </div>

    </div>

</div>


</body>
</html>
