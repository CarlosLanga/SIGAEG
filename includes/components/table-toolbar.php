<?php
if (!isset($filter_id)) {
    $filter_id = 'filtro'; 
    }
if (!isset($filter_label)) { 
    $filter_label = 'Filtro'; 
    }
if (!isset($filter_all)) { 
    $filter_all = 'Todos'; 
    }
if (!isset($search_id)) { $search_id = 'pesquisa'; }
if (!isset($print_id)) { $print_id = 'btn_imprimir'; }
if (!isset($print_text)) { $print_text = 'Imprimir'; }
?>

<div class="table-toolbar">
    <div class="toolbar-left">
        <label class="filter-field">
            <span><?= htmlspecialchars($filter_label) ?></span>
            <div class="select-wrap">
                <select id="<?= htmlspecialchars($filter_id) ?>">
                    <option value="all"><?= htmlspecialchars($filter_all) ?></option>
                </select>
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </label>

        <label class="filter-field">
            <span>Pesquisar</span>
            <input type="text" id="<?= htmlspecialchars($search_id) ?>">
        </label>
    </div>

    <div class="toolbar-right">
        <button class="btn btn-outline btn-table" id="<?= htmlspecialchars($print_id) ?>">
            <i class="fa-solid fa-print"></i>
            <span class="btn-text"><?= htmlspecialchars($print_text) ?></span>
        </button>
    </div>
</div>
