import ApexCharts from 'apexcharts';

const salesTrendEl = document.querySelector('#salesTrendChart');
if (salesTrendEl) {
    const salesTrendChart = new ApexCharts(salesTrendEl, {
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            zoom: { enabled: false },
        },
        stroke: {
            curve: 'smooth',
            width: 3,
        },
        dataLabels: { enabled: false },
        colors: ['#0d6efd'],
        series: [
            {
                name: 'Net Sales',
                data: [820, 930, 1010, 980, 1240, 1360, 1280, 1420, 1510, 1640, 1580, 1760],
            },
        ],
        xaxis: {
            categories: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8', 'W9', 'W10', 'W11', 'W12'],
            labels: { style: { colors: '#6c757d' } },
        },
        yaxis: {
            labels: {
                style: { colors: '#6c757d' },
                formatter: (value) => `$${value}`,
            },
        },
        grid: {
            borderColor: '#e9ecef',
            strokeDashArray: 4,
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0.2,
                opacityFrom: 0.35,
                opacityTo: 0.05,
            },
        },
        tooltip: {
            y: {
                formatter: (value) => `$${value}`,
            },
        },
    });

    salesTrendChart.render();
}

const revenueSplitEl = document.querySelector('#revenueSplitChart');
if (revenueSplitEl) {
    const revenueSplitChart = new ApexCharts(revenueSplitEl, {
        chart: {
            type: 'donut',
            height: 220,
        },
        series: [68, 21, 11],
        labels: ['In-store', 'Delivery', 'Wholesale'],
        colors: ['#0d6efd', '#198754', '#f59f00'],
        dataLabels: { enabled: false },
        legend: { show: false },
        stroke: { width: 0 },
        tooltip: {
            y: {
                formatter: (value) => `${value}%`,
            },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: () => '100%',
                        },
                        value: {
                            formatter: (value) => `${value}%`,
                        },
                    },
                },
            },
        },
    });

    revenueSplitChart.render();
}
