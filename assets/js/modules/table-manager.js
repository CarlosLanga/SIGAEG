window.TableManager = function (config) {
    const $page = $(config.root);
    const listUrl = $page.data("list-url");
    const filterUrl = $page.data("turmas-url");

    const $tbody = $(config.tbody);
    const $info = $(config.info);
    const $btnPrev = $(config.btnPrev);
    const $btnNext = $(config.btnNext);
    const $btnPage = config.btnPage ? $(config.btnPage) : $();
    const $pageNumbers = config.pageNumbers ? $(config.pageNumbers) : $();
    const $pageSize = config.pageSize ? $(config.pageSize) : $();
    const $filter = config.filter ? $(config.filter) : null;
    const $search = config.search ? $(config.search) : null;
    const $btnPrint = config.btnPrint ? $(config.btnPrint) : null;

    const mode = config.mode || "remote";
    const paginationMode = config.paginationMode || "classic";
    const emptyColspan = config.emptyColspan || 8;
    const emptyMessage = config.emptyMessage || "Nenhum registo encontrado";

    let currentPage = 1;
    let total = 0;
    let totalDisplay = 0;
    let shownTotal = 0;
    let limit = parseInt($pageSize.val(), 10) || config.limit || 20;
    let localRows = [];
    let filteredLocalRows = [];

    function getTotalPages() {
        return Math.max(1, Math.ceil(total / limit));
    }

    function getRangeLabel() {
        if (total === 0) {
            return "0-0 de 0";
        }

        const start = (currentPage - 1) * limit + 1;
        const end = Math.min(currentPage * limit, total);
        return `${start}-${end} de ${totalDisplay}`;
    }

    function buildPageItems(totalPages) {
        if (totalPages <= 7) {
            return Array.from({ length: totalPages }, (_, index) => index + 1);
        }

        if (currentPage <= 4) {
            return [1, 2, 3, 4, 5, "...", totalPages];
        }

        if (currentPage >= totalPages - 3) {
            return [1, "...", totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
        }

        return [1, "...", currentPage - 1, currentPage, currentPage + 1, "...", totalPages];
    }

    function renderPageNumbers() {
        if (!$pageNumbers.length) {
            return;
        }

        const totalPages = getTotalPages();
        const items = buildPageItems(totalPages);

        const html = items.map((item) => {
            if (item === "...") {
                return '<span class="table-page-ellipsis">...</span>';
            }

            const activeClass = item === currentPage ? " btn-active" : "";
            const outlineClass = item === currentPage ? "" : " btn-outline";
            return `
                <button type="button" class="btn btn-table table-page-link${outlineClass}${activeClass}" data-page="${item}">
                    ${item}
                </button>
            `;
        }).join("");

        $pageNumbers.html(html);
    }

    function updatePagination() {
        const totalPages = getTotalPages();

        if (paginationMode === "numbered") {
            $info.text(getRangeLabel());
            if ($pageSize.length) {
                $pageSize.val(String(limit));
            }
            $btnPrev.prop("disabled", currentPage <= 1);
            $btnNext.prop("disabled", currentPage >= totalPages);
            renderPageNumbers();
            if ($btnPage.length) {
                $btnPage.text(currentPage);
            }
            return;
        }

        $info.text(`A mostrar ${shownTotal} de ${totalDisplay}`);
        if ($btnPage.length) {
            $btnPage.text(currentPage);
        }
        $btnPrev.prop("disabled", currentPage <= 1);
        $btnNext.prop("disabled", currentPage >= totalPages);
    }

    function renderEmpty() {
        $tbody.html(`<tr><td colspan="${emptyColspan}" class="empty-row">${emptyMessage}</td></tr>`);
    }

    function updatePrintButton() {
        if (!$btnPrint) return;
        const hasRows = total > 0;
        if ($filter) {
            const turma = $filter.val();
            $btnPrint.prop("disabled", turma === "all" || !hasRows);
            return;
        }
        $btnPrint.prop("disabled", !hasRows);
    }

    function renderLocalList() {
        const search = $search ? $search.val().trim().toLowerCase() : "";
        filteredLocalRows = localRows.filter(($row) => $row.text().toLowerCase().includes(search));

        total = filteredLocalRows.length;
        totalDisplay = localRows.length;
        shownTotal = total;

        const totalPages = getTotalPages();
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        if (!filteredLocalRows.length) {
            renderEmpty();
            updatePagination();
            updatePrintButton();
            return;
        }

        const start = (currentPage - 1) * limit;
        const end = start + limit;

        $tbody.empty();
        filteredLocalRows.slice(start, end).forEach(($row) => {
            $tbody.append($row);
        });

        updatePagination();
        updatePrintButton();
    }

    function fetchList() {
        if (mode === "local") {
            renderLocalList();
            return;
        }

        const turma = $filter ? $filter.val() : null;
        const search = $search ? $search.val().trim() : "";
        const extraParams = typeof config.extraParams === "function" ? config.extraParams() : {};
        const params = { search, page: currentPage, limit, ...extraParams };

        if (turma !== null) {
            params.turma_id = turma;
        }

        $.getJSON(listUrl, params, function (res) {
            if (!res.ok) {
                renderEmpty();
                total = 0;
                totalDisplay = 0;
                shownTotal = 0;
                updatePagination();
                updatePrintButton();
                return;
            }

            total = res.total_filtrado ?? res.total ?? 0;
            totalDisplay = res.total ?? total;
            shownTotal = res.total_filtrado ?? total;
            config.render(res.rows || [], currentPage, limit);
            updatePagination();
            updatePrintButton();
            _fireReady();
        });
    }

    function fetchFilters() {
        if (!filterUrl || !config.renderFilters) return;
        $.getJSON(filterUrl, function (rows) {
            if (!rows.length) return;
            config.renderFilters(rows);
        });
    }

    if (mode === "local") {
        localRows = $tbody.children("tr").filter(function () {
            return !$(this).find(".empty-row").length;
        }).map(function () {
            return $(this).clone(true, true);
        }).get();
    }

    if ($filter) {
        $filter.on("change", function () {
            currentPage = 1;
            fetchList();
            updatePrintButton();
        });
    }

    if ($search) {
        $search.on("input", function () {
            currentPage = 1;
            fetchList();
        });
    }

    if ($pageSize.length) {
        $pageSize.on("change", function () {
            const nextLimit = parseInt($(this).val(), 10);
            limit = Number.isFinite(nextLimit) && nextLimit > 0 ? nextLimit : (config.limit || 20);
            currentPage = 1;
            fetchList();
        });
    }

    $btnPrev.on("click", function () {
        if (currentPage > 1) {
            currentPage -= 1;
            fetchList();
        }
    });

    $btnNext.on("click", function () {
        if (currentPage < getTotalPages()) {
            currentPage += 1;
            fetchList();
        }
    });

    if ($pageNumbers.length) {
        $pageNumbers.on("click", "[data-page]", function () {
            const page = parseInt($(this).data("page"), 10);
            if (!Number.isFinite(page) || page === currentPage) {
                return;
            }
            currentPage = page;
            fetchList();
        });
    }

    let _readyFired = false;
    function _fireReady() {
        if (_readyFired) return;
        _readyFired = true;
        if (typeof config.onReady === 'function') {
            config.onReady(api);
        }
    }

    fetchFilters();
    fetchList();

    const api = {
        refresh: fetchList,
        resetPage: function () {
            currentPage = 1;
            fetchList();
        },
        setFilter: function (val) {
            if ($filter && $filter.length) {
                $filter.val(val);
            }
        },
        setSearch: function (val) {
            if ($search && $search.length) {
                $search.val(val);
            }
        },
        setPage: function (p) {
            currentPage = p;
        },
        getFilter: function () {
            return $filter ? $filter.val() : null;
        },
        getSearch: function () {
            return $search ? $search.val() : '';
        },
        getPage: function () {
            return currentPage;
        },
        fireReady: _fireReady
    };

    return api;
};
