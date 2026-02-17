import ApexCharts from "apexcharts";
import flatpickr from "flatpickr";

const chartDataElement = document.querySelector("#dashboard-chart-data");

if (chartDataElement) {
    const rawChartData = chartDataElement.textContent ?? "{}";
    const chartData = JSON.parse(rawChartData);

    const formatAmount = (value) => {
        const number = Number(value ?? 0);
        return number.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const dateRangeInput = document.querySelector("#daterange");
    const dateFromInput = document.querySelector("#dashboard-date-from");
    const dateToInput = document.querySelector("#dashboard-date-to");

    if (dateRangeInput && dateFromInput && dateToInput) {
        if (dateRangeInput._flatpickr) {
            dateRangeInput._flatpickr.destroy();
        }

        flatpickr(dateRangeInput, {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [dateFromInput.value, dateToInput.value],
            disableMobile: true,
            onClose: (selectedDates, _dateStr, instance) => {
                if (selectedDates.length !== 2) {
                    return;
                }

                dateFromInput.value = instance.formatDate(
                    selectedDates[0],
                    "Y-m-d",
                );
                dateToInput.value = instance.formatDate(
                    selectedDates[1],
                    "Y-m-d",
                );
            },
        });
    }

    const trendElement = document.querySelector(
        "#dashboard-sales-purchase-trend",
    );
    if (trendElement) {
        new ApexCharts(trendElement, {
            chart: {
                type: "area",
                height: 520,
                toolbar: { show: false },
                zoom: { enabled: false },
            },
            stroke: {
                curve: "smooth",
                width: 2,
            },
            dataLabels: { enabled: false },
            series: [
                {
                    name: "Sales",
                    data: chartData.sales_trend ?? [],
                },
                {
                    name: "Purchases",
                    data: chartData.purchase_trend ?? [],
                },
            ],
            colors: ["#0d6efd", "#f59f00"],
            xaxis: {
                categories: chartData.trend_labels ?? [],
                labels: {
                    rotate: -30,
                    style: { colors: "#6c757d" },
                },
            },
            yaxis: {
                labels: {
                    style: { colors: "#6c757d" },
                    formatter: (value) => formatAmount(value),
                },
            },
            grid: {
                borderColor: "#e9ecef",
                strokeDashArray: 4,
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 0.3,
                    opacityFrom: 0.3,
                    opacityTo: 0.05,
                },
            },
            tooltip: {
                y: {
                    formatter: (value) => formatAmount(value),
                },
            },
            legend: {
                position: "top",
                horizontalAlign: "right",
            },
        }).render();
    }

    const paymentMixElement = document.querySelector("#dashboard-payment-mix");
    if (paymentMixElement) {
        new ApexCharts(paymentMixElement, {
            chart: {
                type: "donut",
                height: 240,
            },
            labels: chartData.payment_labels ?? [],
            series: chartData.payment_values ?? [],
            colors: ["#0d6efd", "#198754", "#f59f00", "#dc3545", "#6f42c1"],
            dataLabels: { enabled: false },
            legend: {
                position: "bottom",
            },
            stroke: { width: 0 },
            tooltip: {
                y: {
                    formatter: (value) => formatAmount(value),
                },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: "68%",
                    },
                },
            },
        }).render();
    }

    const saleStatusElement = document.querySelector("#dashboard-sale-status");
    if (saleStatusElement) {
        new ApexCharts(saleStatusElement, {
            chart: {
                type: "bar",
                height: 220,
                toolbar: { show: false },
            },
            series: [
                {
                    name: "Invoices",
                    data: chartData.sale_status_values ?? [],
                },
            ],
            xaxis: {
                categories: chartData.sale_status_labels ?? [],
                labels: {
                    style: { colors: "#6c757d" },
                },
            },
            yaxis: {
                labels: {
                    style: { colors: "#6c757d" },
                },
            },
            dataLabels: {
                enabled: false,
            },
            colors: ["#198754"],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: "45%",
                },
            },
            grid: {
                borderColor: "#f1f3f5",
                strokeDashArray: 3,
            },
        }).render();
    }
}
