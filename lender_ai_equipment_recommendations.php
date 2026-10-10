<?php require_once __DIR__ . '/lender_ai_insights_data.php'; ?>
<!doctype html><html lang="<?= h($current_lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Equipment AI Recommendations - Agriculture Equipment Rental</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><style>
*{box-sizing:border-box}body{margin:0;background:#f3f7f4;color:#26352d;font-family:'Segoe UI',Arial,sans-serif}.main-content{margin-left:250px;padding:28px;min-height:100vh}.hero{padding:26px;margin-bottom:22px;border-radius:18px;color:white;background:linear-gradient(120deg,#147d50,#248f9b,#5366c8)}.hero h1{font-weight:750;font-size:clamp(24px,3vw,34px);margin:10px 0}.hero p{margin:0}.pill{display:inline-block;padding:5px 10px;border-radius:30px;background:#e2f5e9;color:#17683b;font-size:12px;font-weight:700}.panel,.summary-card,.feature-card,.equipment-card{background:white;border:1px solid #e5ece7;border-radius:16px;box-shadow:0 6px 20px #2e47400d}.panel{padding:22px;margin-bottom:20px}.panel h2{font-size:20px;font-weight:750;margin:0 0 16px}.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.summary-card{padding:18px}.summary-card small,.muted{color:#66756b}.summary-value{display:block;margin-top:7px;font-size:24px;font-weight:750;overflow-wrap:anywhere}.feature-grid,.equipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.feature-card{padding:20px;color:inherit;text-decoration:none;display:block}.feature-icon{font-size:24px;color:#168050;margin-bottom:12px}.equipment-card{padding:18px;min-width:0}.metric-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #e8eee9}.metric-row strong{text-align:right;overflow-wrap:anywhere}.muted{font-size:13px}.notice{background:#fff8df;border:1px solid #f1dfa0;border-radius:12px;padding:14px 16px;margin-bottom:18px;color:#69551a}.chart-container{position:relative;height:300px}.table-responsive{overflow-x:auto}@media(max-width:1100px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.feature-grid,.equipment-grid{grid-template-columns:1fr}}@media(max-width:700px){.main-content{margin-left:0;padding:15px}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.summary-card{padding:13px}.summary-value{font-size:20px}.panel,.hero{padding:17px}}
.equipment-detail-card{padding:0;overflow:hidden}.equipment-summary{list-style:none;cursor:pointer;padding:18px;display:flex;flex-direction:column;gap:9px}.equipment-summary::-webkit-details-marker{display:none}.equipment-summary:after{content:"＋";align-self:flex-end;font-size:20px;color:#168050;font-weight:700}.equipment-detail-card details[open]>.equipment-summary:after{content:"−"}.equipment-name{font-size:20px;font-weight:700;color:#26352d}.equipment-details{padding:0 18px 18px}.equipment-summary:hover{background:#f7fbf8}</style></head><body><?php include __DIR__ . '/lender_sidebar.php'; ?><main class="main-content"><section class="hero"><span class="pill">AI-ASSISTED ANALYSIS</span><h1>Equipment AI Recommendations</h1><p>Review demand signals, weather outlook and explainable pricing suggestions.</p></section><div class="mb-3"><a class="btn btn-outline-success" href="lender_ai_insights.php"><i class="fa-solid fa-arrow-left me-2"></i>Back to AI Insights Dashboard</a></div><section class="panel">
        <h2>Weather-Based Demand Outlook (Next 2 Days)</h2>
        <p class="muted">Location is taken automatically from your registered lender profile. This is a rule-based weather impact estimate, not a trained ML prediction or a guarantee of bookings.</p>
        <?php if (!$weatherOutlook['available']): ?>
            <div class="notice mb-0"><?= h($weatherOutlook['message']) ?></div>
        <?php else: ?>
            <div class="summary-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                <div class="summary-card">
                    <small>Forecast location</small>
                    <span class="summary-value" style="font-size:19px;"><?= h($weatherOutlook['location']) ?></span>
                </div>
                <div class="summary-card">
                    <small>Average rain chance</small>
                    <span class="summary-value"><?= $weatherOutlook['rain_probability'] === null ? 'N/A' : (int)$weatherOutlook['rain_probability'] . '%' ?></span>
                </div>
                <div class="summary-card">
                    <small>Total forecast rainfall</small>
                    <span class="summary-value"><?= $weatherOutlook['precipitation'] === null ? 'N/A' : number_format((float)$weatherOutlook['precipitation'], 1) . ' mm' ?></span>
                </div>
            </div>
            <div class="equipment-grid">
                <?php foreach ($weatherOutlook['days'] as $forecastDay): ?>
                    <article class="equipment-card">
                        <h3><?= h(date('D, d M', strtotime($forecastDay['date']))) ?></h3>
                        <div class="metric-row"><span>Rain probability</span><strong><?= $forecastDay['rain_probability'] === null ? 'N/A' : (int)$forecastDay['rain_probability'] . '%' ?></strong></div>
                        <div class="metric-row"><span>Expected rainfall</span><strong><?= $forecastDay['precipitation'] === null ? 'N/A' : number_format((float)$forecastDay['precipitation'], 1) . ' mm' ?></strong></div>
                        <div class="metric-row"><span>Temperature</span><strong><?= $forecastDay['temperature_min'] === null || $forecastDay['temperature_max'] === null ? 'N/A' : round($forecastDay['temperature_min']) . '°C – ' . round($forecastDay['temperature_max']) . '°C' ?></strong></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="muted mt-3 mb-0">Weather source: Open-Meteo. Forecasts may change; check local field conditions before making rental decisions.</p>
        <?php endif; ?>
    </section><section class="panel">
        <h2>Smart Rental Pricing &amp; Price History</h2>
        <p class="muted">Suggested prices use the current average daily price of active equipment in the same category. Price history starts from the setup date's baseline and records future price changes automatically.</p>
        <?php if (empty($priceHistory)): ?>
            <div class="notice mb-0">No price history is available yet. Check that the Step 3 SQL setup completed successfully and that equipment exists for your lender account.</div>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="priceHistoryChart"></canvas>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr><th>Equipment</th><th>Change type</th><th>Previous price</th><th>New price</th><th>Date recorded</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_reverse($priceHistory) as $historyRow): ?>
                        <tr>
                            <td><?= h($historyRow['title']) ?></td>
                            <td><?= h($historyRow['change_type']) ?></td>
                            <td><?= $historyRow['old_price'] === null ? '—' : money($historyRow['old_price']) ?></td>
                            <td><?= money($historyRow['new_price']) ?></td>
                            <td><?= h(date('d M Y, h:i A', strtotime($historyRow['changed_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="muted mb-0">Pricing suggestions are reference estimates, not guaranteed market prices. Compare equipment condition, local demand and service costs before changing a rate.</p>
    </section><section class="panel">
        <h2>Equipment Utilization Comparison</h2>

        <?php if (empty($equipmentInsights)): ?>
            <p>No equipment has been added to your account yet.</p>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="utilizationChart"></canvas>
            </div>
            <p class="muted mt-3">
                Estimated from recorded rental days over a 90-day period.
                This is an approximate indicator, not verified machine operating time.
            </p>
        <?php endif; ?>
    </section>
<section class="panel">
        <h2>Equipment AI Recommendations</h2>

        <p>
            <strong>Overall summary:</strong>
            <?= h($overallDemand) ?>.
            Review each equipment item below before changing prices or scheduling maintenance.
        </p>

        <?php if (empty($equipmentInsights)): ?>
            <p>No recommendations are available until you add equipment.</p>
        <?php else: ?>

            <div class="equipment-grid">

                <?php foreach ($equipmentInsights as $item): ?>

                    <article class="equipment-card equipment-detail-card">
                        <details>
                            <summary class="equipment-summary">
                                <span class="pill"><?= h($item['category']) ?></span>
                                <span class="equipment-name"><?= h($item['title']) ?></span>
                                <span class="muted">Click to view AI recommendation details</span>
                            </summary>
                            <div class="equipment-details">

                        <div class="metric-row">
                            <span>Equipment status</span>
                            <strong><?= h($item['status']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Recorded bookings</span>
                            <strong><?= (int)$item['bookings'] ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Estimated accumulated rental-days</span>
                            <strong><?= (int)$item['rental_days'] ?> days</strong>
                        </div>

                        <div class="metric-row">
                            <span>Completed / returned rentals</span>
                            <strong><?= (int)$item['completed_rentals'] ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Estimated demand from history</span>
                            <strong><?= h($item['demand']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Weather-based outlook</span>
                            <strong><?= h($item['weather_demand']) ?></strong>
                        </div>
                        <p class="muted mt-2"><?= h($item['weather_reason']) ?></p>

                        <div class="metric-row">
                            <span>Equipment condition</span>
                            <strong><?= h($item['condition']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Customer reviews</span>
                            <strong><?= $item['average_rating'] === null ? 'No reviews yet' : number_format((float)$item['average_rating'], 1) . '/5 (' . (int)$item['review_count'] . ')' ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Current daily price</span>
                            <strong><?= money($item['price']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Category average price</span>
                            <strong><?= money($item['average']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Price position</span>
                            <strong><?= h(ucfirst($item['price_position'])) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>AI pricing action</span>
                            <strong><?= h($item['price_action']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Suggested action price</span>
                            <strong><?= money($item['action_price']) ?></strong>
                        </div>

                        <p class="muted mt-2"><?= h($item['action_detail']) ?></p>
                        <strong>Why this recommendation?</strong>
                        <ul class="mt-2">
                            <?php foreach ($item['pricing_reasons'] as $reason): ?>
                                <li><?= h($reason) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="metric-row">
                            <span>Category-based reference price</span>
                            <strong><?= money($item['suggested']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Estimated utilization</span>
                            <strong><?= (int)$item['utilization'] ?>%</strong>
                        </div>

                        
                            </div>
                        </details>
                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>
    </section><script><?php if (!empty($priceHistory)): ?>const labels=<?= json_encode($priceChartLabels,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;const datasets=<?= json_encode($priceChartDatasets,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;new Chart(document.getElementById('priceHistoryChart'),{type:'line',data:{labels:labels,datasets:datasets},options:{responsive:true,maintainAspectRatio:false,scales:{y:{ticks:{callback:v=>'₹'+v}}},plugins:{legend:{position:'bottom'}}}});<?php endif; ?><?php if (!empty($equipmentInsights)): ?>new Chart(document.getElementById('utilizationChart'),{type:'bar',data:{labels:<?= json_encode(array_column($equipmentInsights,'title')) ?>,datasets:[{label:'Estimated utilization (%)',data:<?= json_encode(array_column($equipmentInsights,'utilization')) ?>,backgroundColor:'rgba(79,105,210,.72)'}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',scales:{x:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}}}}});<?php endif; ?></script></main></body></html>