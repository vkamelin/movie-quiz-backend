<?php
/** @var array $user */
/** @var array<int,array<string,mixed>> $items */
?>
<style>
    .chat-wrapper { max-width: 900px; margin: 0 auto; }
    .chat-item { display: flex; margin: 8px 0; }
    .chat-item.in { justify-content: flex-start; }
    .chat-item.out { justify-content: flex-end; }
    .bubble { max-width: 75%; padding: 10px 12px; border-radius: 12px; }
    /* Light theme defaults */
    .in .bubble { background: var(--chat-in-bg, #f1f3f5); border-top-left-radius: 4px; }
    .out .bubble { background: var(--chat-out-bg, #d1e7dd); border-top-right-radius: 4px; }
    .meta { font-size: 12px; color: var(--bs-secondary-color, #6c757d); margin-top: 4px; }
    .bubble img, .bubble video { max-width: 100%; height: auto; border-radius: 6px; }
    .caption { margin-top: 6px; white-space: pre-wrap; }
    .text-msg { white-space: pre-wrap; }

    /* Dark theme adjustments */
    [data-bs-theme="dark"] .in .bubble {
        /* Make incoming (user) messages stand out on dark bg */
        background: var(--chat-in-bg-dark, rgba(255,255,255,.08));
        border: 1px solid rgba(255,255,255,.12);
        box-shadow: 0 1px 2px rgba(0,0,0,.4);
    }
    [data-bs-theme="dark"] .out .bubble {
        /* subtle success tint on dark */
        background: var(--chat-out-bg-dark, rgba(var(--bs-success-rgb, 25,135,84), .22));
        border: 1px solid rgba(255,255,255,.06);
        box-shadow: 0 1px 2px rgba(0,0,0,.4);
    }
    [data-bs-theme="dark"] .meta {
        color: var(--bs-secondary-color, #a7acb1);
    }
</style>

<h1>История чата: <?= htmlspecialchars($user['username'] ?: $user['user_id']) ?></h1>

<div class="row">
    <div class="col-lg-6 col-md-12">

        <div class="card">
            <div class="card-body">
                <div class="chat-wrapper">
                    <?php foreach ($items as $it): ?>
                        <?php $dir = ($it['direction'] ?? 'in') === 'out' ? 'out' : 'in'; ?>
                        <div class="chat-item <?= $dir ?>">
                            <div class="bubble">
                                <?php $type = (string)($it['type'] ?? 'text'); ?>

                                <?php if ($type === 'text'): ?>
                                    <div class="text-msg"><?= nl2br(htmlspecialchars((string)($it['text'] ?? ''))) ?></div>

                                <?php elseif ($type === 'photo' && !empty($it['file_url'])): ?>
                                    <img src="<?= htmlspecialchars((string)$it['file_url']) ?>" alt="photo">
                                    <?php if (!empty($it['caption'])): ?>
                                        <div class="caption"><?= nl2br(htmlspecialchars((string)$it['caption'])) ?></div>
                                    <?php endif; ?>

                                <?php elseif (in_array($type, ['video','animation','video_note'], true) && !empty($it['file_url'])): ?>
                                    <video controls src="<?= htmlspecialchars((string)$it['file_url']) ?>"></video>
                                    <?php if (!empty($it['caption'])): ?>
                                        <div class="caption"><?= nl2br(htmlspecialchars((string)$it['caption'])) ?></div>
                                    <?php endif; ?>

                                <?php elseif (in_array($type, ['audio','voice'], true) && !empty($it['file_url'])): ?>
                                    <audio controls src="<?= htmlspecialchars((string)$it['file_url']) ?>"></audio>
                                    <?php if (!empty($it['caption'])): ?>
                                        <div class="caption"><?= nl2br(htmlspecialchars((string)$it['caption'])) ?></div>
                                    <?php endif; ?>

                                <?php elseif ($type === 'document' && !empty($it['file_url'])): ?>
                                    <a href="<?= htmlspecialchars((string)$it['file_url']) ?>" target="_blank">
                                        <?= htmlspecialchars((string)($it['file_name'] ?? 'Документ')) ?>
                                    </a>
                                    <?php if (!empty($it['caption'])): ?>
                                        <div class="caption"><?= nl2br(htmlspecialchars((string)$it['caption'])) ?></div>
                                    <?php endif; ?>

                                <?php elseif ($type === 'sticker' && !empty($it['file_url'])): ?>
                                    <img src="<?= htmlspecialchars((string)$it['file_url']) ?>" alt="sticker">

                                <?php else: ?>
                                    <div class="text-msg">[<?= htmlspecialchars($type) ?>]</div>
                                <?php endif; ?>

                                <?php if (!empty($it['ts'])): ?>
                                    <div class="meta">
                                        <?= htmlspecialchars(date('Y-m-d H:i:s', (int)$it['ts'])) ?>
                                        · <?= $dir === 'out' ? 'вы' : 'пользователь' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>


