<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

// BASE QUERY — FOUND ITEMS ONLY
$sql = "
    SELECT
        id,
        reported_location,
        category,
        status,
        email,
        image,
        cash_amount,
        gadget_type,
        gadget_brand,
        gadget_color,
        gadget_features,
        document_type,
        document_name,
        other_description,
        created_at,
        is_claimed,
        is_resolved
    FROM lost_found
    WHERE status = 'Found'
";

$params = [];
$types = "";

// SEARCH
if ($search !== '') {
    $sql .= "
        AND (
            reported_location LIKE ?
            OR category LIKE ?
            OR gadget_brand LIKE ?
            OR gadget_color LIKE ?
            OR document_name LIKE ?
            OR other_description LIKE ?
        )
    ";

    $searchParam = "%" . $search . "%";

    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;

    $types .= "ssssss";
}

// CATEGORY FILTER
if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

// LATEST FIRST
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Found Items | Lost & Found</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .page-title {
            font-weight: 700;
        }

        .item-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            transition: 0.2s ease;
        }

        .item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.10) !important;
        }

        .item-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #e9ecef;
        }

        .badge-found {
            background: #198754;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .empty-box {
            border-radius: 18px;
        }

    </style>

</head>

<body>

<div class="container py-5">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="page-title mb-1">
                Found Items
            </h2>

            <p class="text-muted mb-0">
                List of all items reported as found.
            </p>
        </div>

        <a
            href="adminLostFoundCreate.php"
            class="btn btn-primary"
        >
            + Create Item
        </a>

    </div>


    <!-- SEARCH / FILTER -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3">

                    <div class="col-md-7">

                        <label class="form-label fw-bold">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search location, brand, document name..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label fw-bold">
                            Category
                        </label>

                        <select
                            name="category"
                            class="form-select"
                        >

                            <option value="">
                                All Categories
                            </option>

                            <option
                                value="Cash"
                                <?= $category === 'Cash' ? 'selected' : '' ?>
                            >
                                Cash
                            </option>

                            <option
                                value="Gadget"
                                <?= $category === 'Gadget' ? 'selected' : '' ?>
                            >
                                Gadget
                            </option>

                            <option
                                value="Document"
                                <?= $category === 'Document' ? 'selected' : '' ?>
                            >
                                Document
                            </option>

                            <option
                                value="Other"
                                <?= $category === 'Other' ? 'selected' : '' ?>
                            >
                                Other
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="submit"
                            class="btn btn-success w-100"
                        >
                            Search
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- ITEMS -->
    <div class="row g-4">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($item = $result->fetch_assoc()): ?>

                <?php

                $image = !empty($item['image'])
                    ? "../uploads/" . $item['image']
                    : "../uploads/default.jpg";

                ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card item-card shadow-sm h-100">

                        <!-- IMAGE -->
                        <img
                            src="<?= htmlspecialchars($image) ?>"
                            class="item-image"
                            alt="Found Item"
                            onerror="this.src='../uploads/default.jpg';"
                        >


                        <div class="card-body">

                            <!-- CATEGORY + STATUS -->
                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <span class="badge bg-primary">
                                    <?= htmlspecialchars($item['category']) ?>
                                </span>

                                <span class="badge badge-found">
                                    Found
                                </span>

                            </div>


                            <!-- CATEGORY DETAILS -->

                            <?php if ($item['category'] === 'Cash'): ?>

                                <h5 class="mb-3">
                                    ₱<?= number_format((float)$item['cash_amount'], 2) ?>
                                </h5>

                                <p class="mb-2">

                                    <span class="detail-label">
                                        Amount:
                                    </span>

                                    ₱<?= number_format((float)$item['cash_amount'], 2) ?>

                                </p>


                            <?php elseif ($item['category'] === 'Gadget'): ?>

                                <h5 class="mb-3">
                                    <?= htmlspecialchars($item['gadget_type'] ?? 'Gadget') ?>
                                </h5>

                                <p class="mb-2">

                                    <span class="detail-label">
                                        Brand / Model:
                                    </span>

                                    <?= htmlspecialchars($item['gadget_brand'] ?? '-') ?>

                                </p>

                                <p class="mb-2">

                                    <span class="detail-label">
                                        Color:
                                    </span>

                                    <?= htmlspecialchars($item['gadget_color'] ?? '-') ?>

                                </p>

                                <?php if (!empty($item['gadget_features'])): ?>

                                    <p class="mb-2">

                                        <span class="detail-label">
                                            Features:
                                        </span>

                                        <?= htmlspecialchars($item['gadget_features']) ?>

                                    </p>

                                <?php endif; ?>


                            <?php elseif ($item['category'] === 'Document'): ?>

                                <h5 class="mb-3">
                                    Document
                                </h5>

                                <p class="mb-2">

                                    <span class="detail-label">
                                        Type:
                                    </span>

                                    <?= htmlspecialchars($item['document_type'] ?? '-') ?>

                                </p>

                                <p class="mb-2">

                                    <span class="detail-label">
                                        Name:
                                    </span>

                                    <?= htmlspecialchars($item['document_name'] ?? '-') ?>

                                </p>


                            <?php elseif ($item['category'] === 'Other'): ?>

                                <h5 class="mb-3">
                                    Other Item
                                </h5>

                                <p class="mb-2">

                                    <span class="detail-label">
                                        Description:
                                    </span>

                                    <?= htmlspecialchars($item['other_description'] ?? '-') ?>

                                </p>

                            <?php endif; ?>


                            <hr>


                            <!-- LOCATION -->
                            <p class="mb-2">

                                <span class="detail-label">
                                    📍 Found Location:
                                </span>

                                <br>

                                <?= htmlspecialchars($item['reported_location']) ?>

                            </p>


                            <!-- DATE -->
                            <p class="text-muted small mb-3">

                                Reported:
                                <?= date(
                                    'F d, Y h:i A',
                                    strtotime($item['created_at'])
                                ) ?>

                            </p>


                            <!-- CLAIM STATUS -->

                            <?php if (!empty($item['is_claimed'])): ?>

                                <span class="badge bg-warning text-dark">
                                    Claimed
                                </span>

                            <?php elseif (!empty($item['is_resolved'])): ?>

                                <span class="badge bg-secondary">
                                    Resolved
                                </span>

                            <?php else: ?>

                                <span class="badge bg-success">
                                    Available
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <!-- NO ITEMS -->
            <div class="col-12">

                <div class="card empty-box border-0 shadow-sm">

                    <div class="card-body text-center py-5">

                        <div style="font-size: 50px;">
                            📦
                        </div>

                        <h4 class="mt-3">
                            No Found Items
                        </h4>

                        <p class="text-muted">
                            There are currently no found items
                            matching your search.
                        </p>

                        <a
                            href="adminLostFoundCreate.php"
                            class="btn btn-primary"
                        >
                            Create Found Item
                        </a>

                    </div>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>

<?php

$stmt->close();

?>