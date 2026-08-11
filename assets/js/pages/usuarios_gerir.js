document.addEventListener('DOMContentLoaded', () => {
    TableManager({
        root: '.form-card',
        mode: 'local',
        paginationMode: 'numbered',
        tbody: '#lista_usuarios',
        info: '#table_info',
        btnPrev: '#btn_prev',
        btnNext: '#btn_next',
        pageNumbers: '#table_page_numbers',
        pageSize: '#page_size',
        search: '#search-users',
        btnPrint: null,
        limit: 10,
        emptyColspan: 9,
        emptyMessage: 'Nenhum utilizador encontrado'
    });

    const modal = document.getElementById('modal_novo_utilizador');
    const openBtn = document.getElementById('btn-add-user');
    const closeBtn = document.getElementById('btn_fechar_novo_utilizador');

    function openModal() {
        if (modal) modal.classList.add('open');
    }

    function closeModal() {
        if (modal) modal.classList.remove('open');
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });
    }
});
