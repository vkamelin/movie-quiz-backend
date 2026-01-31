<?php
/**
 * Submenu partial (Bootstrap nav-underline)
 *
 * @var array $submenu Подменю
 */
?>

<ul class="nav nav-underline mb-3 submenu">
    <?php foreach ($submenu as $item): ?>
        <?php
            $itemClass = (string)($item['class'] ?? '');
            $isActive = str_contains($itemClass, 'active');
            $isDisabled = !empty($item['disabled']);
            $icon = (string)($item['icon'] ?? 'bi bi-chevron-right');

            $classes = ['nav-link'];
            if ($isActive) {
                $classes[] = 'active';
            }
            if ($isDisabled) {
                $classes[] = 'disabled';
            }
            $href = $isDisabled ? '#' : url($item['url']);
        ?>
        <li class="nav-item">
            <a
                class="<?= implode(' ', $classes) ?>"
                href="<?= $href ?>"
                <?php if ($isActive): ?>aria-current="page"<?php endif; ?>
                <?php if ($isDisabled): ?>aria-disabled="true" tabindex="-1"<?php endif; ?>
            >
                <i class="<?= $icon ?>"></i>
                <span class="ms-1"><?= $item['title'] ?></span>
            </a>
        </li>
    <?php endforeach; ?>
    </ul>
