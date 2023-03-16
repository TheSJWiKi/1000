<?php defined('ALTUMCODE') || die() ?>

<?php ob_start() ?>
<div class="card mb-5">
    <div class="card-body">
        <h2 class="h4"><i class="fa fa-fw fa-robot fa-xs text-muted"></i> <?= l('admin_statistics.images.header') ?></h2>
        <div class="d-flex flex-column flex-xl-row">
            <div class="mb-2 mb-xl-0 mr-4">
                <span class="font-weight-bold"><?= nr($data->total['images']) ?></span> <?= l('admin_statistics.images.chart') ?>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="images"></canvas>
        </div>
    </div>
</div>

<?php $html = ob_get_clean() ?>

<?php ob_start() ?>
<script>
    'use strict';

    let color = css.getPropertyValue('--primary');
    let color_gradient = null;

    /* Display chart */
    let images_chart = document.getElementById('images').getContext('2d');
    color_gradient = images_chart.createLinearGradient(0, 0, 0, 250);
    color_gradient.addColorStop(0, 'rgba(63, 136, 253, .1)');
    color_gradient.addColorStop(1, 'rgba(63, 136, 253, 0.025)');

    new Chart(images_chart, {
        type: 'line',
        data: {
            labels: <?= $data->images_chart['labels'] ?>,
            datasets: [
                {
                    label: <?= json_encode(l('admin_statistics.images.chart')) ?>,
                    data: <?= $data->images_chart['images'] ?? '[]' ?>,
                    backgroundColor: color_gradient,
                    borderColor: color,
                    fill: true
                }
            ]
        },
        options: chart_options
    });
</script>
<?php $javascript = ob_get_clean() ?>

<?php return (object) ['html' => $html, 'javascript' => $javascript] ?>