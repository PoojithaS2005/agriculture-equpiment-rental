<?php
require_once __DIR__ . '/lender_ai_insights_data.php';

// Load individual written reviews when the table has the expected ownership fields.
$equipmentReviews = [];
$reviewColumns = [];
if (isset($conn) && $conn) {
    $columnResult = $conn->query('SHOW COLUMNS FROM reviews');
    if ($columnResult) {
        while ($column = $columnResult->fetch_assoc()) {
            $reviewColumns[] = $column['Field'];
        }
        $columnResult->free();
    }
    if (in_array('equipment_id', $reviewColumns, true) && in_array('lender_id', $reviewColumns, true)) {
        $detailStmt = $conn->prepare('SELECT * FROM reviews WHERE lender_id = ? ORDER BY equipment_id');
        if ($detailStmt) {
            $detailStmt->bind_param('i', $lender_id);
            if ($detailStmt->execute()) {
                $detailResult = $detailStmt->get_result();
                while ($detail = $detailResult->fetch_assoc()) {
                    $equipmentReviews[(int)$detail['equipment_id']][] = $detail;
                }
            }
            $detailStmt->close();
        }
    }
}

function reviewField(array $review, array $possibleFields): string {
    foreach ($possibleFields as $field) {
        if (isset($review[$field]) && trim((string)$review[$field]) !== '') {
            return trim((string)$review[$field]);
        }
    }
    return '';
}
?>
<!doctype html><html lang="<?= h($current_lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Review Analysis - Agriculture Equipment Rental</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><style>
*{box-sizing:border-box}body{margin:0;background:#f3f7f4;color:#26352d;font-family:'Segoe UI',Arial,sans-serif}.main-content{margin-left:250px;padding:28px;min-height:100vh}.hero{padding:26px;margin-bottom:22px;border-radius:18px;color:white;background:linear-gradient(120deg,#147d50,#248f9b,#5366c8)}.hero h1{font-weight:750;font-size:clamp(24px,3vw,34px);margin:10px 0}.hero p{margin:0}.pill{display:inline-block;padding:5px 10px;border-radius:30px;background:#e2f5e9;color:#17683b;font-size:12px;font-weight:700}.panel,.summary-card,.feature-card,.equipment-card{background:white;border:1px solid #e5ece7;border-radius:16px;box-shadow:0 6px 20px #2e47400d}.panel{padding:22px;margin-bottom:20px}.panel h2{font-size:20px;font-weight:750;margin:0 0 16px}.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.summary-card{padding:18px}.summary-card small,.muted{color:#66756b}.summary-value{display:block;margin-top:7px;font-size:24px;font-weight:750;overflow-wrap:anywhere}.feature-grid,.equipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.feature-card{padding:20px;color:inherit;text-decoration:none;display:block}.feature-icon{font-size:24px;color:#168050;margin-bottom:12px}.equipment-card{padding:18px;min-width:0}.review-list{border-top:1px solid #e5ece7;padding-top:15px}.review-entry{padding:13px 14px;border:1px solid #e5ece7;border-radius:12px;background:#fbfdfb;margin-top:10px}.review-rating{color:#9a6a00;font-weight:700;white-space:nowrap}.review-text{white-space:normal;overflow-wrap:anywhere}.metric-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #e8eee9}.metric-row strong{text-align:right;overflow-wrap:anywhere}.muted{font-size:13px}.notice{background:#fff8df;border:1px solid #f1dfa0;border-radius:12px;padding:14px 16px;margin-bottom:18px;color:#69551a}.chart-container{position:relative;height:300px}.table-responsive{overflow-x:auto}@media(max-width:1100px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.feature-grid,.equipment-grid{grid-template-columns:1fr}}@media(max-width:700px){.main-content{margin-left:0;padding:15px}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.summary-card{padding:13px}.summary-value{font-size:20px}.panel,.hero{padding:17px}}
</style></head><body><?php include __DIR__ . '/lender_sidebar.php'; ?><main class="main-content"><section class="hero"><span class="pill">AI-ASSISTED ANALYSIS</span><h1>Review Analysis</h1><p>Summarize customer ratings and flag equipment where feedback may need attention.</p></section><div class="mb-3"><a class="btn btn-outline-success" href="lender_ai_insights.php"><i class="fa-solid fa-arrow-left me-2"></i>Back to AI Insights Dashboard</a></div><div class="notice">Review ratings and written comments are shown below each equipment item when available.</div><section class="panel"><h2>Customer Feedback by Equipment</h2><?php if(empty($equipmentInsights)): ?><p>No equipment has been added yet.</p><?php else: ?><div class="equipment-grid"><?php foreach($equipmentInsights as $item): ?><article class="equipment-card"><span class="pill"><?= h($item['category']) ?></span><h3 class="mt-3"><?= h($item['title']) ?></h3><div class="metric-row"><span>Average rating</span><strong><?= $item['average_rating']===null?'No reviews yet':number_format((float)$item['average_rating'],1).' / 5' ?></strong></div><div class="metric-row"><span>Total reviews</span><strong><?= (int)$item['review_count'] ?></strong></div><div class="metric-row"><span>Low ratings (1–2)</span><strong><?= (int)$item['low_rating_count'] ?></strong></div><div class="metric-row"><span>Positive ratings (4–5)</span><strong><?= (int)($reviewSignals[(int)$item['equipment_id']]['positive_rating_count']??0) ?></strong></div><p class="mt-3 mb-0"><?php if((int)$item['review_count']===0): ?>No review evidence yet; collect feedback after future rentals.<?php elseif((int)$item['review_count']<3): ?>Only a few reviews are available; read comments manually before drawing conclusions.<?php elseif((float)$item['average_rating']<3||(int)$item['low_rating_count']>=2): ?>Review recent comments, identify repeated concerns, and check equipment condition and handover quality.<?php elseif((float)$item['average_rating']>=4.5): ?>Feedback is strong so far; maintain equipment condition and consistent service.<?php else: ?>Monitor new feedback and look for recurring suggestions.<?php endif; ?></p><div class="review-list mt-4"><h4 class="h6 fw-bold mb-3">Customer reviews</h4><?php $itemReviews = $equipmentReviews[(int)$item['equipment_id']] ?? []; ?><?php if (!$itemReviews): ?><p class="muted mb-0">No written reviews are available for this equipment yet.</p><?php else: ?><?php foreach ($itemReviews as $oneReview): ?><?php
$reviewRating = reviewField($oneReview, ['rating', 'stars', 'review_rating']);
$reviewText = reviewField($oneReview, ['review', 'review_text', 'comment', 'comments', 'feedback', 'description', 'message', 'content']);
$reviewer = reviewField($oneReview, ['customer_name', 'user_name', 'reviewer_name', 'name', 'customer', 'username']);
$reviewDate = reviewField($oneReview, ['created_at', 'review_date', 'date', 'created_on']);
?><?php if ($reviewText !== '' || $reviewRating !== '' || $reviewer !== ''): ?><div class="review-entry"><div class="d-flex justify-content-between align-items-start gap-2 flex-wrap"><strong><?= h($reviewer !== '' ? $reviewer : 'Customer') ?></strong><?php if ($reviewRating !== ''): ?><span class="review-rating"><i class="fa-solid fa-star"></i> <?= h($reviewRating) ?>/5</span><?php endif; ?></div><?php if ($reviewDate !== ''): ?><div class="muted mt-1"><?= h($reviewDate) ?></div><?php endif; ?><?php if ($reviewText !== ''): ?><p class="mb-0 mt-2 review-text"><?= nl2br(h($reviewText)) ?></p><?php elseif ($reviewRating !== ''): ?><p class="muted mb-0 mt-2">No written comment was provided.</p><?php endif; ?></div><?php endif; ?><?php endforeach; ?><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?></section></main></body></html>