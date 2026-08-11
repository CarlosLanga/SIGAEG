<?php
// Este componente é responsável por exibir as breadcrumbs (trilhas de navegação) em páginas específicas do sistema.
/* modelo:
$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
    ['label' => 'Formandos', 'url' => null],
    ['label' => 'Detalhes do Formando', 'url' => null],
]
*/

if(empty($breadcrumbs) || !is_array($breadcrumbs)) {
    return;
}
?>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol>
        <?php foreach($breadcrumbs as $item): ?>
            <li>
                <?php if(!empty($item['url'])): ?>
                    <a href="<?= $item['url'] ?>">
                        <?php if ($item['label'] === 'Início'): ?>
                            <i class="fa-solid fa-home"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php else: ?>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>

</nav>