import flatpickr from "flatpickr";

const reportDateRangeInput = document.querySelector(".js-report-daterange");
const reportDateFromInput = document.querySelector("#reports-date-from");
const reportDateToInput = document.querySelector("#reports-date-to");

if (reportDateRangeInput && reportDateFromInput && reportDateToInput) {
    const dateFrom = reportDateRangeInput.dataset.dateFrom ?? "";
    const dateTo = reportDateRangeInput.dataset.dateTo ?? "";
    const defaultDate = [];

    if (dateFrom !== "") {
        defaultDate.push(dateFrom);
    }

    if (dateTo !== "") {
        defaultDate.push(dateTo);
    }

    if (reportDateRangeInput._flatpickr) {
        reportDateRangeInput._flatpickr.destroy();
    }

    flatpickr(reportDateRangeInput, {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate,
        disableMobile: true,
        onClose: (selectedDates, _dateStr, instance) => {
            if (selectedDates.length === 2) {
                reportDateFromInput.value = instance.formatDate(
                    selectedDates[0],
                    "Y-m-d",
                );
                reportDateToInput.value = instance.formatDate(
                    selectedDates[1],
                    "Y-m-d",
                );

                return;
            }

            if (selectedDates.length === 0) {
                reportDateFromInput.value = "";
                reportDateToInput.value = "";
            }
        },
    });
}
