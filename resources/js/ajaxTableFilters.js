const initializedAjaxTableSearchRoots = new WeakSet();

const toInteger = (value, fallback) => {
    const parsed = Number.parseInt(value ?? "", 10);

    return Number.isNaN(parsed) ? fallback : parsed;
};

const initAjaxTableSearch = (rootElement) => {
    if (
        !(rootElement instanceof HTMLElement) ||
        initializedAjaxTableSearchRoots.has(rootElement)
    ) {
        return;
    }

    const formSelector =
        rootElement.dataset.formSelector ?? "#brands-search-form";
    const inputSelector = rootElement.dataset.inputSelector ?? "#search";
    const tableBodySelector =
        rootElement.dataset.tableBodySelector ?? "#brands-table tbody";
    const tableHeadSelector =
        rootElement.dataset.tableHeadSelector ??
        tableBodySelector.replace(/tbody$/, "thead");
    const paginationSelector =
        rootElement.dataset.paginationSelector ?? "[data-brands-pagination]";
    const loadingSelector =
        rootElement.dataset.loadingSelector ?? "#brands-search-loading";
    const sortLinkSelector =
        rootElement.dataset.sortLinkSelector ?? "[data-ajax-sort-link]";
    const searchParam = rootElement.dataset.searchParam ?? "search";
    const debounceMs = toInteger(rootElement.dataset.debounce, 350);
    const minLoadingVisibleMs = toInteger(
        rootElement.dataset.minLoadingVisible,
        220,
    );

    const searchForm = rootElement.querySelector(formSelector);
    const searchInput = rootElement.querySelector(inputSelector);
    const searchLoading = rootElement.querySelector(loadingSelector);
    const tableBody = rootElement.querySelector(tableBodySelector);
    const tableHead = rootElement.querySelector(tableHeadSelector);
    const paginationWrapper = rootElement.querySelector(paginationSelector);

    if (!(searchInput instanceof HTMLInputElement)) {
        return;
    }

    if (!tableBody || !tableHead || !paginationWrapper) {
        return;
    }

    let searchDebounce = null;
    let activeRequestController = null;
    let activeRequestToken = 0;
    let loadingVisibleAt = 0;

    const showSearchLoading = () => {
        if (!searchLoading) {
            return;
        }

        searchLoading.classList.remove("opacity-0", "pe-none");
        loadingVisibleAt = Date.now();
    };

    const hideSearchLoading = () => {
        if (!searchLoading) {
            return;
        }

        const elapsed = Date.now() - loadingVisibleAt;
        const delay =
            loadingVisibleAt > 0
                ? Math.max(minLoadingVisibleMs - elapsed, 0)
                : 0;

        window.setTimeout(() => {
            searchLoading.classList.add("opacity-0", "pe-none");
            loadingVisibleAt = 0;
        }, delay);
    };

    const buildSearchUrl = (targetUrl = null) => {
        const url = new URL(targetUrl || window.location.href);
        const keyword = searchInput.value.trim();

        if (keyword === "") {
            url.searchParams.delete(searchParam);
        } else {
            url.searchParams.set(searchParam, keyword);
        }

        url.searchParams.delete("page");

        return url;
    };

    const refreshTable = async (url, shouldPushState = true) => {
        if (activeRequestController) {
            activeRequestController.abort();
        }

        activeRequestController = new AbortController();
        activeRequestToken += 1;
        const requestToken = activeRequestToken;

        showSearchLoading();
        searchInput.setAttribute("aria-busy", "true");

        try {
            const response = await fetch(url.toString(), {
                signal: activeRequestController.signal,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!response.ok) {
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const parsedDocument = parser.parseFromString(html, "text/html");
            const parsedBody = parsedDocument.querySelector(tableBodySelector);
            const parsedHead = parsedDocument.querySelector(tableHeadSelector);
            const parsedPagination =
                parsedDocument.querySelector(paginationSelector);

            if (!parsedBody || !parsedHead || !parsedPagination) {
                return;
            }

            tableBody.innerHTML = parsedBody.innerHTML;
            tableHead.innerHTML = parsedHead.innerHTML;
            paginationWrapper.innerHTML = parsedPagination.innerHTML;

            if (shouldPushState) {
                window.history.replaceState({}, "", url.toString());
            }
        } catch (error) {
            if (error.name !== "AbortError") {
                console.error(error);
            }
        } finally {
            if (requestToken === activeRequestToken) {
                hideSearchLoading();
                searchInput.setAttribute("aria-busy", "false");
            }
        }
    };

    if (searchForm instanceof HTMLFormElement) {
        searchForm.addEventListener("submit", (event) => {
            event.preventDefault();
            refreshTable(buildSearchUrl());
        });
    }

    searchInput.addEventListener("input", () => {
        window.clearTimeout(searchDebounce);
        searchDebounce = window.setTimeout(() => {
            refreshTable(buildSearchUrl());
        }, debounceMs);
    });

    paginationWrapper.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        const link = target.closest("a");

        if (!(link instanceof HTMLAnchorElement) || !link.href) {
            return;
        }

        event.preventDefault();

        const url = buildSearchUrl(link.href);
        refreshTable(url);
    });

    rootElement.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        const link = target.closest(sortLinkSelector);

        if (!(link instanceof HTMLAnchorElement) || !link.href) {
            return;
        }

        event.preventDefault();

        const url = buildSearchUrl(link.href);
        refreshTable(url);
    });

    rootElement.__ajaxTableSearch = {
        refreshTable,
        buildSearchUrl,
    };

    initializedAjaxTableSearchRoots.add(rootElement);
};

const initAllAjaxTableSearch = () => {
    document
        .querySelectorAll("[data-ajax-table-search]")
        .forEach((rootElement) => {
            initAjaxTableSearch(rootElement);
        });
};

window.initAjaxTableSearch = initAjaxTableSearch;
window.initAllAjaxTableSearch = initAllAjaxTableSearch;

document.addEventListener("DOMContentLoaded", () => {
    initAllAjaxTableSearch();
});
