<?php

declare(strict_types=1);

namespace Longman\TelegramBot {
    class PhotoSize
    {
        public function __construct(private string $fileId)
        {
        }
        public function getFileId(): string
        {
            return $this->fileId;
        }
    }
    class Message
    {
        /** @var PhotoSize[] */
        private array $photo;
        public function __construct(array $photo)
        {
            $this->photo = $photo;
        }
        /** @return PhotoSize[] */
        public function getPhoto(): array
        {
            return $this->photo;
        }
    }
    class ServerResponse
    {
        public function __construct(private Message $result)
        {
        }
        public function isOk(): bool
        {
            return true;
        }
        public function getResult(): Message
        {
            return $this->result;
        }
    }
    class Request
    {
        public static array $last = [];
        public static function encodeFile(string $path): string
        {
            return $path;
        }
        public static function sendPhoto(array $params): ServerResponse
        {
            self::$last = ['sendPhoto', $params];
            $msg = new Message([new PhotoSize('photo_file_id')]);
            return new ServerResponse($msg);
        }
        public static function sendDocument(array $params): ServerResponse
        {
            self::$last = ['sendDocument', $params];
            $msg = new Message([]); // not used in test
            return new ServerResponse($msg);
        }
        public static function sendAudio(array $params): ServerResponse
        {
            self::$last = ['sendAudio', $params];
            $msg = new Message([]);
            return new ServerResponse($msg);
        }
        public static function sendVideo(array $params): ServerResponse
        {
            self::$last = ['sendVideo', $params];
            $msg = new Message([]);
            return new ServerResponse($msg);
        }
        public static function sendVoice(array $params): ServerResponse
        {
            self::$last = ['sendVoice', $params];
            $msg = new Message([]);
            return new ServerResponse($msg);
        }
    }
}

namespace Tests\Unit {

    use App\Helpers\Database;
    use App\Helpers\FileService;
    use PDO;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;

    final class FileServiceTest extends TestCase
    {
        private PDO $db;

        protected function setUp(): void
        {
            $_ENV['TELEGRAM_DEFAULT_CHAT_ID'] = '123';
            $this->db = new PDO('sqlite::memory:');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('CREATE TABLE telegram_files (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, original_name TEXT, mime_type TEXT, size INTEGER, file_id TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
            $ref = new ReflectionClass(Database::class);
            $prop = $ref->getProperty('instance');
            $prop->setAccessible(true);
            $prop->setValue(null, $this->db);
        }

        public function testSendPhotoStoresFileId(): void
        {
            $file = tempnam(sys_get_temp_dir(), 'tg');
            file_put_contents($file, 'data');
            $service = new FileService();
            $fileId = $service->sendPhoto($file);

            $this->assertSame('photo_file_id', $fileId);
            $this->assertSame(['sendPhoto', ['chat_id' => 123, 'photo' => $file]], \Longman\TelegramBot\Request::$last);
            $row = $this->db->query('SELECT type, original_name, file_id FROM telegram_files')->fetch();
            $this->assertSame('photo', $row['type']);
            $this->assertSame(basename($file), $row['original_name']);
            $this->assertSame('photo_file_id', $row['file_id']);
            unlink($file);
        }
    }
}
