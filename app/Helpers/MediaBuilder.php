<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/**
 * Подготовка структур InputMedia для Telegram API.
 */
class MediaBuilder
{
    /**
     * Фабричный метод формирования структуры InputMedia.
     *
     * @param string $type    Тип медиа (photo, audio, document, video и т.д.)
     * @param string $media   URL, fileId или путь к файлу
     * @param array  $options Дополнительные параметры InputMedia
     *
     * @return array
     */
    public static function buildInputMedia(string $type, string $media, array $options = []): array
    {
        $data = [
            'type' => $type,
            'media' => $media,
        ];

        // Обработка подписи и режима парсинга. Нужно учитывать случаи,
        // когда параметр передан со значением null - такой параметр не
        // должен попадать в итоговый массив, однако его наличие не должно
        // активировать значение по-умолчанию.
        if (array_key_exists('caption', $options)) {
            $caption = $options['caption'];
            unset($options['caption']);

            if ($caption !== null && $caption !== '') {
                $data['caption'] = $caption;

                if (array_key_exists('parse_mode', $options)) {
                    $parseMode = $options['parse_mode'];
                    unset($options['parse_mode']);

                    if ($parseMode !== null && $parseMode !== '') {
                        $data['parse_mode'] = $parseMode;
                    }
                } else {
                    // parse_mode не передан явно - используем значение по-умолчанию
                    $data['parse_mode'] = 'html';
                }
            } elseif (array_key_exists('parse_mode', $options)) {
                // Подпись пустая, но parse_mode передан - нужно корректно
                // обработать значение parse_mode и исключить null.
                $parseMode = $options['parse_mode'];
                unset($options['parse_mode']);

                if ($parseMode !== null && $parseMode !== '') {
                    $data['parse_mode'] = $parseMode;
                }
            }
        } elseif (array_key_exists('parse_mode', $options)) {
            $parseMode = $options['parse_mode'];
            unset($options['parse_mode']);

            if ($parseMode !== null && $parseMode !== '') {
                $data['parse_mode'] = $parseMode;
            }
        }

        // Удаляем из options параметры со значением null
        $options = array_filter($options, static fn ($value) => $value !== null);

        return array_merge($data, $options);
    }

    /**
     * Подготавливает данные для медиа-запросов Telegram API.
     *
     * @param int          $chatId    Id чата
     * @param string       $mediaType Тип медиа (photo, audio, document, video и т.д.)
     * @param array|string $media     URL, fileId или структура InputMedia
     * @param string       $caption   Текст подписи
     * @param array        $options   Дополнительные параметры метода
     *
     * @return array
     */
    public static function prepareMediaData(
        int $chatId,
        string $mediaType,
        array|string $media,
        string $caption = '',
        array $options = []
    ): array {
        if (is_array($media)) {
            $payload = $media;
        } else {
            $method = 'inputMedia' . ucfirst($mediaType);

            if (method_exists(self::class, $method)) {
                $ref = new \ReflectionMethod(self::class, $method);
                $args = [];

                foreach ($ref->getParameters() as $param) {
                    $name = $param->getName();

                    if ($name === 'media') {
                        $args[] = $media;
                        continue;
                    }

                    if ($name === 'caption') {
                        $args[] = $caption !== '' ? $caption : null;
                        continue;
                    }

                    $optionName = self::camelToSnake($name);

                    if (array_key_exists($optionName, $options)) {
                        $args[] = $options[$optionName];
                        unset($options[$optionName]);
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        $args[] = null;
                    }
                }

                $payload = $ref->invokeArgs(null, $args);
            } else {
                $payload = self::buildInputMedia($mediaType, $media, ['caption' => $caption]);
            }
        }

        $data = [
            'chat_id' => $chatId,
            $mediaType => $payload['media'],
        ];

        unset($payload['type'], $payload['media']);

        if ($caption !== '' && !array_key_exists('caption', $payload)) {
            $payload['caption'] = $caption;

            if (!array_key_exists('parse_mode', $payload)) {
                $payload['parse_mode'] = 'html';
            }
        }

        // Удаляем параметры со значением null как из payload, так и из options
        $payload = array_filter($payload, static fn ($value) => $value !== null);
        $options = array_filter($options, static fn ($value) => $value !== null);

        return array_merge($data, $payload, $options);
    }

    /**
     * @param string      $media                 Файл для отправки. Передайте file_id для отправки файла, который
     *                                           существует на серверах Telegram (рекомендуется), передайте URL-адрес
     *                                           HTTP для Telegram, чтобы получить файл из Интернета, или передайте
     *                                           “attach://<имя_файла>”, чтобы загрузить новый файл, используя
     *                                           multipart/form-data под именем <имя_файла>.
     * @param string|null $caption               Подпись к отправляемой фотографии, 0-1024 символа после разбора
     *                                           сущностей
     * @param string|null $parseMode             Режим парсинга подписи. Поддерживаемые режимы: html, markdown
     * @param bool        $showCaptionAboveMedia Показывать подпись выше медиа
     * @param bool        $has_spoiler           Спрятать подпись в спойлере
     *
     * @return array
     */
    public static function inputMediaPhoto(
        string $media,
        string $caption = null,
        string $parseMode = null,
        bool $showCaptionAboveMedia = false,
        bool $has_spoiler = false
    ): array {
        if ($caption !== null && $caption !== '' && mb_strlen($caption) > 1024) {
            throw new RuntimeException('Длина подписи должна быть не более 1024 символов');
        }

        $options = [
            'caption' => $caption !== '' ? $caption : null,
            'parse_mode' => $parseMode,
        ];

        if ($showCaptionAboveMedia) {
            $options['show_caption_above_media'] = true;
        }

        if ($has_spoiler) {
            $options['has_spoiler'] = true;
        }

        $data = self::buildInputMedia('photo', $media, $options);

        return array_filter(
            $data,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * @param string      $media                 Файл для отправки. Передайте file_id для отправки файла, который
     *                                           существует на серверах Telegram (рекомендуется), передайте URL-адрес
     *                                           HTTP для Telegram, чтобы получить файл из Интернета, или передайте
     *                                           “attach://<имя_файла>”, чтобы загрузить новый файл, используя
     *                                           multipart/form-data под именем <имя_файла>.
     * @param string|null $caption
     * @param string|null $thumbnail             Миниатюра отправленного файла; может быть проигнорирована, если
     *                                           создание миниатюр для файла поддерживается на стороне сервера.
     *                                           Миниатюра должна быть в формате JPEG и иметь размер менее 200 Кб.
     *                                           Ширина и высота миниатюры не должны превышать 320. Игнорируется, если
     *                                           файл не загружен с использованием multipart/form-data. Миниатюры
     *                                           нельзя использовать повторно, их можно загрузить только в виде нового
     *                                           файла, поэтому вы можете передать “прикрепить://<имя_файла>”, если
     *                                           миниатюра была загружена с использованием multipart/form-data в
     *                                           <имя_файла>.
     * @param string|null $cover                 Обложка для видео в сообщении. Передайте file_id для отправки файла,
     *                                           который существует на серверах Telegram (рекомендуется), передайте
     *                                           URL-адрес HTTP для Telegram, чтобы получить файл из Интернета, или
     *                                           передайте “attach://<имя_файла>”, чтобы загрузить новый файл,
     *                                           используя multipart/form-data под именем <имя_файла>.
     * @param int|null    $startTimestamp        Стартовая метка времени в секундах, от 0 до 59
     * @param string|null $parseMode             Определяет режим парсинга подписи. Поддерживаемые режимы: html,
     *                                           markdown
     * @param bool        $showCaptionAboveMedia Показывать подпись выше медиа
     * @param int|null    $width                 Ширина видео
     * @param int|null    $height                Высота видео
     * @param int|null    $duration              Длительность видео в секундах, от 0 до 59
     * @param bool        $supportsStreaming     Поддержка стриминга
     * @param bool        $has_spoiler           Спрятать видео в спойлере
     *
     * @return array
     */
    public static function inputMediaVideo(
        string $media,
        string $caption = null,
        string $thumbnail = null,
        string $cover = null,
        int $startTimestamp = null,
        string $parseMode = null,
        bool $showCaptionAboveMedia = false,
        int $width = null,
        int $height = null,
        int $duration = null,
        bool $supportsStreaming = false,
        bool $has_spoiler = false
    ): array {
        if ($caption !== null && $caption !== '' && mb_strlen($caption) > 1024) {
            throw new RuntimeException('Длина подписи должна быть не более 1024 символов');
        }

        foreach (
            [
                'width' => $width,
                'height' => $height,
                'duration' => $duration,
                'startTimestamp' => $startTimestamp,
            ] as $name => $value
        ) {
            if ($value !== null && $value < 0) {
                throw new RuntimeException(sprintf('%s не может быть отрицательным', $name));
            }
        }

        $options = [
            'caption' => $caption !== '' ? $caption : null,
            'parse_mode' => $parseMode,
            'thumbnail' => $thumbnail,
            'cover' => $cover,
            'start_timestamp' => $startTimestamp,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
        ];

        if ($showCaptionAboveMedia) {
            $options['show_caption_above_media'] = true;
        }

        if ($supportsStreaming) {
            $options['supports_streaming'] = true;
        }

        if ($has_spoiler) {
            $options['has_spoiler'] = true;
        }

        $data = self::buildInputMedia('video', $media, $options);

        return array_filter(
            $data,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * @param string      $media       Файл для отправки. Передайте file_id для отправки файла, который существует на
     *                                 серверах Telegram (рекомендуется), передайте URL-адрес HTTP для Telegram, чтобы
     *                                 получить файл из Интернета, или передайте “attach://<имя_файла>”, чтобы загрузить
     *                                 новый файл, используя multipart/form-data под именем <имя_файла>.
     * @param string|null $caption
     * @param string|null $thumbnail   Миниатюра отправленного файла; может быть проигнорирована, если создание миниатюр
     *                                 для файла поддерживается на стороне сервера. Миниатюра должна быть в формате JPEG
     *                                 и иметь размер менее 200 Кб. Ширина и высота миниатюры не должны превышать 320.
     *                                 Игнорируется, если файл не загружен с использованием multipart/form-data.
     *                                 Миниатюры нельзя использовать повторно, их можно загрузить только в виде нового
     *                                 файла, поэтому вы можете передать “прикрепить://<имя_файла>”, если миниатюра была
     *                                 загружена с использованием multipart/form-data под <имя_файла>.
     * @param string|null $parseMode   Определяет режим парсинга подписи. Поддерживаемые режимы: html, markdown
     * @param int|null    $width       Ширина видео
     * @param int|null    $height      Высота видео
     * @param int|null    $duration    Длительность видео в секундах, от 0 до 59
     * @param bool        $has_spoiler Спрятать видео в спойлере
     *
     * @return array
     */
    public static function inputMediaAnimation(
        string $media,
        string $caption = null,
        string $thumbnail = null,
        string $parseMode = null,
        int $width = null,
        int $height = null,
        int $duration = null,
        bool $has_spoiler = false
    ): array {
        if ($caption !== null && $caption !== '' && mb_strlen($caption) > 1024) {
            throw new RuntimeException('Длина подписи должна быть не более 1024 символов');
        }

        foreach (
            [
                'width' => $width,
                'height' => $height,
                'duration' => $duration,
            ] as $name => $value
        ) {
            if ($value !== null && $value < 0) {
                throw new RuntimeException(sprintf('%s не может быть отрицательным', $name));
            }
        }

        $options = [
            'caption' => $caption !== '' ? $caption : null,
            'parse_mode' => $parseMode,
            'thumbnail' => $thumbnail,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
        ];

        if ($has_spoiler) {
            $options['has_spoiler'] = true;
        }

        $data = self::buildInputMedia('animation', $media, $options);

        return array_filter(
            $data,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * @param string      $media                        Файл для отправки. Передайте file_id для отправки файла,
     *                                                  который существует на серверах Telegram (рекомендуется),
     *                                                  передайте URL-адрес HTTP для Telegram, чтобы получить файл из
     *                                                  Интернета, или передайте “attach://<имя_файла>”, чтобы
     *                                                  загрузить новый файл, используя multipart/form-data под именем
     *                                                  <имя_файла>.
     * @param string|null $caption                      Подпись к отправляемой фотографии, 0-1024 символа после разбора
     *                                                  сущностей
     * @param string|null $thumbnail                    Миниатюра отправленного файла; может быть проигнорирована, если
     *                                                  создание миниатюр для файла поддерживается на стороне сервера.
     *                                                  Миниатюра должна быть в формате JPEG и иметь размер менее 200
     *                                                  Кб. Ширина и высота миниатюры не должны превышать 320.
     *                                                  Игнорируется, если файл не загружен с использованием
     *                                                  multipart/form-data. Миниатюры нельзя использовать повторно, их
     *                                                  можно загрузить только в виде нового файла, поэтому вы можете
     *                                                  передать
     *                                                  “прикрепить://<имя_файла>”, если миниатюра была загружена с
     *                                                  использованием multipart/form-data под <имя_файла>.
     * @param string|null $parseMode                    Определяет режим парсинга подписи. Поддерживаемые режимы: html,
     *                                                  markdown
     * @param int|null    $duration                     Длительность аудио в секундах, от 0 до 59
     * @param string|null $performer                    Исполнитель аудио
     * @param string|null $title                        Название аудио
     *
     * @return array
     */
    public static function inputMediaAudio(
        string $media,
        string $caption = null,
        string $thumbnail = null,
        string $parseMode = null,
        int $duration = null,
        string $performer = null,
        string $title = null
    ): array {
        if ($caption !== null && $caption !== '' && mb_strlen($caption) > 1024) {
            throw new RuntimeException('Длина подписи должна быть не более 1024 символов');
        }

        foreach (
            [
                'performer' => $performer,
                'title' => $title,
            ] as $name => $value
        ) {
            if ($value !== null && $value !== '' && mb_strlen($value) > 64) {
                throw new RuntimeException(sprintf('Длина %s должна быть не более 64 символов', $name));
            }
        }

        if ($duration !== null && $duration < 0) {
            throw new RuntimeException('duration не может быть отрицательным');
        }

        $options = [
            'caption' => $caption !== '' ? $caption : null,
            'parse_mode' => $parseMode,
            'thumbnail' => $thumbnail,
            'duration' => $duration,
            'performer' => $performer !== '' ? $performer : null,
            'title' => $title !== '' ? $title : null,
        ];

        $data = self::buildInputMedia('audio', $media, $options);

        return array_filter(
            $data,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * @param string      $media                       Файл для отправки. Передайте file_id для отправки файла, который
     *                                                 существует на серверах Telegram (рекомендуется), передайте
     *                                                 URL-адрес HTTP для Telegram, чтобы получить файл из Интернета,
     *                                                 или передайте “attach://<имя_файла>”, чтобы загрузить новый
     *                                                 файл, используя multipart/form-data под именем <имя_файла>.
     * @param string|null $caption                     Подпись к отправляемой фотографии, 0-1024 символа после разбора
     * @param string|null $thumbnail                   Миниатюра отправленного файла; может быть проигнорирована, если
     *                                                 создание миниатюр для файла поддерживается на стороне сервера.
     *                                                 Миниатюра должна быть в формате JPEG и иметь размер менее 200
     *                                                 Кб. Ширина и высота миниатюры не должны превышать 320.
     *                                                 Игнорируется, если файл не загружен с использованием
     *                                                 multipart/form-data. Миниатюры нельзя использовать повторно, их
     *                                                 можно загрузить только в виде нового файла, поэтому вы можете
     *                                                 передать “прикрепить://<имя_файла>”, если миниатюра была
     *                                                 загружена с использованием multipart/form-data под <имя_файла>.
     * @param string|null $parseMode                   Определяет режим парсинга подписи. Поддерживаемые режимы: html,
     *                                                 markdown
     * @param bool        $disableContentTypeDetection Отключает автоматическое определение типа содержимого на стороне
     *                                                 сервера для файлов, загруженных с использованием
     *                                                 multipart/form-data. Всегда выполняется, если документ
     *                                                 отправляется как часть альбома.
     *
     * @return array
     */
    public static function inputMediaDocument(
        string $media,
        string $caption = null,
        string $thumbnail = null,
        string $parseMode = null,
        bool $disableContentTypeDetection = false
    ): array {
        if ($caption !== null && $caption !== '' && mb_strlen($caption) > 1024) {
            throw new RuntimeException('Длина подписи должна быть не более 1024 символов');
        }

        $options = [
            'caption' => $caption !== '' ? $caption : null,
            'parse_mode' => $parseMode,
            'thumbnail' => $thumbnail,
        ];

        if ($disableContentTypeDetection) {
            $options['disable_content_type_detection'] = true;
        }

        $data = self::buildInputMedia('document', $media, $options);

        return array_filter(
            $data,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    private static function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
