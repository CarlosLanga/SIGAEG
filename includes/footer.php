    <footer style="border-top: 1px solid var(--border-soft); padding: 20px; text-align: center; margin-top: 20px;">
        <p style="font-size: 14px; color: var(--text-main); margin: 0;">
            &copy; <?= date('Y') ?> Desenvolvido por <a href="https://github.com/CarlosLanga" target="_blank" style="color: var(--ideal-yellow); text-decoration: none; font-weight: 500;">Carlos Langa</a>
        </p>
    </footer>

 </div> <!-- Da classe .main-wrapper -->

    <script src="<?= BASE_URL ?>assets/js/jQuery.js"></script>
    <script src="<?= BASE_URL ?>assets/js/layout.js"></script>

    <?php
    $page_js_list = [];
    if (!empty($page_js)) {
        $page_js_list = is_array($page_js) ? $page_js : [$page_js];
    }
    foreach ($page_js_list as $js_file):
    ?>
    <script src="<?= BASE_URL ?>assets/js/<?= $js_file ?>"></script>
    <?php endforeach; ?>
</body>
</html>
