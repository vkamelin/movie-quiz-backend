<?php
/** @var array $groups */
/** @var array $errors */
/** @var array $data */
/** @var string $csrfToken */
?>
<h1 class="mb-3">Отправить сообщение</h1>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<form id="message-send-form" method="post" action="<?= url('/dashboard/messages/send') ?>" enctype="multipart/form-data" data-tg-user-search-url="<?= url('/dashboard/tg-users/search') ?>">
    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">

    <div class="mb-3">
        <label for="messageType" class="form-label">Тип</label>
        <select class="form-select" name="type" id="messageType">
            <?php
            $types = [
                'text' => 'Текст',
                'photo' => 'Фото',
                'audio' => 'Аудио',
                'video' => 'Видео',
                'document' => 'Документ',
                'sticker' => 'Стикер',
                'animation' => 'Анимация (GIF)',
                'voice' => 'Голосовое смообщение',
                'video_note' => 'Видео кружочек',
                'media_group' => 'Медиа группа'
            ];
            $curType = $data['type'] ?? 'text';
            foreach ($types as $val => $label): ?>
                <option value="<?= $val ?>" <?= $curType === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="message-fields <?= $curType === 'text' ? '' : 'd-none' ?>" data-type="text">
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="pre"><i class="bi bi-braces"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-text" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 140px; white-space: pre-wrap;" data-hidden-id="text" data-counter-id="counter-text" data-limit="4096"><?= nl2br(htmlspecialchars($data['text'] ?? '')) ?></div>
                <textarea class="d-none" name="text" id="text"></textarea>
                <div class="form-text"><span id="counter-text">0</span>/4096</div>
            </div>

            <div class="message-fields <?= $curType === 'photo' ? '' : 'd-none' ?>" data-type="photo">
                <input class="form-control mb-2" type="file" name="photo">
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-caption-photo" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 80px; white-space: pre-wrap;" data-hidden-id="caption-photo" data-counter-id="counter-caption-photo" data-limit="1024"><?= nl2br(htmlspecialchars($data['caption'] ?? '')) ?></div>
                <textarea class="d-none" name="caption" id="caption-photo"></textarea>
                <div class="form-text"><span id="counter-caption-photo">0</span>/1024</div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="has_spoiler" id="photoSpoiler" <?= !empty($data['has_spoiler']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="photoSpoiler">Спойлер</label>
                </div>
            </div>

            <div class="message-fields <?= $curType === 'audio' ? '' : 'd-none' ?>" data-type="audio">
                <input class="form-control mb-2" type="file" name="audio">
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-caption-audio" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 80px; white-space: pre-wrap;" data-hidden-id="caption-audio" data-counter-id="counter-caption-audio" data-limit="1024"><?= nl2br(htmlspecialchars($data['caption'] ?? '')) ?></div>
                <textarea class="d-none" name="caption" id="caption-audio"></textarea>
                <div class="form-text"><span id="counter-caption-audio">0</span>/1024</div>
                <input class="form-control mb-2" type="number" name="duration" placeholder="Продолжительность (секунд)" value="<?= htmlspecialchars($data['duration'] ?? '') ?>">
                <input class="form-control mb-2" type="text" name="performer" placeholder="Автор" value="<?= htmlspecialchars($data['performer'] ?? '') ?>">
                <input class="form-control" type="text" name="title" placeholder="Название" value="<?= htmlspecialchars($data['title'] ?? '') ?>">
            </div>

            <div class="message-fields <?= $curType === 'video' ? '' : 'd-none' ?>" data-type="video">
                <input class="form-control mb-2" type="file" name="video">
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-caption-video" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 80px; white-space: pre-wrap;" data-hidden-id="caption-video" data-counter-id="counter-caption-video" data-limit="1024"><?= nl2br(htmlspecialchars($data['caption'] ?? '')) ?></div>
                <textarea class="d-none" name="caption" id="caption-video"></textarea>
                <div class="form-text"><span id="counter-caption-video">0</span>/1024</div>
                <div class="row g-2 mb-2">
                    <div class="col"><input class="form-control" type="number" name="width" placeholder="Ширина" value="<?= htmlspecialchars($data['width'] ?? '') ?>"></div>
                    <div class="col"><input class="form-control" type="number" name="height" placeholder="Высота" value="<?= htmlspecialchars($data['height'] ?? '') ?>"></div>
                </div>
                <input class="form-control mb-2" type="number" name="duration" placeholder="Продолжительность (секунд)" value="<?= htmlspecialchars($data['duration'] ?? '') ?>">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="has_spoiler" id="videoSpoiler" <?= !empty($data['has_spoiler']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="videoSpoiler">Спойлер</label>
                </div>
            </div>

            <div class="message-fields <?= $curType === 'document' ? '' : 'd-none' ?>" data-type="document">
                <input class="form-control mb-2" type="file" name="document">
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-caption-document" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 80px; white-space: pre-wrap;" data-hidden-id="caption-document" data-counter-id="counter-caption-document" data-limit="1024"><?= nl2br(htmlspecialchars($data['caption'] ?? '')) ?></div>
                <textarea class="d-none" name="caption" id="caption-document"></textarea>
                <div class="form-text"><span id="counter-caption-document">0</span>/1024</div>
            </div>

            <div class="message-fields <?= $curType === 'sticker' ? '' : 'd-none' ?>" data-type="sticker">
                <input class="form-control" type="file" name="sticker">
            </div>

            <div class="message-fields <?= $curType === 'animation' ? '' : 'd-none' ?>" data-type="animation">
                <input class="form-control mb-2" type="file" name="animation">
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-caption-animation" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 80px; white-space: pre-wrap;" data-hidden-id="caption-animation" data-counter-id="counter-caption-animation" data-limit="1024"><?= nl2br(htmlspecialchars($data['caption'] ?? '')) ?></div>
                <textarea class="d-none" name="caption" id="caption-animation"></textarea>
                <div class="form-text"><span id="counter-caption-animation">0</span>/1024</div>
                <div class="row g-2 mb-2">
                    <div class="col"><input class="form-control" type="number" name="width" placeholder="Шитрина" value="<?= htmlspecialchars($data['width'] ?? '') ?>"></div>
                    <div class="col"><input class="form-control" type="number" name="height" placeholder="Высота" value="<?= htmlspecialchars($data['height'] ?? '') ?>"></div>
                </div>
                <input class="form-control mb-2" type="number" name="duration" placeholder="Продолжительность (секунд)" value="<?= htmlspecialchars($data['duration'] ?? '') ?>">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="has_spoiler" id="animationSpoiler" <?= !empty($data['has_spoiler']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="animationSpoiler">Спойлер</label>
                </div>
            </div>

            <div class="message-fields <?= $curType === 'voice' ? '' : 'd-none' ?>" data-type="voice">
                <input class="form-control mb-2" type="file" name="voice">
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-caption-voice" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 80px; white-space: pre-wrap;" data-hidden-id="caption-voice" data-counter-id="counter-caption-voice" data-limit="1024"><?= nl2br(htmlspecialchars($data['caption'] ?? '')) ?></div>
                <textarea class="d-none" name="caption" id="caption-voice"></textarea>
                <div class="form-text"><span id="counter-caption-voice">0</span>/1024</div>
                <input class="form-control" type="number" name="duration" placeholder="Продолжительность (секунд)" value="<?= htmlspecialchars($data['duration'] ?? '') ?>">
            </div>

            <div class="message-fields <?= $curType === 'video_note' ? '' : 'd-none' ?>" data-type="video_note">
                <input class="form-control" type="file" name="video_note">
                <div class="row g-2 mt-2">
                    <div class="col"><input class="form-control" type="number" name="length" placeholder="Ширина/Высота" value="<?= htmlspecialchars($data['length'] ?? '') ?>"></div>
                    <div class="col"><input class="form-control" type="number" name="duration" placeholder="Продолжительность (секунд)" value="<?= htmlspecialchars($data['duration'] ?? '') ?>"></div>
                </div>
            </div>

            <div class="message-fields <?= $curType === 'media_group' ? '' : 'd-none' ?>" data-type="media_group">
                <input class="form-control mb-2" type="file" name="media[]" multiple>
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="s"><i class="bi bi-type-strikethrough"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary wysi-btn" data-wrap="code"><i class="bi bi-code"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="spoiler">tg-spoiler</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="blockquote"><i class="bi bi-chat-right-quote"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="editor-caption-media_group" class="form-control wysiwyg-editor" contenteditable="true" style="min-height: 80px; white-space: pre-wrap;" data-hidden-id="caption-media_group" data-counter-id="counter-caption-media_group" data-limit="1024"><?= nl2br(htmlspecialchars($data['caption'] ?? '')) ?></div>
                <textarea class="d-none" name="caption" id="caption-media_group"></textarea>
                <div class="form-text"><span id="counter-caption-media_group">0</span>/1024</div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="modeAll" value="all" <?= (($data['mode'] ?? '') === 'all') ? 'checked' : '' ?>>
            <label class="form-check-label" for="modeAll">Все пользователи</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="modeSingle" value="single" <?= (($data['mode'] ?? '') === 'single') ? 'checked' : '' ?>>
            <label class="form-check-label" for="modeSingle">Отдельный пользователь/канал/чат</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="modeSelected" value="selected" <?= (($data['mode'] ?? '') === 'selected') ? 'checked' : '' ?>>
            <label class="form-check-label" for="modeSelected">Выбранные пользователи</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="modeGroup" value="group" <?= (($data['mode'] ?? '') === 'group') ? 'checked' : '' ?>>
            <label class="form-check-label" for="modeGroup">Группа</label>
        </div>
    </div>
    <div id="singleUserSection" class="mb-3 d-none">
        <input type="text" class="form-control" name="user" id="singleUserInput" placeholder="ID пользователя или логин" value="<?= htmlspecialchars($data['user'] ?? '') ?>">
    </div>
    <div id="selectedUsersSection" class="mb-3 d-none">
        <label for="userSelect" class="form-label">Выберите пользователей</label>
        <select id="userSelect" name="users[]" class="form-select" multiple style="width: 100%" data-placeholder="Начните ввод для поиска...">
            <?php if (!empty($data['users']) && is_array($data['users'])): foreach ($data['users'] as $u): ?>
                <option value="<?= htmlspecialchars((string)$u) ?>" selected><?= htmlspecialchars((string)$u) ?></option>
            <?php endforeach; endif; ?>
        </select>
        <div class="form-text">Ищите по ID, логину или имени.</div>
    </div>
    <div id="groupSection" class="mb-3 d-none">
        <select class="form-select" name="group_id" id="groupSelect">
            <option value="">Выберите группу</option>
            <?php foreach ($groups as $g): ?>
                <option value="<?= $g['id'] ?>" <?= (($data['group_id'] ?? '') == $g['id']) ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="msgTypeInput" class="form-label">Тип сообщения (латиница)</label>
        <input class="form-control" name="msg_type" id="msgTypeInput" placeholder="например, campaign, promo, service" value="<?= htmlspecialchars($data['msg_type'] ?? 'message') ?>">
        <div class="form-text">Сохранится в колонке <code>type</code> таблиц сообщений.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Время отправки</label>
        <?php $sendMode = $data['send_mode'] ?? 'now'; ?>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="send_mode" id="sendNow" value="now" <?= $sendMode === 'now' ? 'checked' : '' ?>>
            <label class="form-check-label" for="sendNow">Отправить сейчас</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="send_mode" id="sendLater" value="schedule" <?= $sendMode === 'schedule' ? 'checked' : '' ?>>
            <label class="form-check-label" for="sendLater">По расписанию</label>
        </div>
        <input type="datetime-local" class="form-control" name="send_after" id="sendAfterInput" value="<?= htmlspecialchars($data['send_after'] ?? '') ?>">
        <div class="form-text">Будет использовано серверное время (<?= date('H:i d.m.Y') ?>).</div>
    </div>
    <button type="submit" class="btn btn-outline-success">Отправить</button>
</form>
<!-- removed inline scripts to comply with CSP -->
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.9/dist/purify.min.js"></script>
<script src="<?= url('/assets/js/wysiwyg.js') ?>"></script>
<script src="<?= url('/assets/js/message-send.js') ?>"></script>
