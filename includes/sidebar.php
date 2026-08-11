<?php
require_once __DIR__ . '/menu.php';

$current = basename($_SERVER['PHP_SELF']);
?>

<aside id="sidebar" class="sidebar <?= ($_COOKIE['iicaeg_sidebar'] ?? $_SESSION['sidebar_estado'] ?? 'expandida') === 'colapsada' ? 'collapsed' : '' ?>">

    <!-- PERFIL -->
    <div class="profile-preview" id="profile-info">
        <div class="profile-header">
            <?php
            $avatarName = $_SESSION['usuario_nome'] ?? '';
            $avatarInitials = getInitials($avatarName);
            $avatarPhoto = $_SESSION['usuario_foto'] ?? '';
            $hasPhoto = !empty($avatarPhoto) && $avatarPhoto !== 'default.png';
            ?>
            <a href="<?= BASE_URL ?>pages/perfil.php">
                <?php if ($hasPhoto): ?>
                    <img src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($avatarPhoto) ?>">
                <?php else: ?>
                    <span class="avatar-initials avatar-lg"><?= htmlspecialchars($avatarInitials) ?></span>
                <?php endif; ?>
            </a>
            <div class="p-details">
                <p class="p-name">
                    <a href="<?= BASE_URL ?>pages/perfil.php"><?= $_SESSION['usuario_nome'] ?></a>
                </p>
                <p class="p-role"><?= getCargo($_SESSION['nivel_acesso']) ?></p>
            </div>
        </div>
    </div>

    <div class="sidebar-scroll">
        <div class="sidebar-menu">
            <ul>
                <!-- DASHBOARD -->
                <?php
                $dashUrl = is_callable($MENU['dashboard']['url'])
                    ? $MENU['dashboard']['url']()
                    : $MENU['dashboard']['url'];
                $dashHref = preg_match('~^https?://~i', $dashUrl)
                    ? $dashUrl
                    : BASE_URL . 'pages/' . ltrim($dashUrl, '/');
                $dashPath = parse_url($dashUrl, PHP_URL_PATH) ?? $dashUrl;
                ?>
                <li>
                    <a href="<?= $dashHref ?>"
                        class="<?= basename($dashPath) === $current ? 'active' : '' ?>">
                        <i class="fa-solid <?= $MENU['dashboard']['icon'] ?>"></i>
                        <span><?= $MENU['dashboard']['label'] ?></span>
                    </a>
                </li>

                <?php
                $nivel = (int)($_SESSION['nivel_acesso'] ?? 0);

                function renderMenuItems(array $items, string $current)
                {
                    foreach ($items as $menu) {
                        $children = $menu['children'] ?? [];
                        if (!empty($children) && is_array($children)) {
                            $open = false;
                            foreach ($children as $child) {
                                if (basename($child['url']) === $current) {
                                    $open = true;
                                    break;
                                }
                            }
                ?>
                            <li class="has-dropdown <?= $open ? 'open active' : '' ?>">
                                <a href="javascript:void(0)">
                                    <i class="fa-solid <?= $menu['icon'] ?>"></i>
                                    <span><?= $menu['label'] ?></span>
                                    <i class="fa-solid fa-chevron-right arrow"></i>
                                </a>

                                <ul class="submenu" <?= $open ? 'style="display:block"' : '' ?>>
                                    <?php foreach ($children as $child): ?>
                                        <li>
                                            <a href="<?= $child['url'] ?>"
                                                class="<?= basename($child['url']) === $current ? 'active' : '' ?>">
                                                <i class="fa-solid <?= $child['icon'] ?>"></i>
                                                <?= $child['label'] ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php
                        } else {
                            $url = $menu['url'] ?? '#';
                        ?>
                            <li>
                                <a href="<?= $url ?>" class="<?= basename($url) === $current ? 'active' : '' ?>">
                                    <i class="fa-solid <?= $menu['icon'] ?>"></i>
                                    <span><?= $menu['label'] ?></span>
                                </a>
                            </li>
                <?php
                        }
                    }
                }
                ?>

                <!-- Menus por nivel acesso -->
                <?php if ($nivel === 1): ?>
                    <?php renderMenuItems($MENU['admin']['items'], $current); ?>
                <?php elseif ($nivel === 2): ?>
                    <?php renderMenuItems($MENU['formador']['items'], $current); ?>
                <?php elseif ($nivel === 3): ?>
                    <?php renderMenuItems($MENU['formando']['items'], $current); ?>
                <?php elseif ($nivel === 4): ?>
                    <?php renderMenuItems($MENU['encarregado']['items'], $current); ?>
                <?php endif; ?>

                <!-- EXTRAS -->
                <?php if (!empty($MENU['extras'])): ?>
                    <?php foreach ($MENU['extras'] as $extra): ?>
                        <?php if (!isset($extra['only']) || (int)$extra['only'] === $nivel): ?>
                            <li>
                                <a href="<?= $extra['url'] ?>"
                                    class="<?= basename($extra['url']) === $current ? 'active' : '' ?>">
                                    <i class="fa-solid <?= $extra['icon'] ?>"></i>
                                    <span><?= $extra['label'] ?></span>
                                    <?php if (!empty($extra['badge'])): ?>
                                        <span class="badge"><?= $extra['badge'] ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</aside>